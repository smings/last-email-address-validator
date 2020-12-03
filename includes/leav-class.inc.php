<?php

defined('ABSPATH') or die('Not today!');

require_once("leav-central.inc.php");
require_once("leav-helper-functions.inc.php");

class LastEmailAddressValidator
{
	private $central;
	private $collapsed_recipient_name = '';
	private $detected_wp_email_domain = '';
	private $email_address = '';
	private $email_domain = '';
	private $email_domain_ip_address = '';
	private $normalized_email_address = '';
	private $smtp_connection;
	private $smtp_connection_is_open = false;
	private $wp_email_domain = '';
	public  $email_domain_has_MX_records = false;
	public  $error_type = '';
	public  $is_email_address_from_dea_service = false;
	public  $is_email_address_inline_catch_all = false;
	public  $is_email_address_on_free_email_address_provider_list = false;
	public  $is_email_address_on_user_defined_blacklist = false;
	public  $is_email_address_on_user_defined_whitelist = false;
	public  $is_email_address_syntax_valid = false;
	public  $is_email_domain_on_user_defined_blacklist = false;
	public  $is_email_domain_on_user_defined_whitelist = false;
	public  $mx_server_domains = array();
	public  $mx_server_ips = array();
	public  $mx_server_preferences = array();
	public  $recipient_name = '';
	public  $simulated_sending_succeeded = false;

	// timeout in s
	private static $FSOCKOPEN_TIMEOUT = 2;

	// timeouts in ms
	private static $SMTP_CONNECTION_TIMEOUT_LONG  = 3000;
	private static $SMTP_CONNECTION_TIMEOUT_SHORT = 1000;


// --------------- Public functions --------------------------------------------


	public function __construct( LeavCentral $central, string $email_address = '', string $wp_email_domain = '' )
	{
		$this->central = $central;
		$this->reset_class_attributes();
		$this->detect_wp_email_domain();

		if( ! empty($email_address) )
		{
			$this->email_address = $email_address;
			$this->normalize_email_address();
			$this->validate_current_email_address_syntax();
			if( $this->is_email_address_syntax_valid )
				$this->extract_recipient_name_and_domain_from_email_address();
		}

		if( ! empty($wp_email_domain) )
			$this->wp_email_domain = $wp_email_domain;
		elseif( empty( $this->wp_email_domain ) )
			$this->wp_email_domain = $this->detected_wp_email_domain;
	}


	// this is resetting and reusing everything without instanciating a new class
	public function reuse( string $email_address ) : bool
	{
		$this->reset_class_attributes();
		$this->email_address = $email_address;
		$this->normalize_email_address();
		$this->validate_current_email_address_syntax();
		if( ! $this->is_email_address_syntax_valid )
			return false;
		return $this->extract_recipient_name_and_domain_from_email_address();
	}


	public function set_wordpress_email_domain( string $wp_email_domain )
	{
		$this->wp_email_domain = $wp_email_domain;
	}


	public function validate_email_address_syntax( string &$email_address ) : bool
	{
		return $this->reuse( $email_address );
	}


	public function is_email_domain_on_user_defined_whitelist( array &$list ) : bool
	{
		if( ! $this->is_email_domain_in_list( $list ) )
			return false;
		$this->is_email_domain_on_user_defined_whitelist = true;
		return true;
	}


	public function check_if_email_address_is_on_user_defined_whitelist( array &$list ) : bool
	{
		if( in_array( $this->normalized_email_address, $list ) )
			$this->is_email_address_on_user_defined_whitelist = true;
		return $this->is_email_address_on_user_defined_whitelist;
	}


	public function is_email_domain_on_user_defined_blacklist( array &$list ) : bool
	{
		if( ! $this->is_email_domain_in_list( $list ) )
			return false;
		$this->is_email_domain_on_user_defined_blacklist = true;
		$this->set_error_type( 'email_domain_is_blacklisted' );
		return true;
	}


	public function is_email_domain_on_free_email_address_provider_domain_list( array &$list ) : bool
	{
		if( ! $this->is_email_domain_in_list( $list ) )
			return false;
		$this->is_email_address_on_free_email_address_provider_list = true;
		$this->set_error_type( 'email_domain_is_on_free_email_address_provider_domain_list' );
		return true;
	}

	public function is_collapsed_recipient_name_empty () : bool
	{
		if( empty( $this->collapsed_recipient_name ) )
		{
			$this->set_error_type( 'recipient_name_is_role_based' );
			return true;
		}
		return false;
	}

	public function check_if_email_address_is_on_user_defined_blacklist( array &$list ) : bool
	{
		if( in_array( $this->normalized_email_address, $list ) )
		{
			$this->is_email_address_on_user_defined_blacklist = true;
			$this->set_error_type( 'email_address_is_blacklisted' );
		}
		return $this->is_email_address_on_user_defined_blacklist;
	}


