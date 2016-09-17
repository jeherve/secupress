<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/**
 * PHP Disclosure scan class.
 *
 * @package SecuPress
 * @subpackage SecuPress_Scan
 * @since 1.0
 */
class SecuPress_Scan_PHP_Disclosure extends SecuPress_Scan implements SecuPress_Scan_Interface {

	/** Constants. ============================================================================== */

	/**
	 * Class version.
	 *
	 * @var (string)
	 */
	const VERSION = '1.0';


	/** Properties. ============================================================================= */

	/**
	 * The reference to the *Singleton* instance of this class.
	 *
	 * @var (object)
	 */
	protected static $_instance;


	/** Init and messages. ====================================================================== */

	/**
	 * Init.
	 *
	 * @since 1.0
	 */
	protected function init() {
		global $is_apache, $is_nginx, $is_iis7;

		$this->title = __( 'Check if your WordPress lists the the PHP modules <em>(know as PHP Easter Egg)</em>.', 'secupress' );
		$this->more  = __( 'PHP contains a flaw that disclosure sensitive information from installed modules, resulting in a loss of confidentiality.', 'secupress' );

		if ( ! $is_apache && ! $is_nginx && ! $is_iis7 ) {
			$this->more_fix = static::get_messages( 301 );
			$this->fixable  = false;
			return;
		}

		if ( $is_apache ) {
			/** Translators: %s is a file name. */
			$this->more_fix = sprintf( __( 'Add rules in your %s file to prevent attackers reading sensitive information about PHP modules.', 'secupress' ), '<code>.htaccess</code>' );
		} elseif ( $is_iis7 ) {
			/** Translators: %s is a file name. */
			$this->more_fix = sprintf( __( 'Add rules in your %s file to prevent attackers reading sensitive information about PHP modules.', 'secupress' ), '<code>web.config</code>' );
		} else {
			/** Translators: %s is a file name. */
			$this->more_fix = sprintf( __( 'The %s file cannot be edited automatically, you will be given the rules to add into this file manually, to prevent attackers reading sensitive information about PHP modules.', 'secupress' ), '<code>nginx.conf</code>' );
		}
	}


	/**
	 * Get messages.
	 *
	 * @since 1.0
	 *
	 * @param (int) $message_id A message ID.
	 *
	 * @return (string|array) A message if a message ID is provided. An array containing all messages otherwise.
	 */
	public static function get_messages( $message_id = null ) {
		global $is_apache;
		$config_file = $is_apache ? '.htaccess' : 'web.config';

		$messages = array(
			// "good"
			0   => __( 'Your site does not reveal the PHP modules.', 'secupress' ),
			/** Translators: %s is a file name. */
			1   => sprintf( __( 'The rules forbidding access to the <strong>PHP Easter Egg</strong> have been successfully added to your %s file.', 'secupress' ), "<code>$config_file</code>" ),
			// "warning"
			100 => __( 'Unable to determine if your homepage is disclosing PHP Easter Egg.', 'secupress' ),
			// "bad"
			/** Translators: %s is an URL */
			200 => sprintf( __( '%s should not be accessible to anyone.', 'secupress' ), '<code>' . esc_url( user_trailingslashit( home_url() ) . '?=PHPB8B5F2A0-3C92-11d3-A3A9-4C7B08C10000' ) . '</code>' ),
			// "cantfix"
			/** Translators: 1 is a file name, 2 is some code */
			300 => sprintf( __( 'Your server runs <strong>Nginx</strong>, PHP modules disclosure cannot be protected automatically but you can do it yourself by adding the following code to your %1$s file: %2$s', 'secupress' ), '<code>nginx.conf</code>', '%s' ),
			301 => __( 'Your server runs an unrecognized system. PHP modules disclosure cannot be protected automatically.', 'secupress' ),
			/** Translators: 1 is a file name, 2 is some code */
			302 => sprintf( __( 'Your %1$s file is not writable. Please add the following lines at the beginning of the file: %2$s', 'secupress' ), "<code>$config_file</code>", '%s' ),
			/** Translators: 1 is a file name, 2 is a folder path (kind of), 3 is some code */
			303 => sprintf( __( 'Your %1$s file is not writable. Please add the following lines inside the tags hierarchy %2$s (create it if does not exist): %3$s', 'secupress' ), "<code>$config_file</code>", '%1$s', '%2$s' ),
		);

		if ( isset( $message_id ) ) {
			return isset( $messages[ $message_id ] ) ? $messages[ $message_id ] : __( 'Unknown message', 'secupress' );
		}

		return $messages;
	}


