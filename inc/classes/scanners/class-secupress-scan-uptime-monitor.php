<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/**
 * Uptime Monitor scan class.
 *
 * @package SecuPress
 * @subpackage SecuPress_Scan
 * @since 1.0
 */
class SecuPress_Scan_Uptime_Monitor extends SecuPress_Scan implements SecuPress_Scan_Interface {

	const VERSION = '1.0';

	/**
	 * The reference to *Singleton* instance of this class.
	 *
	 * @var (object)
	 */
	protected static $_instance;

	/**
	 * Priority.
	 *
	 * @var (string)
	 */
	public    static $prio    = 'medium';


	/**
	 * Init.
	 *
	 * @since 1.0
	 */
	protected static function init() {
		self::$type     = 'WordPress';
		self::$title    = __( 'Check if your website\'s uptime is monitored.', 'secupress' );
		self::$more     = __( 'Monitoring your website uptime allows you to be alerted if it goes down. While it is not necessary because it has been hacked, it allows you to investigate rapidly.', 'secupress' );
		self::$more_fix = sprintf(
			__( 'This will activate the %s feature.', 'secupress' ),
			'<a href="' . esc_url( secupress_admin_url( 'modules', 'alerts' ) ) . '#row-monitoring_activated">' . __( 'Uptime Monitoring', 'secupress' ) . '</a>'
		);
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
		$messages = array(
			// "good"
			0   => __( 'The <strong>Uptime Monitoring</strong> feature is activated.', 'secupress' ),
			1   => __( 'The <strong>Uptime Monitoring</strong> feature has been activated. You will be notified if the website goes down.', 'secupress' ),
			2   => __( 'The <strong>Uptime Monitoring</strong> feature is already activated.', 'secupress' ),
			// "warning"
			100 => __( 'The <strong>Uptime Monitoring</strong> feature could not be activated. Maybe our server is temporary unavailable, please try again in few minutes.', 'secupress' ),
			// "bad"
			200 => __( 'The <strong>Uptime Monitoring</strong> feature is not activated.', 'secupress' ),
		);

		if ( isset( $message_id ) ) {
			return isset( $messages[ $message_id ] ) ? $messages[ $message_id ] : __( 'Unknown message', 'secupress' );
		}

		return $messages;
	}


	/**
	 * Scan for flaw(s).
	 *
	 * @since 1.0
	 *
	 * @return (array) The scan results.
	 */
	public function scan() {
		$activated = secupress_is_submodule_active( 'alerts', 'uptime-monitoring' );

		if ( ! $activated ) {
			// "bad"
			$this->add_message( 200 );
		} else {
			// "good"
			$this->add_message( 0 );
		}

		return parent::scan();
	}


	/**
	 * Try to fix the flaw(s).
	 *
	 * @since 1.0
	 *
	 * @return (array) The fix results.
	 */
	public function fix() {
		$activated = secupress_is_submodule_active( 'alerts', 'uptime-monitoring' );

		if ( ! $activated ) {
			secupress_activate_submodule( 'alerts', 'uptime-monitoring' );

			$activated = secupress_is_submodule_active( 'alerts', 'uptime-monitoring' );

			if ( ! $activated ) {
				// "warning"
				$this->add_fix_message( 100 );
			} else {
				// "good"
				$this->add_fix_message( 1 );
			}
		} else {
			// "good"
			$this->add_fix_message( 2 );
		}

		return parent::fix();
	}
}