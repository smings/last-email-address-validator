<?php
// Ensuring the existence of functions that are being used throughout 
// the plugin
// 
// Making sure we have a `write_log` function for debugging

defined('ABSPATH') or die('Not today!');

if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

?>