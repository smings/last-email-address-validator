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


function leav_check_field_name_for_email( &$strFieldName )
{
    return preg_match( "/^.*e[ -_~<>\.,\|=\+()\*!#\$%\^]{0,2}mail.*$/i", $strFieldName  ) == 1;
}



?>