	/** Scan. =================================================================================== */

	/**
	 * Scan for flaw(s).
	 *
	 * @since 1.0
	 *
	 * @return (array) The scan results.
	 */
	public function scan() {
		// - http://osvdb.org/12184
		$response = wp_remote_get( user_trailingslashit( home_url() ) . '?=PHPB8B5F2A0-3C92-11d3-A3A9-4C7B08C10000', array( 'redirection' => 0 ) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {

			$response_body = wp_remote_retrieve_body( $response );

			if ( strpos( $response_body, '<h1>PHP Credits</h1>' ) > 0 && strpos( $response_body, '<title>phpinfo()</title>' ) > 0 ) {
				// "bad"
				$this->add_message( 200 );

				if ( ! $this->fixable ) {
					$this->add_pre_fix_message( 301 );
				}
			}
		} elseif ( is_wp_error( $response ) ) {
			// If it's not an error, then no disclosure.
			// "warning".
			$this->add_message( 100 );
		}

		// "good"
		$this->maybe_set_status( 0 );

		return parent::scan();
	}


	/** Fix. ==================================================================================== */

	/**
	 * Try to fix the flaw(s).
	 *
	 * @since 1.0
	 *
	 * @return (array) The fix results.
	 */
	public function fix() {
		global $is_apache, $is_nginx, $is_iis7;

		if ( $is_apache ) {
			$this->fix_apache();
		} elseif ( $is_iis7 ) {
			$this->fix_iis7();
		} elseif ( $is_nginx ) {
			$this->fix_nginx();
		}

		// "good"
		$this->maybe_set_fix_status( 0 );

		return parent::fix();
	}


	/**
	 * Fix for Apache system.
	 *
	 * @since 1.0
	 */
	protected function fix_apache() {
		global $wp_settings_errors;

		secupress_activate_submodule( 'sensitive-data', 'php-easter-egg' );

		// Got error?
		$last_error = is_array( $wp_settings_errors ) && $wp_settings_errors ? end( $wp_settings_errors ) : false;

		if ( $last_error && 'general' === $last_error['setting'] && 'apache_manual_edit' === $last_error['code'] ) {
			$rules = static::get_rules_from_error( $last_error );
			// "cantfix"
			$this->add_fix_message( 302, array( $rules ) );
			array_pop( $wp_settings_errors );
			return;
		}

		// "good"
		$this->add_fix_message( 1 );
	}


	/**
	 * Fix for IIS7 system.
	 *
	 * @since 1.0
	 */
	protected function fix_iis7() {
		global $wp_settings_errors;

		secupress_activate_submodule( 'sensitive-data', 'php-easter-egg' );

		// Got error?
		$last_error = is_array( $wp_settings_errors ) && $wp_settings_errors ? end( $wp_settings_errors ) : false;

		if ( $last_error && 'general' === $last_error['setting'] && 'iis7_manual_edit' === $last_error['code'] ) {
			$rules = static::get_rules_from_error( $last_error );
			$path  = static::get_code_tag_from_error( $last_error, 'secupress-iis7-path' );
			// "cantfix"
			$this->add_fix_message( 303, array( $path, $rules ) );
			array_pop( $wp_settings_errors );
			return;
		}

		// "good"
		$this->add_fix_message( 1 );
	}


	/**
	 * Fix for nginx system.
	 *
	 * @since 1.0
	 */
	protected function fix_nginx() {
		global $wp_settings_errors;

		secupress_activate_submodule( 'sensitive-data', 'php-easter-egg' );

		// Get the error.
		$last_error = is_array( $wp_settings_errors ) && $wp_settings_errors ? end( $wp_settings_errors ) : false;
		$rules      = '<code>Error</code>';

		if ( $last_error && 'general' === $last_error['setting'] && 'nginx_manual_edit' === $last_error['code'] ) {
			$rules = static::get_rules_from_error( $last_error );
			array_pop( $wp_settings_errors );
		}

		// "cantfix"
		$this->add_fix_message( 300, array( $rules ) );
	}
}
