<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Get the plugins removed from repo from our local file
 *
 * @return array The plugins removed from the repo
 * @since 1.0
 **/
function secupress_get_removed_plugins() {
	static $removed_plugins;

	if ( isset( $removed_plugins ) ) {
		return $removed_plugins;
	}

	if ( false !== ( $from_transient = get_site_transient( __FUNCTION__ ) ) ) {
		return $from_transient;
	}

	$plugins_list_file = SECUPRESS_INC_PATH . 'data/no-longer-in-directory-plugin-list.data';
	if ( is_readable( $plugins_list_file ) ) {
		$removed_plugins = array_flip( array_map( 'trim', file( $plugins_list_file ) ) );
	}
	$all_plugins = array_keys( get_plugins() );
	$all_plugins = array_map( 'dirname', $all_plugins );
	$all_plugins = array_flip( $all_plugins );
	$all_plugins = array_intersect_key( $all_plugins, $removed_plugins );

	$removed_plugins = $all_plugins;
	set_site_transient( __FUNCTION__, $removed_plugins, 6 * HOUR_IN_SECONDS );

	return $removed_plugins;
}

/**
 * Get the plugins not update since 2 years from repo from our local file
 *
 * @return array The plugins not update since 2 years from the repo
 * @since 1.0
 **/
function secupress_get_notupdated_plugins() {
	static $notupdated_plugins;

	if ( isset( $notupdated_plugins ) ) {
		return $notupdated_plugins;
	}

	if ( false !== ( $from_transient = get_site_transient( __FUNCTION__ ) ) ) {
		return $from_transient;
	}

	$plugins_list_file = SECUPRESS_INC_PATH . 'data/not-updated-in-over-two-years-plugin-list.data';
	if ( is_readable( $plugins_list_file ) ) {
		$notupdated_plugins = array_flip( array_map( 'trim', file( $plugins_list_file ) ) );
	}

	$all_plugins = array_keys( get_plugins() );
	$all_plugins = array_map( 'dirname', $all_plugins );
	$all_plugins = array_flip( $all_plugins );
	$all_plugins = array_intersect_key( $all_plugins, $notupdated_plugins );

	$notupdated_plugins = $all_plugins;
	set_site_transient( __FUNCTION__, $notupdated_plugins, 6 * HOUR_IN_SECONDS );

	return $notupdated_plugins;
}

/**
 * Get the plugins vulnerable from an option, from our option, set by secupress_refresh_vulnerable_plugins()
 *
 * @return array The vulnerables plugins
 * @since 1.0
 **/
function secupress_get_vulnerable_plugins() {
	static $vulnerable_plugins;

	if ( isset( $vulnerable_plugins ) ) {
		return $vulnerable_plugins;
	}

	if ( false !== ( $from_transient = get_site_transient( __FUNCTION__ ) ) ) {
		return $from_transient;
	}

	$temp = get_site_option( 'secupress_bad_plugins' );
	$temp = $temp ? (array) json_decode( $temp ) : array();

	if ( $temp ) {
		$vulnerable_plugins = $temp;
		set_site_transient( __FUNCTION__, $vulnerable_plugins, 6 * HOUR_IN_SECONDS );
		return $vulnerable_plugins;
	}

	return array();
}

/**
 * Get the vulnerable plugins from an option
 *
 * @since 1.0
 **/
function secupress_refresh_vulnerable_plugins() { //// GO PRO
	$plugins  = get_plugins();
	$plugins  = wp_list_pluck( $plugins, 'Version' );
	$args     = array( 'body' => array( 'items' => $plugins, 'type' => 'plugin' ), 'headers' => array( 'X-Secupress' => SECUPRESS_VERSION ) ); //// use client licence here

	$response = wp_remote_post( SECUPRESS_WEB_DEMO . '/wpvulndb.php', $args ); //// url temp
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		update_site_option( 'secupress_bad_plugins', wp_remote_retrieve_body( $response ) );
	}
}

/* THEMES */

/**
 * Get the vulnerable themes from an option
 *
 * @since 1.0
 **/
function secupress_refresh_vulnerable_themes() { //// GO PRO
	$themes = wp_get_themes();
	$themes = wp_list_pluck( $themes, 'Version' );

	$args   = array( 'body' => array( 'items' => $themes, 'type' => 'theme' ), 'headers' => array( 'X-Secupress' => SECUPRESS_VERSION ) ); //// use client licence here

	$response = wp_remote_post( SECUPRESS_WEB_DEMO . '/wpvulndb.php', $args ); //// url temp
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		update_site_option( 'secupress_bad_themes', wp_remote_retrieve_body( $response ) );
	}
}

/**
 * Get the vulnerable themes from an option, from our option, set by secupress_refresh_vulnerable_themes()
 *
 * @return array The vulnerables themes
 * @since 1.0
 **/
function secupress_get_vulnerable_themes() {
	static $vulnerable_themes;

	if ( isset( $vulnerable_themes ) ) {
		return $vulnerable_themes;
	}

	if ( false !== ( $from_transient = get_site_transient( __FUNCTION__ ) ) ) {
		return $from_transient;
	}

	$temp = get_site_option( 'secupress_bad_themes' );
	$temp = $temp ? (array) json_decode( $temp ) : array();

	if ( $temp ) {
		$vulnerable_themes = $temp;
		set_site_transient( __FUNCTION__, $vulnerable_themes, 6 * HOUR_IN_SECONDS );
		return $vulnerable_themes;
	}

	return array();
}