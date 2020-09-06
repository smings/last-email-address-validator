<?php
// Ensuring the existence of functions that are being used throughout 
// the plugin
// 
// Making sure we have a `write_log` function for debugging
if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

// making sure we have a `getmxrr` function on windows
if (! function_exists('getmxrr'))
{
    function getmxrr($hostName, &$mxHosts, &$mxPreference)
    {
        global $d;
        global $leav_options;
        
        $gateway = $leav_options['default_gateway'];
    
        $nsLookup = shell_exec("nslookup -q=mx {$hostName} {$gateway} 2>nul");
        preg_match_all("'^.*MX preference = (\d{1,10}), mail exchanger = (.*)$'simU", $nsLookup, $mxMatches);

        if ( count($mxMatches[2]) > 0 )
        {
            array_multisort($mxMatches[1], $mxMatches[2]);
            for ($i = 0; $i < count($mxMatches[2]); $i++) 
            {
                $mxHosts[$i] = $mxMatches[2][$i];
                $mxPreference[$i] = $mxMatches[1][$i];
            }
            return true;
        } 
        else
        {
            return false;
        }
    }
}


function leav_get_email_domain( $strEmailAddress )
{
  list( $local, $domain ) = explode( '@', $strEmailAddress, 2 );
  return $domain;
}

function leav_get_wp_email_domain()
{
  $WP_MAIL_DOMAIN = getenv( "HTTP_HOST" );
  echo( getenv( "HTTP_HOST" ) . "<br/>" );
  $CUT_OFF_PORT_REGEX = "/^(.*)(:\d{2,5})?$/";
  $MAIL_DOMAIN_REGEX = "/^.*([^\.]+)$/";

  preg_match( $CUT_OFF_PORT_REGEX, getenv( "HTTP_HOST" ), $match );
  $WP_MAIL_DOMAIN = $match[1];

  echo( $WP_MAIL_DOMAIN );
  // $WP_DOMAIN_PARTS = explode( ".", getenv( "HTTP_HOST" ) );
  // $WP_MAIL_DOMAIN = $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 2 ] . "." .  $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 1 ];
  return $WP_MAIL_DOMAIN;
}

if ( ! function_exists("write_log")) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}



?>