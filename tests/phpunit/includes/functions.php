<?php

/**
 * Miscellaneous functions used in the tests.
 *
 * @package WordPoints\Tests
 * @since 1.0.0
 */

/**
 * Autoloader for the helper classes used by the PHPUnit tests.
 *
 * We could use the WordPoints_Class_Autoloader class instead, but the plugin isn't
 * always loaded during the tests.
 *
 * @since 2.1.0
 *
 * @param string $class_name The name of the class to load.
 */
function wordpoints_phpunit_autoloader( $class_name ) {

	// Autoloading for tests, in case they sub-class one another (which generally
	// they shouldn't).
	if (
		'WordPoints_' === substr( $class_name, 0, 11 )
		&& '_Test' === substr( $class_name, -5 )
	) {

		$file_name = str_replace( '_', '/', strtolower( substr( $class_name, 11, -5 ) ) );
		$file_name = dirname( __FILE__ ) . '/../tests/classes/' . $file_name . '.php';

		if ( ! file_exists( $file_name ) ) {
			return;
		}

		require( $file_name );
	}

	// Autoloading for helpers (test cases, factories, mocks, etc.).
	if ( 'WordPoints_PHPUnit_' !== substr( $class_name, 0, 19 ) ) {
		return;
	}

	$file_name = str_replace( '_', '/', strtolower( substr( $class_name, 19 ) ) );
	$file_name = dirname( __FILE__ ) . '/classes/' . $file_name . '.php';

	if ( ! file_exists( $file_name ) ) {
		return;
	}

	require( $file_name );
}

/**
 * Manually load the plugin main file.
 *
 * The plugin won't be activated within the test WP environment, that's why we need
 * to load it manually. We also mock activate all components so they will be fully
 * loaded too.
 *
 * @since 1.0.0
 *
 * @filter muplugins_loaded
 */
