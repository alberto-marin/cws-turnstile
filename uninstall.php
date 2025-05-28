<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete plugin options.
delete_option( 'cws_turnstile_site_key' );
delete_option( 'cws_turnstile_secret_key' );
