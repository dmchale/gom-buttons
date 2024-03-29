<?php
/**
 * Plugin Name: GoM Button Click Tracker
 * Plugin URI: http://www.gameonmarathon.com/
 * Description: Tracks the clicks of any anchor link on the website with the "track-me" class on it
 * Version: 1.3
 * Author: Dave McHale
 * Author URI: http://www.binarytemplar.com
 * Text Domain: gom-buttons
 * Domain Path: /languages
 * License: GPL2+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Make sure jquery is enqueued
wp_enqueue_script( 'jquery' );

// Plugin Update Checker
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/dmchale/gom-buttons/',
	__FILE__,
	'gom-buttons'
);

// Use the "release" method of checking for releases, so we don't get updates from the `master` branch
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


/**
 * The click handler will fire off an AJAX post to add tracking for the href that was clicked
 * It will not sit around waiting for a response, however. We just do a blind post that increments the value
 */
function gom_js_footer() {
	?>
	<script>
        jQuery( document ).ready(function() {
	        jQuery('.track-me a, track-me button, a.track-me, button.track-me').click( function(e) {
	            e.preventDefault();
                jQuery(this).fadeOut( 500 ).delay( 5000 ).fadeIn( 1000 );
	            jQuery.post('https://www.binarytemplar.com/wp-json/gom/v1/clicks-post/', { href : jQuery(this).attr('href') } );
            });
        });
	</script>
	<?php
}
add_action( 'wp_footer', 'gom_js_footer' );


/**
 * REST API endpoint handler
 *
 * @param WP_REST_Request $request
 *
 * @return string
 */
function gom_rest_get( WP_REST_Request $request ) {

    $str = get_option( 'gom_clicks_13' );
    return $str;

}


/**
 * Update the WP_Option value with the new href incremented
 *
 * @param WP_REST_Request $request
 */
function gom_rest_post( WP_REST_Request $request ) {

    // If we didn't get an href param, quit
    if ( ! $request['href'] )
        return;

    // Get or make array for data
	if ( get_option( 'gom_clicks_13' ) )
		$gom_clicks = get_option( 'gom_clicks_13' );
	else
		$gom_clicks = array();

	// Get value from params
    $key = sanitize_key( $request['href'] );

    // If value already exists in our data, increment count. Else create new key with value of 1
    if ( $gom_clicks[ $key ] ) {
	    $gom_clicks[ $key ] = $gom_clicks[ $key ] + 1;
    } else {
	    $gom_clicks[ $key ] = 1;
    }

    // Save new array of data to database
	update_option( 'gom_clicks_13', $gom_clicks );

}


/**
 * Register REST endpoints
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'gom/v1', '/clicks/', array(
		'methods' => 'GET',
		'callback' => 'gom_rest_get',
	) );

	register_rest_route( 'gom/v1', '/clicks-post/', array(
		'methods' => 'POST',
		'callback' => 'gom_rest_post',
	) );
} );