	public function check_if_email_address_is_from_dea_service( array &$domain_list, array &$mx_domain_list, array &$mx_ip_list ) : bool
	{
		if( ! $this->has_mx_records() )
			return false;
		if(    in_array( $this->email_domain, $domain_list )
			  || ! empty( array_intersect( $this->mx_server_domains, $mx_domain_list ) )
			  || ! empty( array_intersect( $this->mx_server_ips, $mx_ip_list ) )
		)
		{
			$this->is_email_address_from_dea_service = true;
			$this->set_error_type( 'email_domain_is_on_dea_blacklist' );
		}

		return $this->is_email_address_from_dea_service;
	}


	public function simulate_sending_an_email( string $email_address = '', string $wp_email_domain = '' )
	{
		if( ! empty($wp_email_domain) )
			$sender_email_domain = $wp_email_domain;
		elseif( ! empty($this->wp_email_domain) )
			$sender_email_domain = $this->wp_email_domain;
		else
			$sender_email_domain = $this->detected_wp_email_domain;

		if( ! $this->has_mx_records() )
			return false;

		if( ! $this->get_smtp_connection() )
			return false;

		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != '220' ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, 'HELO ' . $sender_email_domain . "\n" );
		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != '250' ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, 'MAIL FROM: <no-reply@' . $sender_email_domain . ">\n" );
		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_SHORT );
		if( substr( $answer, 0, 3 ) != '250' ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, 'RCPT TO: <' . $this->normalized_email_address . ">\n" );
		if( substr( $answer, 0, 3 ) != '250' ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}
		$this->close_smtp_connection();
		$this->simulated_sending_succeeded = true;
		return true;
	}


	public function get_detected_wp_email_domain()
	{
		return $this->detected_wp_email_domain;
	}

	public function is_comment_line( string &$line ) : bool
	{
		if(		 preg_match( $this->central::$COMMENT_LINE_REGEX, $line )
				|| preg_match( $this->central::$EMPTY_LINE_REGEX,   $line )
		)
			return true;
		return false;
	}

	public function line_contains_wildcard( string &$line ) : bool
	{
		return ( preg_match( $this->central::$WILDCARD_REGEX, $line ) );
	}

	public function sanitize_domain( string $domain ) : string
	{
	    $domain = strtolower( trim( sanitize_text_field( $domain ) ) );
	    $domain = preg_replace( $this->central::$SANITIZE_DOMAIN_REGEX, '', $domain );
	    return $domain;
	}

	public function validate_domain( string $domain ) : bool
	{
		return preg_match( $this->central::$DOMAIN_REGEX, $domain );
	}

	public function sanitize_and_validate_domain( string &$domain ) : bool
	{
	    $domain = strtolower( trim( sanitize_text_field( $domain ) ) );
	    $domain = preg_replace( $this->central::$SANITIZE_DOMAIN_REGEX, '', $domain );
	    return preg_match( $this->central::$DOMAIN_REGEX, $domain );
	}


	// ---- the difference to `sanitize_and_validate_domain` is that we allow `*` for wildcards within 
	public function sanitize_and_validate_domain_internally( string &$domain ) : bool
	{
	    $domain = strtolower( trim( sanitize_text_field( $domain ) ) );
	    $domain = preg_replace( $this->central::$SANITIZE_DOMAIN_INTERNAL_REGEX, '', $domain );
	    return preg_match( $this->central::$DOMAIN_INTERNAL_REGEX, $domain );
	}


	public function collapse_recipient_name( string &$recipient_name ) : void
	{
	    $this->collapsed_recipient_name = strtolower( $recipient_name );
	    $this->collapsed_recipient_name = preg_replace( $this->central::$COLLAPSE_RECIPIENT_NAME_REGEX, '', $recipient_name );
	}

	// ---- the difference to `sanitize_and_validate_recipient_name` is that we allow `*` for wildcards
	public function sanitize_and_validate_recipient_name_internally( string &$recipient_name ) : bool
	{
	    $recipient_name = strtolower( $recipient_name );
	    $recipient_name = preg_replace( $this->central::$SANITIZE_RECIPIENT_NAME_INTERNAL_REGEX, '', $recipient_name );
	    return preg_match( $this->central::$RECIPIENT_NAME_INTERNAL_REGEX, $recipient_name );
	}


	public function sanitize_and_validate_text( string &$text ) : bool
	{
		$text = trim( sanitize_text_field( $text ) );
		return true;
	}

	public function sanitize_and_validate_email_address( string &$email_address ) : bool
	{
	    $email_address = strtolower( sanitize_email( $email_address ) );
	    return preg_match( $this->central::$EMAIL_ADDRESS_REGEX, $email_address );
	}


	public function sanitize_and_validate_ip( string &$ip ) : bool
	{
	    $ip = preg_replace( $this->central::$SANITIZE_IP_REGEX, '', $ip );
	    return preg_match( $this->central::$IP_ADDRESS_REGEX, $ip );
	}


	public function is_email_recipient_name_catch_all() : bool
	{
		if( preg_match( $this->central::$RECIPIENT_NAME_CATCH_ALL_REGEX, $this->normalized_email_address ) )
		{
			$this->set_error_type( 'recipient_name_catch_all_email_address_error' );
			$this->is_email_address_inline_catch_all = true;
			return true;
		}
		return false;
	}


	public function is_recipient_name_on_list( array &$recipient_name_list, string $error_type = '', bool $use_collapsed_recipient_name = true ) : bool
	{
		if( empty( $recipient_name_list['recipient_names'] ) )
			return false;

		// if we look at the whitelists, we don't use the collapsed recipient name
		// we only do this, when we compare against blacklists
		if ( $use_collapsed_recipient_name )
			$recipient_name = $this->collapsed_recipient_name;
		else
			$recipient_name = $this->recipient_name;

		// First we look for the "cheap" way of figuring out if the recipient name is in
		// the array of known names
		if( in_array( $recipient_name, $recipient_name_list['recipient_names'] ) )
		{
			$this->set_error_type( $error_type );
			return true;

		}

		// if the "cheap" way failed, we go through the regexps. This is much more expensive
		else
		{
			foreach( $recipient_name_list['regexps'] as $id => $pattern )
			{
				if ( $use_collapsed_recipient_name )
					$pattern = '/^' . preg_replace($this->central::$WILDCARD_REGEX, $this->central::$RECIPIENT_NAME_BLACKLIST_WILDCARD_REPLACEMENT, $pattern ) . '$/';
				else
					$pattern = '/^' . preg_replace($this->central::$WILDCARD_REGEX, $this->central::$RECIPIENT_NAME_WHITELIST_WILDCARD_REPLACEMENT, $pattern ) . '$/';

				if( preg_match( $pattern, $recipient_name ) )
				{
					$this->set_error_type( $error_type );
					return true;
				}
			}
		}
		return false;
	}


	public function is_catch_all_domain() : bool
	{
		// creating a random
		$email_address = 'hfugyiohkjbhgymxcbiewkbe' . microtime(true) . '@' . $this->email_domain;
		if(! $this->simulate_sending_an_email( $email_address, $this->wp_email_domain ) )
			return false;
		$this->set_error_type( 'email_from_catch_all_domain' );
		return true;

	}


	public function set_error_type( string $error_type ) : void
	{
		$this->error_type = $error_type;
	}


