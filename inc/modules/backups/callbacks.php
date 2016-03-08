<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/*------------------------------------------------------------------------------------------------*/
/* ON MODULE SETTINGS SAVE ====================================================================== */
/*------------------------------------------------------------------------------------------------*/

/**
 * Callback to filter, sanitize.
 *
 * @since 1.0
 * @return array $settings
 */
function __secupress_backups_settings_callback( $settings ) {
	$locations = secupress_backups_storage_labels();
	$settings  = $settings ? $settings : array();

	if ( isset( $settings['sanitized'] ) ) {
		return $settings;
	}
	$settings['sanitized'] = 1;

	if ( ! isset( $settings['backups-storage_location'] ) || ! secupress_is_pro() || ! isset( $locations[ $settings['backups-storage_location'] ] ) ) {
		$settings['backups-storage_location'] = 'local';
	}

	return $settings;
}

/**
 * Will do a DB backup
 *
 * @return void
 * @since 1.0
 **/
add_action( 'wp_ajax_secupress_backup_db',    '__secupress_do_backup_db' );
add_action( 'admin_post_secupress_backup_db', '__secupress_do_backup_db' );

function __secupress_do_backup_db() {

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'secupress_backup_db' ) ) {
		secupress_admin_die();
	}

	$wp_tables      = secupress_get_wp_tables();
	$other_tables   = secupress_get_non_wp_tables();
	$backup_storage = secupress_get_module_option( 'backups-storage_location', 'local', 'backups' );
	$backup_file    = '';

	if ( 'local' == $backup_storage ) {
		$backup_file  = secupress_get_hashed_folder_name( 'backup', WP_CONTENT_DIR . '/backups/' ) . secupress_get_db_backup_filename();

		if ( secupress_pre_backup() ) {
			file_put_contents( $backup_file, secupress_get_db_tables_content( array_merge( $wp_tables, $other_tables ) ) );
			$backup_file = secupress_zip_backup_file( $backup_file );
		}
	} elseif ( secupress_is_pro() ) {
		$backup_file = apply_filters( 'secupress.do_backup.file', $backup_file, $backup_storage );
	}

	if ( ! $backup_file ) {
		secupress_admin_die();
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$backup_files = secupress_get_backup_file_list();

		wp_send_json_success( array(
			'elemRow'   => secupress_print_backup_file_formated( $backup_file, false ),
			'countText' => sprintf( _n( '%s available Backup', '%s available Backups', count( $backup_files ), 'secupress' ), number_format_i18n( count( $backup_files ) ) ),
		) );
	}

	wp_redirect( wp_get_referer() );
	die();
}

/**
 * Will download a requested backup
 *
 * @return void
 * @since 1.0
 **/
add_action( 'admin_post_secupress_download_backup', '__secupress_download_backup_ajax_post_cb' );
// No need any AJAX support here

function __secupress_download_backup_ajax_post_cb() {

	if ( ! isset( $_GET['_wpnonce'], $_GET['file'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'secupress_download_backup-' . $_GET['file'] ) ) {
		wp_nonce_ays( '' );
	}

	$file = glob( secupress_get_hashed_folder_name( 'backup', WP_CONTENT_DIR . '/backups/' ) . '*' . $_GET['file'] . '*.{zip,sql}', GLOB_BRACE );

	if ( $file ) {
		$file = reset( $file );
	} else {
		wp_nonce_ays( '' );
	}

	if ( ini_get( 'zlib.output_compression' ) ) {
		ini_set( 'zlib.output_compression', 'Off' );
	}

	header( 'Pragma: public' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s', filemtime( $file ) ) . ' GMT' );
	header( 'Cache-Control: private', false );
	header( 'Content-Type: application/force-download' );
	header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'Connection: close' );
	readfile($file);
	die();
}

/**
 * Will delete a specified backup file
 *
 * @return void
 * @since 1.0
 **/
add_action( 'wp_ajax_secupress_delete_backup',    '__secupress_delete_backup_ajax_post_cb' );
add_action( 'admin_post_secupress_delete_backup', '__secupress_delete_backup_ajax_post_cb' );

function __secupress_delete_backup_ajax_post_cb() {

	if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['file'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'secupress_delete_backup-' . $_GET['file'] ) ) {
		secupress_admin_die();
	}

	$files = glob( secupress_get_hashed_folder_name( 'backup', WP_CONTENT_DIR . '/backups/' ) . '*' . $_GET['file'] . '*.{zip,sql}', GLOB_BRACE );

	if ( ! $files ) {
		secupress_admin_die();
	}

	@array_map( 'unlink', $files );

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$backup_files = secupress_get_backup_file_list();

		wp_send_json_success( array(
			'countText' => sprintf( _n( '%s available Backup', '%s available Backups', count( $backup_files ), 'secupress' ), number_format_i18n( count( $backup_files ) ) ),
		) );
	}

	wp_redirect( wp_get_referer() );
	die();
}

/**
 * Will delete all the backups
 *
 * @return void
 * @since 1.0
 **/

add_action( 'wp_ajax_secupress_delete_backups',    '__secupress_delete_backups_ajax_post_cb' );
add_action( 'admin_post_secupress_delete_backups', '__secupress_delete_backups_ajax_post_cb' );

function __secupress_delete_backups_ajax_post_cb() {

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'secupress_delete_backups' ) ) {
		secupress_admin_die();
	}

	$files = glob( secupress_get_hashed_folder_name( 'backup', WP_CONTENT_DIR . '/backups/' ) . '*.{zip,sql}', GLOB_BRACE );

	if ( ! $files ) {
		secupress_admin_die();
	}

	@array_map( 'unlink', $files );

	secupress_admin_send_response_or_redirect( 1 );
}

/*------------------------------------------------------------------------------------------------*/
/* TOOLS ======================================================================================== */
/*------------------------------------------------------------------------------------------------*/

/**
 * Return the values/labels used for the backups storage setting.
 *
 * @since 1.0
 *
 * @return (array) An array with back types as keys and labels as values.
 */
function secupress_backups_storage_labels() {
	return array(
		'local'     => __( 'Local', 'secupress' ),
		'ftp'       => __( 'FTP', 'secupress' ),
		'amazons3'  => __( 'Amazon S3', 'secupress' ),
		'dropbox'   => __( 'Dropbox', 'secupress' ),
		'rackspace' => __( 'Rackspace Cloud', 'secupress' ),
	);
}
