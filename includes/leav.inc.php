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


// return status values
define ( "RECIPIENT_EMAIL_REJECTED_BY_MX_SERVER",              -10 );
define ( "SERVER_HAS_NO_DNS_ENTRY",                            -20 );
define ( "SERVER_HAS_NO_DNS_MX_RECORDS",                       -15 );
define ( "EMAIL_ADDRESS_SYNTAX_INCORRECT",		                 -30 );
define ( "SMTP_CONNECTION_ATTEMPTS_TIMED_OUT",		             -40 );
define ( "SMTP_CONNECTION_REJECTED",	                         -50 );
define ( "VALID_EMAIL_ADDRESS",				                          10 );
define ( "EMAIL_ADDRESS_SYNTAX_CORRECT_BUT_CONNECTION_FAILED",  20 );


// timeouts in ms
define ( "SMTP_CONNECTION_TIMEOUT_SHORT",	                    1500 );
define ( "SMTP_CONNECTION_TIMEOUT_LONG",	                    3000 );	

class LastEmailAddressValidator
{

	function leav_normalize_email_address( $strEmailAddress )
	{
		$strEmailAddress = ststrtolower($strEmailAddress)
		$strEmailAddress = sanitize_email($strEmailAddress);
	}

	function leav_get_email_domain( $strEmailAddress )
	{
		list( $local, $domain ) = explode( '@', $strEmailAddress, 2 );
		return $domain;
	}

	function leav_validate_email_address( $strEmailAddress )
	{
		$strEmailAddress = this->leav_normalize_email_address( $strEmailAddress );
		if( !$this -> leav_check_email_adress_syntax( $strEmailAddress ) )
		{
			return EMAIL_ADDRESS_SYNTAX_INCORRECT;
		}
		$strEmailDomain = $this -> leav_extract_hostname( $strEmailAddress );
		if( !$this -> leav_resolve_host_ip_address( $strEmailDomain ) )
		{
			return SERVER_HAS_NO_DNS_ENTRY;
		}
		return $this -> leav_check_mx_servers_and_simulate_sending_to_email_address( $strEmailDomain, $strEmailAddress );
	}


	function leav_check_email_adress_syntax( &$strEmailAddress )
	{
		return preg_match( "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*\\.[a-z]{2,18}$/i", $strEmailAddress  ) == 1;
	}


	function leav_check_field_name_for_email( &$strFieldName )
	{
	    return preg_match( "/^.*e[ -_~<>\.,\|=\+()\*!#\$%\^]{0,2}mail.*$/i", $strFieldName  ) == 1;
	}


	function leav_extract_hostname( &$strEmailAddress )
	{
		$arrElements = explode( "@", $strEmailAddress );
		return $arrElements[ 1 ];
	}


	function leav_resolve_host_ip_address( &$strEmailDomain )
	{
		# If we only have an IP-address, we check if it resolves into a DNS name
		if ( preg_match( "/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $strEmailDomain ) )
		{
			$strEmailDomain = @gethostbyaddr ( $strEmailDomain );
		}

		# now we get the host IP address to the passed (or resolved) hostname
		$strHostIP = @gethostbyname ( $strEmailDomain );

		# only if it truly resolved into an IP address, we can return true
		if ( preg_match( "/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $strHostIP ) )
		{
			return true;
		}

		# in this case we couldn't resolve an IP address
		return false;
	}


	function leav_check_mx_servers_and_simulate_sending_to_email_address( $strEmailDomain, $strEmailAddress )
	{
		$arrMXHosts = $this -> leav_get_mx_host_List( $strEmailDomain );

		if( sizeof( $arrMXHosts ) == 0 )
		{
			return SERVER_HAS_NO_DNS_MX_RECORDS;
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