function wordpointstests_manually_load_plugin() {

	global $wpdb;

	add_filter( 'wordpoints_modules_dir', 'wordpointstests_modules_dir' );
	add_filter( 'wordpoints_component_active', '__return_true', 100 );
	add_action( 'wordpoints_components_loaded', 'wordpointstests_manually_activate_components', 0 );

	require WORDPOINTS_TESTS_DIR . '/../../src/wordpoints.php';

	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->base_prefix}wordpoints_points_logs`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->base_prefix}wordpoints_points_log_meta`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->base_prefix}wordpoints_ranks`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->base_prefix}wordpoints_rankmeta`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->base_prefix}wordpoints_user_ranks`" );

	$network_active = false;

	if ( is_multisite() && getenv( 'WORDPOINTS_NETWORK_ACTIVE' ) ) {

		$network_active = true;

		$plugins = get_site_option( 'active_sitewide_plugins' );

		$wordpoints = plugin_basename( WORDPOINTS_DIR . 'wordpoints.php' );

		$plugins[ $wordpoints ] = time();

		update_site_option( 'active_sitewide_plugins', $plugins );
	}

	wordpoints_activate( $network_active );

	delete_site_transient( 'wordpoints_all_site_ids' );
}

/**
 * Get the modules directory for the test modules.
 *
 * @since 1.1.0
 *
 * @filter wordpoints_modules_dir Added by wordpointstests_manually_load_plugin()
 *
 * @return string The path to the test modules directory.
 */
function wordpointstests_modules_dir() {

	return WORDPOINTS_TESTS_DIR . '/data/modules/';
}

/**
 * Manually activate all components.
 *
 * @since 1.0.0
 *
 * @action wordpoints_components_loaded 0 Added by wordpointstests_manually_load_plugin().
 */
function wordpointstests_manually_activate_components() {

	add_filter( 'wordpoints_component_active', '__return_false', 110 );

	$components = WordPoints_Components::instance();

	foreach ( $components->get() as $component => $data ) {

		$components->activate( $component );
	}

	remove_filter( 'wordpoints_component_active', '__return_false', 110 );
}

/**
 * Call a shortcode function by tag name.
 *
 * We can now avoid evil calls to do_shortcode( '[shortcode]' ).
 *
 * @since 1.0.0
 *
 * @param string $tag     The shortcode whose function to call.
 * @param array  $atts    The attributes to pass to the shortcode function. Optional.
 * @param array  $content The shortcode's content. Default is null (none).
 *
 * @return string|false False on failure, the result of the shortcode on success.
 */
function wordpointstests_do_shortcode_func( $tag, array $atts = array(), $content = null ) {

	global $shortcode_tags;

	if ( ! isset( $shortcode_tags[ $tag ] ) ) {
		return false;
	}

	return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
}

/**
 * Programmatically create a new instance of a points hook.
 *
 * @since 1.0.1
 * @since 1.4.0 Allows more than one hook to be created per test.
 * @since 1.9.0 $hook_type can now be a hook type handler.
 *
 * @param string|WordPoints_Points_Hook $hook_type The type of hook to create.
 * @param array                         $instance  The arguments for the instance.
 *
 * @return bool|WordPoints_Points_Hook The points hook object, or false on failure.
 */
function wordpointstests_add_points_hook( $hook_type, $instance = array() ) {

	if ( is_string( $hook_type ) ) {
		$hook = WordPoints_Points_Hooks::get_handler_by_id_base( $hook_type );
	} else {
		$hook = $hook_type;
		$hook_type = $hook->get_id_base();
	}

	if ( ! $hook instanceof WordPoints_Points_Hook ) {
		return false;
	}

	$number = $hook->next_hook_id_number();

	if ( WordPoints_Points_Hooks::get_network_mode() ) {

		$points_types_hooks = wordpoints_get_array_option( 'wordpoints_points_types_hooks', 'site' );
		$points_types_hooks['points'][] = $hook_type . '-' . $number;
		update_site_option( 'wordpoints_points_types_hooks', $points_types_hooks );

	} else {

		$points_types_hooks = wordpoints_get_array_option( 'wordpoints_points_types_hooks' );
		$points_types_hooks['points'][] = $hook_type . '-' . $number;
		update_option( 'wordpoints_points_types_hooks', $points_types_hooks );
	}

	$hook->update_callback( $instance, $number );

	return $hook;
}

/**
 * Programmatically save a new widget instance.
 *
 * Based on wp_ajax_save_widget().
 *
 * @since 1.0.1
 *
 * @param string $id_base    The base ID for instances of this widget.
 * @param array  $settings   The settings for this widget instance. Optional.
 * @param string $sidebar_id The ID of the sidebar to add the widget to. Optional.
 *
 * @return bool Whether the widget was saved successfully.
 */
function wordpointstests_add_widget( $id_base, array $settings = array(), $sidebar_id = null ) {

	global $wp_registered_widget_updates;
	static $multi_number = 0;
	$multi_number++;

	$sidebars = wp_get_sidebars_widgets();

	if ( isset( $sidebar_id ) ) {

		$sidebar = ( isset( $sidebars[ $sidebar_id ] ) ) ? $sidebars[ $sidebar_id ] : array();

	} else {

		$sidebar_id = key( $sidebars );
		$sidebar    = array_shift( $sidebars );
	}

	$sidebar[] = $id_base . '-' . $multi_number;

	$_POST['sidebar'] = $sidebar_id;
	$_POST[ "widget-{$id_base}" ] = array( $multi_number => $settings );
	$_POST['widget-id'] = $sidebar;

	if (
		! isset( $wp_registered_widget_updates[ $id_base ] )
		|| ! is_callable( $wp_registered_widget_updates[ $id_base ]['callback'] )
	) {

		return false;
	}

	$control = $wp_registered_widget_updates[ $id_base ];

	return call_user_func_array( $control['callback'], $control['params'] );
}

/**
 * Check if selenium server is running.
 *
 * Selenium is required for the UI tests.
 *
 * @since 1.0.1
 *
 * @return bool Whether selenium is running.
 */
function wordpointstests_selenium_is_running() {

	$selenium_running = false;
	$fp = fsockopen( 'localhost', 4444 );

	if ( false !== $fp ) {

		$selenium_running = true;
		fclose( $fp );
	}

	return $selenium_running;
}

/**
 * Attempt to start Selenium.
 *
 * To make this work, add the following to wp-tests-config.php:
 * define( 'WORDPOINTS_TESTS_SELENIUM', '/path/to/selenium.jar' );
 *
 * @since 1.0.1
 */
function wordpointstests_start_selenium() {

	if ( ! defined( 'WORDPOINTS_TESTS_SELENIUM' ) ) {
		return false;
	}

	$result = shell_exec( 'java -jar ' . escapeshellarg( WORDPOINTS_TESTS_SELENIUM ) );

	return ( $result && wordpointstests_selenium_is_running() );
}

/**
 * Get the user that is used in the UI tests.
 *
 * @since 1.0.1
 *
 * @return WP_User The user object.
 */
function wordpointstests_ui_user() {

	$user = get_user_by( 'login', 'wordpoints_ui_tester' );

	if ( ! $user ) {

		$user_factory = new WP_UnitTest_Factory_For_User();

		$user_id = $user_factory->create(
			array(
				'user_login' => 'wordpoints_ui_tester',
				'user_email' => 'wordpoints.ui.tester@example.com',
			)
		);

		wp_set_password( 'wordpoints_ui_tester', $user_id );

		$user = get_userdata( $user_id );
	}

	return $user;
}

/**
 * Create a symlink of a plugin in the WordPress tests suite and activate it.
 *
 * @since 1.0.1
 *
 * @param string $plugin     The basename path of the main plugin file.
 * @param string $plugin_dir The name to give the symlinked plugin directory.
 *
 * @return bool Whether this was successful.
 */
function wordpointstests_symlink_plugin( $plugin, $plugin_dir ) {

	$link_name = dirname( WP_PLUGIN_DIR . '/' . $plugin );

	// Check if the symlink exists.
	if ( ! is_link( $link_name ) ) {

		shell_exec( 'ln -s ' . escapeshellarg( $plugin_dir ) . ' ' . escapeshellarg( $link_name ) );

		if ( ! is_link( $link_name ) ) {
			return false;
		}
	}

	return true;
}

// EOF
