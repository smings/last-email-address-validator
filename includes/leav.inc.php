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


if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}


class LastEmailAddressValidator
{
	public  $debug;
	private $email_address;
	private $email_domain;
	private $email_domain_ip_address;
	public  $email_domain_has_MX_records;
	private $detected_wp_mail_domain;
	public  $is_email_address_syntax_valid;
	private $mx_server_domains;
	private $mx_server_ips;
	private $normalized_email_address;
	public  $simulated_sending_succeeded;
	private $smtp_connection;
	private $smtp_connection_is_open;
	private $wp_email_domain;

	// timeouts in ms
	private static $SMTP_CONNECTION_TIMEOUT_SHORT = 1000;
	private static $SMTP_CONNECTION_TIMEOUT_LONG  = 3000;




	// const DOMAIN_NAME_REGEX = '[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}';
	// const EMAIL_ADDRESS_NAME_PART_REGEX = 
	// 	'/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@$/i';

	private $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}$/i";

	// Courtesy of https://emailregex.com/	
	// private $EMAIL_ADDRESS_REGEX = "/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD";
	
	private $IP_ADDRESS_REGEX = "/^(?:[0-9]{1,3}\\.){3}[0-9]{1,3}$/";


// --------------- Public functions --------------------------------------------


	public function __construct( string $email_address = '', string $wp_email_domain = '' )
	{
		$this->reset_class_attributes();
		$detected_wp_domain_parts = explode( '.', getenv( "HTTP_HOST" ) );
	  $this->detected_wp_mail_domain = $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 2 ] . '.' .  $detected_wp_domain_parts[ count($detected_wp_domain_parts) - 1 ];

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
			$this->wp_email_domain = $this->detected_wp_mail_domain;
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


	public function simulate_sending_an_email( string $email_address = '', string $wp_email_domain = '' )
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

		if( empty($mx_server_ips) && ! $this->gather_server_data() )
			 return false;

		if( ! $this->get_smtp_connection() )
			return false;

		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_LONG );

		if( substr( $answer, 0, 3 ) != "220" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, "HELO " . $sender_email_domain . "\n" );
		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return false;
		}

		@fwrite ( $this->smtp_connection, "MAIL FROM: <no-reply@" . $sender_email_domain . ">\n" );
		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_SHORT );
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


	public function set_debug( boolean $debug )
	{
		$this->debug = $debug;
	}

// --------------- Private functions --------------------------------------------


	private function reset_class_attributes()
	{
		$this->email_address                 = '';
		$this->email_domain                  = '';
		$this->email_domain_has_MX_records   = false;
		$this->email_domain_ip_address       = '';
		$this->is_email_address_syntax_valid = false;
		$this->mx_server_domains             = array();
		$this->mx_server_ips                 = array();
		$this->normalized_email_address      = '';
		$this->simulated_sending_succeeded   = false;
		$this->smtp_connection               = '';
		$this->smtp_connection_is_open       = false;
	}


	private function validate_current_email_address_syntax()
	{
		if( empty($this->normalized_email_address) )
			return false;
		elseif( preg_match( $this->EMAIL_ADDRESS_REGEX, $this->normalized_email_address ) == 1 )
		{
			$this->is_email_address_syntax_valid = true;
			$this->extract_domain_from_email_address();
		}
		return $this->is_email_address_syntax_valid;
	}


	private function gather_server_data()
	{
		if( ! $this->get_email_domain_mx_servers() )
			return false;
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
		if( @getmxrr($this->email_domain, $this->mx_server_domains) )
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


	private function get_host_ip_address( string $hostname )
	{
		$original_hostname = $hostname;
		if ( preg_match( $this->IP_ADDRESS_REGEX, $hostname ) )
			$hostname = @gethostbyaddr ( $strEmailDomain );
		$host_ip = @gethostbyname ( $hostname );

		if (   preg_match( $this->IP_ADDRESS_REGEX, $host_ip ) 
			  && $host_ip != $original_hostname )
			return $host_ip;
		else
			return '';
	}


	private function cleanup_simulation_failure()
	{
		$this->smtp_connection_is_open = false;
		$this->close_smtp_connection();
	}


	private function get_smtp_connection()
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
		$this->smtp_connection = @fsockopen ( $hostname_or_ip, 25, $errno, $errstr, $this->SMTP_CONNECTION_TIMEOUT_SHORT );
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


}
?>
