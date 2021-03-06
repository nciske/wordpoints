<?php

/**
 * Set up environment for WordPoints tests suite.
 *
 * @package WordPoints\Tests
 * @since 1.0.0
 */

if ( ! getenv( 'WP_TESTS_DIR' ) ) {

	echo( '$_ENV["WP_TESTS_DIR"] is not set.' . PHP_EOL );
	exit( 1 );
}

/**
 * The WordPoints tests directory.
 *
 * @since 1.1.0
 *
 * @const WORDPOINTS_TESTS_DIR
 */
define( 'WORDPOINTS_TESTS_DIR', dirname( dirname( __FILE__ ) ) );

if ( ! defined( 'RUNNING_WORDPOINTS_MODULE_TESTS' ) ) {
	/**
	 * The WP plugin uninstall testing functions.
	 *
	 * We need this so we can check if the uninstall tests are being run.
	 *
	 * @since 1.2.0
	 * @since 1.7.0 Only when not RUNNING_WORDPOINTS_MODULE_TESTS.
	 */
	require WORDPOINTS_TESTS_DIR . '/../../vendor/jdgrimes/wp-plugin-uninstall-tester/includes/functions.php';
}

/**
 * The WordPress tests functions.
 *
 * Clearly, WP_TESTS_DIR should be the path to the WordPress PHPUnit tests checkout.
 *
 * We are loading this so that we can add our tests filter to load the plugin, using
 * tests_add_filter().
 *
 * @since 1.0.0
 */
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

/**
 * Miscellaneous utility functions.
 *
 * Among these is the one that manually loads the plugin. We need to hook it to
 * 'muplugins_loaded'.
 *
 * @since 1.0.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/functions.php';

if (
	defined( 'RUNNING_WORDPOINTS_MODULE_TESTS' )
	&& (
		! function_exists( 'running_wordpoints_module_uninstall_tests' )
		|| ! running_wordpoints_module_uninstall_tests()
	)
) {

	tests_add_filter( 'muplugins_loaded', 'wordpointstests_manually_load_plugin' );

} elseif ( ! running_wp_plugin_uninstall_tests() ) {

	// If we aren't running the uninstall tests, we need to hook in to load the plugin.
	tests_add_filter( 'muplugins_loaded', 'wordpointstests_manually_load_plugin' );
}

// Now that our functions are loaded, we can register the autoloader function.
spl_autoload_register( 'wordpoints_phpunit_autoloader' );

/**
 * Checks which groups we are running, and gives helpful messages.
 *
 * @since 1.0.1
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/class-wordpoints-phpunit-util-getopt.php';

new WordPoints_PHPUnit_Util_Getopt( $_SERVER['argv'] );

/**
 * Sets up the WordPress test environment.
 *
 * We've got our action set up, so we can load this now, and viola, the tests begin.
 * Again, WordPress' PHPUnit test suite needs to be installed under the given path.
 *
 * @since 1.0.0
 */
require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

/**
 * Include the plugin's constants so that we can access the current version.
 *
 * @since 1.4.0
 */
require_once WORDPOINTS_TESTS_DIR . '/../../src/includes/constants.php';

if ( ! defined( 'RUNNING_WORDPOINTS_MODULE_TESTS' ) ) {
	/**
	 * The bootstrap for the uninstall tests.
	 *
	 * @since 1.2.0
	 * @since 1.7.0 Only when not RUNNING_WORDPOINTS_MODULE_TESTS.
	 */
	require WORDPOINTS_TESTS_DIR . '/../../vendor/jdgrimes/wp-plugin-uninstall-tester/bootstrap.php';
}

/**
 * The WordPoints_UnitTestCase class.
 *
 * @since 1.5.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/wordpoints.php';

/**
 * The Ajax unit test case class.
 *
 * @since 1.7.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/ajax.php';

/**
 * The WordPoints_Points_UnitTestCase class.
 *
 * @since 1.0.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/points.php';

/**
 * The WordPoints_Points_AJAX_UnitTestCase class.
 *
 * @since 1.3.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/points-ajax.php';

/**
 * The ranks unit test case class.
 *
 * @since 1.7.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/ranks.php';

/**
 * The ranks Ajax unit test case class.
 *
 * @since 1.7.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/testcases/ranks-ajax.php';

/**
 * The points log factory.
 *
 * @since 1.6.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/factories/points-log.php';

/**
 * The rank factory.
 *
 * @since 1.7.0
 */
require_once WORDPOINTS_TESTS_DIR . '/includes/factories/rank.php';

/**
 * The WordPress filter mock.
 *
 * @since 2.0.0
 */
require_once( WORDPOINTS_TESTS_DIR . '/includes/mocks/filter.php' );

if ( class_exists( 'WordPoints_Points_Hook' ) ) {
	/**
	 * The points hook mocks.
	 *
	 * @since 1.9.0
	 */
	require_once( WORDPOINTS_TESTS_DIR . '/includes/mocks/points-hooks.php' );
}

if ( class_exists( 'WordPoints_Rank_Type' ) ) {
	/**
	 * The rank type mock.
	 *
	 * @since 1.7.0
	 */
	require_once( WORDPOINTS_TESTS_DIR . '/includes/mocks/rank-type.php' );
}

if ( class_exists( 'WordPoints_Un_Installer_Base' ) ) {
	/**
	 * The un/installer mock.
	 *
	 * @since 2.0.0
	 */
	require_once( WORDPOINTS_TESTS_DIR . '/includes/mocks/un-installer.php' );
}

$factory = WordPoints_PHPUnit_Factory::init();
$factory->register( 'entity', 'WordPoints_PHPUnit_Factory_For_Entity' );
$factory->register( 'hook_reaction', 'WordPoints_PHPUnit_Factory_For_Hook_Reaction' );
$factory->register( 'hook_reaction_store', 'WordPoints_PHPUnit_Factory_For_Hook_Reaction_Store' );
$factory->register( 'hook_reactor', 'WordPoints_PHPUnit_Factory_For_Hook_Reactor' );
$factory->register( 'hook_extension', 'WordPoints_PHPUnit_Factory_For_Hook_Extension' );
$factory->register( 'hook_event', 'WordPoints_PHPUnit_Factory_For_Hook_Event' );
$factory->register( 'hook_action', 'WordPoints_PHPUnit_Factory_For_Hook_Action' );
$factory->register( 'hook_condition', 'WordPoints_PHPUnit_Factory_For_Hook_Condition' );
$factory->register( 'post_type', 'WordPoints_PHPUnit_Factory_For_Post_Type' );
$factory->register( 'user_role', 'WordPoints_PHPUnit_Factory_For_User_Role' );

global $EZSQL_ERROR;
$EZSQL_ERROR = new WordPoints_PHPUnit_Error_Handler_Database();

// https://core.trac.wordpress.org/ticket/25239
$_SERVER['SERVER_NAME'] = 'example.com';

// EOF