// --------------- Private functions --------------------------------------------


	private function reset_class_attributes()
	{
		$this->collapsed_recipient_name                   = '';
		$this->email_address                 							= '';
		$this->email_domain                  							= '';
		$this->email_domain_has_MX_records   							= false;
		$this->email_domain_ip_address       							= '';
		$this->error_type                                 = '';
		$this->is_email_address_from_dea_service 					= false;
		$this->is_email_address_inline_catch_all          = false;
		$this->is_email_address_on_user_defined_blacklist = false;
		$this->is_email_address_on_user_defined_whitelist = false;
		$this->is_email_address_on_free_email_address_provider_list = false;
		$this->is_email_address_syntax_valid 							= false;
		$this->is_email_domain_on_user_defined_blacklist 	= false;
		$this->is_email_domain_on_user_defined_whitelist 	= false;
		$this->mx_server_domains 													= array();
		$this->mx_server_ips 															= array();
		$this->mx_server_preferences 											= array();
		$this->normalized_email_address 									= '';
	  $this->recipient_name                             = '';
		$this->simulated_sending_succeeded 								= false;
		$this->smtp_connection               							= '';
		$this->smtp_connection_is_open       							= false;
	}


	private function validate_current_email_address_syntax()
	{
		if( ! empty( $this->normalized_email_address ) && ! empty( $this->email_address ) )
		{
			if( $this->normalized_email_address != trim( strtolower( $this->email_address ) ) )
			{
				$this->set_error_type( 'email_address_contains_invalid_characters' );
				return false;
			}

			if( preg_match( $this->central::$EMAIL_ADDRESS_REGEX, $this->normalized_email_address ) ) 
			{
				$this->is_email_address_syntax_valid = true;
				return $this->extract_recipient_name_and_domain_from_email_address();
			}
		}

		$this->set_error_type( 'email_address_syntax_error' );
		return false;
	}


	private function normalize_email_address()
	{
	  $this->normalized_email_address = strtolower( sanitize_email( $this->email_address ) );
	}


	private function extract_recipient_name_and_domain_from_email_address() : bool
	{
		$arr = explode( '@', $this->normalized_email_address );
		$this->recipient_name = $arr[0];
		$this->collapse_recipient_name( $arr[0] );

		if( $this->sanitize_and_validate_domain( $arr[1] ) )
			$this->email_domain = $arr[1];

		if( ! empty( $this->email_domain ) )
			return true;

		$this->is_email_address_syntax_valid = false;
		return false;
	}


	private function get_email_domain_mx_servers() : bool
	{
		if( empty( $this->email_domain ) )
			return false;
		if( @getmxrr($this->email_domain, $this->mx_server_domains, $this->mx_server_preferences ) )
		{
			for( $i = 0; $i < sizeof( $this->mx_server_domains ); $i++ )
			{
				$ip = $this->get_host_ip_address( $this->mx_server_domains[$i] );
				if( ! empty($ip) )
					array_unshift( $this->mx_server_ips, $ip );
			}
			if( ! empty( $this->mx_server_ips ) )
				$this->email_domain_has_MX_records = true;
			else
				$this->set_error_type( 'email_domain_has_no_mx_record' );
		}
		return $this->email_domain_has_MX_records;
	}


	private function get_host_ip_address( string $hostname ) : string
	{
		$original_hostname = $hostname;
		if ( preg_match( $this->central::$IP_ADDRESS_REGEX, $hostname ) )
			$hostname = @gethostbyaddr ( $strEmailDomain );
		$host_ip = @gethostbyname ( $hostname );

		if (   preg_match( $this->central::$IP_ADDRESS_REGEX, $host_ip )
			  && $host_ip != $original_hostname )
			return $host_ip;
		return '';
	}


	private function cleanup_simulation_failure()
	{
		$this->smtp_connection_is_open = false;
		$this->close_smtp_connection();
	}


	private function get_smtp_connection() : bool
	{
		// if we don't have any resolvable mx servers, we return right away
		if( empty( $this->mx_server_ips ) )
			return false;
		for( $i = 0; $i < sizeof( $this->mx_server_ips ); $i++ )
		{
			if( $this->get_smtp_connection_to_host( $this->mx_server_ips[$i] ) )
				return true;
		}
		return false;
	}


	private function get_smtp_connection_to_host( string $hostname_or_ip )
	{
		$this->smtp_connection = @fsockopen ( $hostname_or_ip, 25, $errno, $errstr, self::$FSOCKOPEN_TIMEOUT);

		if( ! empty($this->smtp_connection) )
		{
			if( @stream_set_timeout( $this->smtp_connection, 1 ) )
				$this->smtp_connection_is_open = true;
			else
				$this->close_smtp_connection();
		}
		return $this->smtp_connection_is_open;
	}


	private function close_smtp_connection()
	{
		$this->smtp_connection_is_open && @fwrite ( $this->smtp_connection, "QUIT\n" );
		@fclose ( $this->smtp_connection );
		$this->smtp_connection_is_open = false;
	}


	private function detect_wp_email_domain()
	{
		$this->detected_wp_email_domain = preg_replace( "/:\d{2,5}$/", '', getenv( 'HTTP_HOST' ) );
		if( $this->detected_wp_email_domain = 'localhost' || $this->detected_wp_email_domain = '127.0.0.1' )
		{
			$this->detected_wp_email_domain = '';
			return;
		}
		$detected_wp_domain_parts = explode( '.', $this->detected_wp_email_domain );
		if( sizeof($detected_wp_domain_parts) > 1)
	  	$this->detected_wp_email_domain = $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 2 ] . '.' .  $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 1 ];
	  else
	  	$this->detected_wp_email_domain = '';
	}


	private function has_mx_records() : bool
	{
		if( empty( $this->mx_server_ips ) && ! $this->get_email_domain_mx_servers() )
		{
			$this->set_error_type( 'email_domain_has_no_mx_record' );
			return false;
		}
		return true;
	}


	private function is_email_domain_in_list( array &$list ) : bool
	{
		// the cheap solution first, we try to find a direct match
		if( in_array( $this->email_domain, $list['domains'] ) )
			return true;
		else
		{
			foreach( $list['regexps'] as $id => $pattern )
			{
				if( preg_match( $pattern, $this->email_domain ) )
					return true;
			}
		}
		return false;
	}


	private function is_in_wildcard_domains( array &$list ) : bool
	{
		foreach( $list as $id => $pattern )
		{
			if( ! strpos( $pattern, '*' ) )
				return false;
			else
			{
				$pattern = '/' . preg_replace( "/\*/", '[a-z0-9-]*', $pattern ) . '/';
				if( preg_match( $pattern, $this->email_domain ) )
					return true;
			}
		}
		return false;
	}

}
?>
