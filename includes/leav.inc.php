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


class LastEmailAddressValidator
{
	public  $wp_email_domain;
	public  $email_address;
	public  $normalized_email_address;
	public  $is_email_address_syntax_valid;
	public  $is_checked;
	public  $email_domain;
	public  $email_domain_ip_address;
	public  $email_domain_has_DNS_record;
	public  $email_domain_has_MX_records;
	public  $mx_server_domains;
	public  $mx_server_priorities;
	public  $mx_server_ips;
	public  $simulated_sending_succeeded;
	private $smtp_connection;
	private $smtp_connection_is_open;

	// timeouts in ms
	private static $SMTP_CONNECTION_TIMEOUT_SHORT = 1000;
	private static $SMTP_CONNECTION_TIMEOUT_LONG  = 3000;

	private static $WP_DOMAIN_PARTS = explode( '.', getenv( "HTTP_HOST" ) );
	private static $WP_MAIL_DOMAIN = $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 2 ] . '.' .  $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 1 ];


	// const DOMAIN_NAME_REGEX = '[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}';
	// const EMAIL_ADDRESS_NAME_PART_REGEX = 
	// 	'/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@$/i';

	// Courtesy of https://emailregex.com/	
	const EMAIL_ADDRESS_NAME_PART_REGEX_PATTERN = 
		"(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*";

	const DOMAIN_NAME_REGEX_PATTERN = 
		"(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))";

	const EMAIL_ADDRESS_REGEX = '/^' . self::EMAIL_ADDRESS_NAME_PART_REGEX_PATTERN . '@' . self::DOMAIN_NAME_REGEX_PATTERN . '$/iD'
	
	const IP_ADDRESS_REGEX = "/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/";




	public function __construct()
	{
		$this->reset_class_attributes();
	}


	public function __construct($email_address)
	{
		$this->__construct();
		$this->email_address = $email_address;
		$this->normalize_email_address();
		$this->validate_email_adress_syntax();
		# Only if we have a valid email address syntax, we extract the email domain
		$this->is_email_address_syntax_valid && $this->extract_domain_from_email_address();
	}


	private function reset_class_attributes()
	{
		$this->wp_email_domain               = '';
		$this->email_address                 = '';
		$this->normalized_email_address      = '';
		$this->is_email_address_syntax_valid = false;
		$this->is_checked                    = false;
		$this->email_domain                  = '';
		$this->email_domain_ip_address       = '';
		$this->email_domain_has_DNS_record   = false;
		$this->email_domain_has_MX_records   = false;
		$this->mx_server_domains             = array();
		$this->mx_server_ips                 = array();
		$this->simulated_sending_succeeded   = false;
		$this->smtp_connection               = '';
		$this->smtp_connection_is_open       = false;
	}


	public function set_wordpress_email_domain( $wp_email_domain )
	{
		$this->wp_email_domain = $wp_email_domain;
	}


	public function is_email_address_syntax_ok( string $email_address )
	{
		$this->__construct( $email_address );
		return $this->is_email_address_syntax_valid;
	}


	public function validate_email_address( string $email_address )
	{
		$this->__construct( $email_address );
		return $this->validate_email_address();
	}


	public function validate_email_address()
	{
		if( $this->is_checked or empty($this->email_address) )
			return $this->is_valid;

		$this->gather_server_data();
		if( $this->is_checked )
			return $this->is_valid;

		$this->simulate_sending_an_email();
		return $this->simulated_sending_succeeded;
	}


	private function gather_server_data()
	{
		$this->get_email_domain_ip_address();
		if( $this->is_checked )
			return;

		$this->get_mx_servers();
		if( $this->is_checked )
			return;		
	}


	private function normalize_email_address()
	{
	  $this->normalized_email_address = strtolower( sanitize_email( $this->email_address ) );
	}


	public function validate_email_adress_syntax()
	{
		if( preg_match( self::EMAIL_ADDRESS_REGEX, $this->normalized_email_address ) == 1 )
		{
			$this->is_email_address_syntax_valid = true;
		}
		else
			$this->is_checked = true;
	}


	private function extract_domain_from_email_address()
	{
		$this->email_domain = end( explode( "@", $this->normalized_email_address ) );
	}


	private function get_email_domain_ip_address()
	{
		$this->email_domain_ip_address = $this->get_host_ip_address( $this->email_domain );
		if( $this->email_domain_ip_address == '' )
			$this->is_checked = true;
		else
			$this->email_domain_has_DNS_record = true;
	}


	public function get_host_ip_address( $hostname )
	{
		if ( preg_match( self::IP_ADDRESS_REGEX, $hostname ) )
			$hostname = @gethostbyaddr ( $strEmailDomain );
		$host_ip = @gethostbyname ( $hostname );

		if ( preg_match( self::IP_ADDRESS_REGEX, $host_ip ) )
			return $host_ip;
		else
			return '';
	}


	private function get_mx_servers()
	{
		if( @getmxrr($this->email_domain, $this->mx_server_domains) )
		{
			for( $i = 0; $i < sizeof( $this->mx_server_domains ); $i++ )
			{
				$ip = $this->get_host_ip_address( $this->mx_server_domains[$i] );
				if( $ip != '' )
					array_unshift( $this->mx_server_ips, $ip );
			}
			// If no MX server name can be resolved into an IP address
			// we have to stop validate too
			if( empty( $this->mx_server_ips ) )
				$this->is_checked = true;
			else
				$this->email_domain_has_MX_records = true;
		}

		// If we couldn't resolve any MX servers for the domain
		else
		{
			$this->is_checked = true;
		}
	}


	private function simulate_sending_an_email()
	{
		global $WP_MAIL_DOMAIN;

		if( ! $this->get_smtp_connection() )
		{
			$is_checked = true;
			return;
		}

		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_LONG );

		if( substr( $answer, 0, 3 ) != "220" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return;
		}

		$wp_email_domain = $this->wp_email_domain;
		if( empty($wp_email_domain) )
			$wp_email_domain = $WP_MAIL_DOMAIN;


		@fwrite ( $this->smtp_connection, "HELO " . $wp_email_domain . "\n" );
		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_LONG );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return;
		}

		@fwrite ( $this->smtp_connection, "MAIL FROM: <no-reply@" . $wp_email_domain . ">\n" );
		$answer = @fgets( $this->smtp_connection, $this->SMTP_CONNECTION_TIMEOUT_SHORT );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return;
		}

		@fwrite ( $this->smtp_connection, "RCPT TO: <" . $this->normalized_email_address . ">\n" );
		if( substr( $answer, 0, 3 ) != "250" ) // no answer or rejected
		{
			$this->cleanup_simulation_failure();
			return;
		}
		$this->close_smtp_connection();
		$this->simulated_sending_succeeded = true;
		$this->is_checked = true;
	}


	private function cleanup_simulation_failure()
	{
		$this->smtp_connection_is_open = false;
		$this->close_smtp_connection();
		$this->is_checked = true;
	}

	private function get_smtp_connection()
	{
		// if we don't have any resolvable mx servers, we return right away
		if( empty( $this->mx_server_ips ) )
			return false;
		for( $i = 0; $i < sizeof( $this->mx_server_ips ); $i++ )
		{
			$this->smtp_connection = @fsockopen ( $this->mx_server_ips[ $i ], 25, $errno, $errstr, $this->SMTP_CONNECTION_TIMEOUT_SHORT );
			if( ! empty($this->smtp_connection) )
			{
				if( @stream_set_timeout(  $this->smtp_connection, 1  )
				{
					$this->smtp_connection_is_open = true;
					return true;
				}
				else
					$this->close_smtp_connection();
			}
		}
		return false;
	}


	private function close_smtp_connection()
	{
		$this->smtp_connection_is_open && @fwrite ( $this->smtp_connection, "QUIT\n" );
		@fclose ( $this->smtp_connection );
		$this->smtp_connection_is_open = false;
	}



	function leav_check_mx_servers_and_simulate_sending_to_email_address( $strEmailDomain, $strEmailAddress )
	{
		$arrMXHosts = $this -> leav_get_mx_host_List( $strEmailDomain );

		if( sizeof( $arrMXHosts ) == 0 )
		{
			return EMAIL_DOMAIN_HAS_NO_MX_RECORDS;
		}

		// iterate through the mail-server ( MX ) -names and send an request
		// to check, if given email-Adress exists
		// if the establishing of a connection failed, try the next one
		for ( $i=0; $i < sizeof( $arrMXHosts ); $i++ )
		{
			$fpMailServer = $this -> leav_connect_to_mx_server( $arrMXHosts[ $i ] );

			if( $fpMailServer == SMTP_CONNECTION_ATTEMPTS_TIMED_OUT )	// connection failed
			{
				$blnConnect = FALSE;
			}
			else	// successful connected
			{
				$blnConnect = TRUE;
				$numEmailExists = $this -> leav_simulate_sending_of_email( $fpMailServer, $strEmailAddress );
				if( $numEmailExists != SMTP_CONNECTION_ATTEMPTS_TIMED_OUT && $numEmailExists != SMTP_CONNECTION_REJECTED )
				break;
			}
		}

		if ( !$blnConnect )	// connection to smtp-service failed
		{
			return EMAIL_ADDRESS_SYNTAX_CORRECT_BUT_CONNECTION_FAILED;
		}
		else
		{
			return $numEmailExists;
		}
	}


	function leav_get_mx_host_List( &$strEmailDomain )
	{
		$arrMXHosts = array( );
		$arrHostsWeight = array( );
		$blnHasMXHosts = @getmxrr ( $strEmailDomain, $arrMXHosts, $arrHostsWeight );
		if( sizeof( $arrMXHosts ) > 1 )
		{
			$this -> leav_sort_by_key( $arrMXHosts, $arrHostsWeight );
		}
		return $arrMXHosts;
	}


	// Ask the smtp-server, if email address exists
	function leav_simulate_sending_of_email( $fpMailServer, $strMailRecipient )
	{
		global $leav_options;

		// check, if server is ready to accept SMTP commands ( Return-Code: 220 )
		$strAnswer = @fgets( $fpMailServer, SMTP_CONNECTION_TIMEOUT_LONG );
		if( strlen( $strAnswer ) == 0 ) // no answer
		{
			$this -> leav_close_connection_with_mx_server( $fpMailServer, FALSE );
			return SMTP_CONNECTION_ATTEMPTS_TIMED_OUT;
		}
		else if( !preg_match( "/^220/", $strAnswer ) ) // request rejected
		{
			$this -> leav_close_connection_with_mx_server( $fpMailServer, FALSE );
			return SMTP_CONNECTION_REJECTED;
		}

		// say hi ( Return-Code: 250 )
		@fwrite ( $fpMailServer, "HELO " . $leav_options['wp_mail_domain'] . "\n" );
		$strAnswer = @fgets( $fpMailServer, SMTP_CONNECTION_TIMEOUT_LONG );
		
		if( !preg_match( "/^250/", $strAnswer ) ) // request rejected ( bad client-host?? )
		{
			$this -> leav_close_connection_with_mx_server( $fpMailServer );
			return SMTP_CONNECTION_REJECTED;
		}

		// tell the server, who wants to send the mail ( Return-Code: 250 )
		@fwrite ( $fpMailServer, "MAIL FROM: <no-reply@" . $leav_options['wp_mail_domain'] . ">\n" );

		$strAnswer = @fgets( $fpMailServer, SMTP_CONNECTION_TIMEOUT_SHORT );

		if( !preg_match( "/^250/", $strAnswer ) ) // SMTP_CONNECTION_REJECTED
		{
			$this -> leav_close_connection_with_mx_server( $fpMailServer );
			return SMTP_CONNECTION_REJECTED;
		}

		// tell the server, who is the mail-recipient ( Return-Code: 250 )
		// if the recipient is unknown, the mail-address is invalid
		@fwrite ( $fpMailServer, "RCPT TO: <" . $strMailRecipient . ">\n" );

		$strAnswer = @fgets( $fpMailServer, SMTP_CONNECTION_TIMEOUT_SHORT );
       
    if( !preg_match( "/^250/", $strAnswer ) ) // recipient unknown
		{
			$this -> leav_close_connection_with_mx_server( $fpMailServer );
			return RECIPIENT_EMAIL_REJECTED_BY_MX_SERVER;
		}

		// say goodbye
		$this -> leav_close_connection_with_mx_server( $fpMailServer );

		// no error occured, so the mail-address is valid
		return VALID_EMAIL_ADDRESS;
	}


	// Opens a socket connection to smtp server
	// try max. 5 times, if connection failed, return SMTP_CONNECTION_ATTEMPTS_TIMED_OUT
	// 
	function leav_connect_to_mx_server( $strMXHost )
	{
		for ( $i = 0; $i < 5; $i++ )
		{
			// open an socket-connection at tcp-port 25 ( default mail-port )
			$fpMailServer = @fsockopen ( $strMXHost, 25, $errno, $errstr, 100 );	// $errno and $errstr currently not used

			if( $fpMailServer )
			{
				// stream should be closed after 1 second, PHP >= PHP 4.3
				$strPHPVersion = phpversion( );
				if( preg_match( "/^(4\.[3-9])/", $strPHPVersion ) || $strPHPVersion[ 0 ] == '5' )
				{
					@stream_set_timeout(  $fpMailServer, 1  );
				}
				return $fpMailServer;	// successful connected
			}
		}
		return SMTP_CONNECTION_ATTEMPTS_TIMED_OUT;	// connection failed
	}


	// Closes an open socket-connection
	function leav_close_connection_with_mx_server( $fpConnection, $bStarted = TRUE )
	{
		if( $bStarted )
		{
			@fwrite ( $fpConnection, "QUIT\n" );
		}
		@fclose ( $fpConnection );
	}


	// bubblesort function
	// sorts first the key-array in ascending order
	// and then the array given with the first parameter
	// in the order of the key-array
	function leav_sort_by_key( &$objArray, &$arrKey )
	{
		$numEnd = sizeof( $objArray ) -1;
		$numEnd = sizeof( $objArray ) -1;
		for ( $i = 1; $i <= $numEnd; $i++ )
		{
			for ( $j = $numEnd; $j >= $i; $j-- )
			{
				if ( $arrKey[ $j - 1 ] > $arrKey[ $j ] )
				{
					$numBuffer[ 0 ]     = $arrKey[ $j ];
					$numBuffer[ 1 ]     = $objArray[ $j ];
					$arrKey[ $j ]       = $arrKey[ $j - 1 ];
					$objArray[ $j ]     = $objArray[ $j - 1 ];
					$arrKey[ $j - 1 ]   = $numBuffer[ 0 ];
					$objArray[ $j - 1 ] = $numBuffer[ 1 ];
				}
			}
		}
	}

}
?>
