<?php

// Example of how to get the current translation strings from the 
// WordPress plugin code. 
// `xgettext --default-domain=leav --language=PHP --keyword=__ --keyword=_e --sort-by-file --copyright-holder="Dirk Tornow" --package-name=leav --package-version=1.0.3 --from-code=UTF-8 --msgid-bugs-address="translastions@smings.com" -i -p languages/ *.php`

// Example of how to merge a newer version with an existing po file
// `msgmerge -i -o new_merged.po last-email-address-validator-de_DE.po leav.po`

// Example of how to create an mo file
// `msgfmt -o last-email-address-validator-de_DE.mo last-email-address-validator-de_DE.po`

// Example for bash one-liner for finding domains with MX records 
// for domain in `cat disposable_email_service_provider_domain_list.txt`; do dig @8.8.8.8 MX $domain +short > /dev/null && echo $domain >> results.txt; done

defined('ABSPATH') or die('Not today!');


class LastEmailAddressValidator
{
	public  $debug = false;
	private $email_address = '';
	private $email_domain = '';
	private $email_domain_ip_address = '';
	public  $email_domain_has_MX_records = false;
	private $detected_wp_email_domain = '';
	public  $is_email_address_syntax_valid = false;
	public  $is_email_domain_on_user_defined_blacklist = false;
	public  $is_email_address_on_user_defined_blacklist = false;
	public  $is_email_domain_on_user_defined_whitelist = false;
	public  $is_email_address_on_user_defined_whitelist = false;
	private $mx_server_domains = array();
	private $mx_server_preferences = array();
	private $mx_server_ips = array();
	private $normalized_email_address = '';
	public  $simulated_sending_succeeded = false;
	private $smtp_connection;
	private $smtp_connection_is_open = false;
	private $wp_email_domain = '';

	// timeouts in ms
	private static $SMTP_CONNECTION_TIMEOUT_SHORT = 1000;
	private static $SMTP_CONNECTION_TIMEOUT_LONG  = 3000;

	// timeout in s
	private static $FSOCKOPEN_TIMEOUT = 2;

	private static $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}$/i";
	
	private static $IP_ADDRESS_REGEX = "/^(?:[0-9]{1,3}\\.){3}[0-9]{1,3}$/";


// --------------- Public functions --------------------------------------------


	public function __construct( string $email_address = "", string $wp_email_domain = "" )
	{
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


	public function set_wordpress_email_domain( string $wp_email_domain )
	{
		$this->wp_email_domain = $wp_email_domain;
	}


	public function validate_email_address_syntax( string $email_address )
	{
		$this->__construct( $email_address, $this->wp_email_domain );
		return $this->is_email_address_syntax_valid;
	}


	public function check_if_email_is_on_user_defined_blacklist( array &$list ) : bool
	{
		if( $this->check_if_string_is_on_list( $this->email_domain, $list ) )
		{
			$this->is_email_domain_on_user_defined_blacklist = true;
			return true;
		}
		return false;
	}


	public function check_if_email_address_is_on_user_defined_blacklist( array &$list ) : bool
	{
		if( $this->check_if_string_is_on_list( $this->normalized_email_address, $list ) )
		{
			$this->is_email_address_on_user_defined_blacklist = true;
			return true;
		}
		return false;
	}


	public function check_if_email_is_on_user_defined_whitelist( array &$list ) : bool
	{
		if( $this->check_if_string_is_on_list( $this->email_domain, $list ) )
		{
			$this->is_email_domain_on_user_defined_whitelist = true;
			return true;
		}
		return false;
	}


	public function check_if_email_address_is_on_user_defined_whitelist( array &$list ) : bool
	{
		if( $this->check_if_string_is_on_list( $this->normalized_email_address, $list ) )
		{
			$this->is_email_address_on_user_defined_whitelist = true;
			return true;
		}
		return false;
	}


	public function simulate_sending_an_email( string $email_address = "", string $wp_email_domain = "" )
	{

		if( ! empty($wp_email_domain) )
			$sender_email_domain = $wp_email_domain;
		elseif( ! empty($this->wp_email_domain) )
			$sender_email_domain = $this->wp_email_domain;
		else
			$sender_email_domain = $this->detected_wp_mail_domain;

		if( empty($this->normalized_email_address) )
			return false;

		if( ! $this->is_email_address_syntax_valid && ! $this->validate_current_email_address_syntax() )
			return false;

		if( empty($mx_server_ips) && ! $this->get_email_domain_mx_servers() )
			 return false;

		if( ! $this->get_smtp_connection() )
			return false;

		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != "220" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, "HELO " . $sender_email_domain . "\n" );
		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, "MAIL FROM: <no-reply@" . $sender_email_domain . ">\n" );
		$answer = @fgets( $this->smtp_connection, self::$SMTP_CONNECTION_TIMEOUT_SHORT );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, "RCPT TO: <" . $this->normalized_email_address . ">\n" );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
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


	public function set_debug( boolean $debug )
	{
		$this->debug = $debug;
	}

// --------------- Private functions --------------------------------------------


	private function reset_class_attributes()
	{
		$this->email_address                 = "";
		$this->email_domain                  = "";
		$this->email_domain_has_MX_records   = false;
		$this->email_domain_ip_address       = "";
		$this->is_email_address_syntax_valid = false;
		$this->mx_server_domains             = array();
		$this->mx_server_ips                 = array();
		$this->normalized_email_address      = "";
		$this->simulated_sending_succeeded   = false;
		$this->smtp_connection               = "";
		$this->smtp_connection_is_open       = false;
	}


	private function validate_current_email_address_syntax()
	{
		if( empty($this->normalized_email_address) )
			return false;
		elseif( preg_match( self::$EMAIL_ADDRESS_REGEX, $this->normalized_email_address )  )
		{
			$this->is_email_address_syntax_valid = true;
			$this->extract_domain_from_email_address();
		}
		return $this->is_email_address_syntax_valid;
	}


	private function normalize_email_address()
	{
	  $this->normalized_email_address = strtolower( sanitize_email( $this->email_address ) );
	}


	private function extract_domain_from_email_address()
	{
		$arr = explode( "@", $this->normalized_email_address );
		$this->email_domain = end($arr);
	}


	private function get_email_domain_mx_servers()
	{
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
		}
		return $this->email_domain_has_MX_records;
	}


	private function get_host_ip_address( string $hostname ) : string
	{
		$original_hostname = $hostname;
		if ( preg_match( self::$IP_ADDRESS_REGEX, $hostname ) )
			$hostname = @gethostbyaddr ( $strEmailDomain );
		$host_ip = @gethostbyname ( $hostname );

		if (   preg_match( self::$IP_ADDRESS_REGEX, $host_ip ) 
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
		$this->detected_wp_email_domain = preg_replace( "/:\d{2,5}$/", '', getenv( "HTTP_HOST" ) );
		if( $this->detected_wp_email_domain = 'localhost' || $this->detected_wp_email_domain = '127.0.0.1' )
		{
			$this->detected_wp_email_domain = '';
			return;
		}
		$detected_wp_domain_parts = explode( ".", $this->detected_wp_email_domain );
		if( sizeof($detected_wp_domain_parts) > 1)
	  	$this->detected_wp_email_domain = $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 2 ] . "." .  $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 1 ];
	  else
	  	$this->detected_wp_email_domain = '';
	}


	private function check_if_string_is_on_list( string &$string, array &$list ) : bool
	{
		foreach( $list as $line )
		{
			if( $line == $string )
				return true;
		}
		return false;
	}

}
?>
