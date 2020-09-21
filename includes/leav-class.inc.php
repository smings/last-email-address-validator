<?php

defined('ABSPATH') or die('Not today!');

require_once("leav-central.inc.php");

class LastEmailAddressValidator
{
	private $central;
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
	public  $is_email_address_on_user_defined_blacklist = false;
	public  $is_email_address_on_user_defined_whitelist = false;
	public  $is_email_address_syntax_valid = false;
	public  $is_email_domain_on_user_defined_blacklist = false;
	public  $is_email_domain_on_user_defined_whitelist = false;
	public  $mx_server_domains = array();
	public  $mx_server_ips = array();
	public  $mx_server_preferences = array();
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
				$this->extract_domain_from_email_address();
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
		$this->extract_domain_from_email_address();
		return true;
	}


	public function set_wordpress_email_domain( string $wp_email_domain )
	{
		$this->wp_email_domain = $wp_email_domain;
	}


	public function validate_email_address_syntax( string &$email_address ) : bool
	{
		$this->reuse( $email_address );
		return $this->is_email_address_syntax_valid;
	}


	public function check_if_email_domain_is_on_user_defined_whitelist( array &$list ) : bool
	{
		if( in_array( $this->email_domain, $list ) )
			$this->is_email_domain_on_user_defined_whitelist = true;
		return $this->is_email_domain_on_user_defined_whitelist;
	}


	public function check_if_email_address_is_on_user_defined_whitelist( array &$list ) : bool
	{
		if( in_array( $this->normalized_email_address, $list ) )
			$this->is_email_address_on_user_defined_whitelist = true;
		return $this->is_email_address_on_user_defined_whitelist;
	}


	public function check_if_email_domain_is_on_user_defined_blacklist( array &$list ) : bool
	{
		if( in_array( $this->email_domain, $list ) )
		{
			$this->is_email_domain_on_user_defined_blacklist = true;
			$this->error_type = 'email_domain_is_blacklisted';
		}
		return $this->is_email_domain_on_user_defined_blacklist;
	}


	public function check_if_email_address_is_on_user_defined_blacklist( array &$list ) : bool
	{
		if( in_array( $this->normalized_email_address, $list ) )
		{
			$this->is_email_address_on_user_defined_blacklist = true;
			$this->error_type = 'email_address_is_blacklisted';
		}
		return $this->is_email_address_on_user_defined_blacklist;
	}


	public function check_if_email_address_is_from_dea_service( array &$domain_list, array &$mx_domain_list, array &$mx_ip_list ) : bool
	{
		if( ! $this->do_basic_email_checks() )
			return false;

		if(    in_array( $this->email_domain, $domain_list ) 
			  || ! empty( array_intersect( $this->mx_server_domains, $mx_domain_list ) )
			  || ! empty( array_intersect( $this->mx_server_ips, $mx_ip_list ) )
		)
		{
			$this->is_email_address_from_dea_service = true;
			$this->error_type = 'email_domain_on_dea_blacklist';
		}

		return $this->is_email_address_from_dea_service;
	}


	public function simulate_sending_an_email( string $email_address = '', string $wp_email_domain = '' )
	{
		$this->error_type = 'simulated_sending_of_email_failed';

		if( ! empty($wp_email_domain) )
			$sender_email_domain = $wp_email_domain;
		elseif( ! empty($this->wp_email_domain) )
			$sender_email_domain = $this->wp_email_domain;
		else
			$sender_email_domain = $this->detected_wp_email_domain;

		if( ! $this->do_basic_email_checks() )
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
		$this->error_type = '';
		return true;
	}


	public function get_detected_wp_email_domain()
	{
		return $this->detected_wp_email_domain;
	}


	public function sanitize_and_validate_domain( string &$domain ) : bool
	{
	    $domain = strtolower( $domain );
	    $domain = preg_replace( $this->central::$SANITIZE_DOMAIN_REGEX, '', $domain );
	    return preg_match( $this->central::$DOMAIN_REGEX, $domain );
	}


	public function sanitize_and_validate_text( string &$text ) : bool
	{
		$text = _sanitize_text_fields( $text );
		$text = preg_replace( '/^\s*/', '',  $text );
		$text = preg_replace( '/\s*$/', '',  $text );
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
			$this->error_type = 'inline_catch_all_email_address_error';
			$this->is_email_address_inline_catch_all = true;
			return true;
		}
		return false;
	}


// --------------- Private functions --------------------------------------------


	private function reset_class_attributes()
	{
		$this->email_address                 							= '';
		$this->email_domain                  							= '';
		$this->email_domain_has_MX_records   							= false;
		$this->email_domain_ip_address       							= '';
		$this->error_type                                 = '';
		$this->is_email_address_from_dea_service 					= false;
		$this->is_email_address_inline_catch_all          = false;
		$this->is_email_address_on_user_defined_blacklist = false;
		$this->is_email_address_on_user_defined_whitelist = false;
		$this->is_email_address_syntax_valid 							= false;
		$this->is_email_address_syntax_valid 							= false;
		$this->is_email_domain_on_user_defined_blacklist 	= false;
		$this->is_email_domain_on_user_defined_whitelist 	= false;
		$this->mx_server_domains             							= array();
		$this->mx_server_domains 													= array();
		$this->mx_server_ips                 							= array();
		$this->mx_server_ips 															= array();
		$this->mx_server_preferences 											= array();
		$this->normalized_email_address      							= '';
		$this->normalized_email_address 									= '';
		$this->simulated_sending_succeeded   							= false;
		$this->simulated_sending_succeeded 								= false;
		$this->smtp_connection               							= '';
		$this->smtp_connection 														= '';
		$this->smtp_connection_is_open       							= false;
		$this->smtp_connection_is_open 										= false;
	}


	private function validate_current_email_address_syntax()
	{
		if( empty($this->normalized_email_address) )
		{
			$this->error_type = 'email_address_syntax_error';
			return false;
		}
		elseif( preg_match( $this->central::$EMAIL_ADDRESS_REGEX, $this->normalized_email_address )  )
		{
			$this->is_email_address_syntax_valid = true;
			$this->extract_domain_from_email_address();
			return true;
		}
		$this->error_type = 'email_address_syntax_error';
		return false;
	}


	private function normalize_email_address()
	{
	  $this->normalized_email_address = strtolower( sanitize_email( $this->email_address ) );
	}


	private function extract_domain_from_email_address() : bool
	{
		if( empty( $this->normalized_email_address ) )
			return false;
		$arr = explode( '@', $this->normalized_email_address );
		if( sizeof($arr) < 2 )
			return false;
		$this->email_domain = end($arr);
		return true;
	}


	private function get_email_domain_mx_servers() : bool
	{
		if( empty( $this->email_domain ) && ! $this->extract_domain_from_email_address() )
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
				$this->error_type = 'email_domain_has_no_mx_record';
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


	private function do_basic_email_checks() : bool
	{

		if( empty($this->normalized_email_address) )
			return false;

		if( ! $this->is_email_address_syntax_valid && ! $this->validate_current_email_address_syntax() )
			return false;

		if( empty($mx_server_ips) && ! $this->get_email_domain_mx_servers() )
		{
			$this->error_type = 'email_domain_has_no_mx_record';
			return false;
		}

		return true;
	}

}
?>
