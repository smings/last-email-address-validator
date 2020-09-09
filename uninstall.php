<?php
/**
 * This file contains all procedures to safely remove 
 * LEAV - Last Email Address Validator 
 * and all the data it stored on your WordPress instance
 */


// making sure no one other than WordPress calls this directly
defined('WP_UNINSTALL_PLUGIN') or die("Nice try! Go away!");

// safely deleting all saved LEAV options
delete_option("leav_options");

?>