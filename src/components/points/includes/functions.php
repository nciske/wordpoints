<?php

/**
 * Utility functions for the points component.
 *
 * @package WordPoints\Points
 * @since 1.0.0
 */

/**
 * Register hook reactors when the reactors registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-reactors
 *
 * @param WordPoints_Class_Registry_Persistent $reactors The reactors registry.
 */
function wordpoints_points_hook_reactors_init( $reactors ) {

	$reactors->register( 'points', 'WordPoints_Points_Hook_Reactor' );
	$reactors->register( 'points_legacy', 'WordPoints_Points_Hook_Reactor_Legacy' );
}

/**
 * Register hook reaction stores when the reaction store registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-reaction_stores
 *
 * @param WordPoints_Class_Registry_Children $reaction_stores The store registry.
 */
function wordpoints_points_hook_reaction_stores_init( $reaction_stores ) {

	$reaction_stores->register(
		'standard'
		, 'points'
		, 'WordPoints_Hook_Reaction_Store_Options'
	);

	if ( is_wordpoints_network_active() ) {
		$reaction_stores->register(
			'network'
			, 'points'
			, 'WordPoints_Hook_Reaction_Store_Options_Network'
		);
	}
}

/**
 * Register hook extensions when the extension registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-extensions
 *
 * @param WordPoints_Class_Registry_Persistent $extensions The extension registry.
 */
function wordpoints_points_hook_extensions_init( $extensions ) {
	$extensions->register( 'points_legacy_reversals', 'WordPoints_Points_Hook_Extension_Legacy_Reversals' );
}

/**
 * Register scripts and styles for the component.
 *
 * @since 1.0.0
 *
 * @WordPress\action wp_enqueue_scripts    5 So they are ready on enqueue (10).
 * @WordPress\action admin_enqueue_scripts 5 So they are ready on enqueue (10).
 */
function wordpoints_points_register_scripts() {

	$assets_url = WORDPOINTS_URL . '/components/points/assets/';

	wp_register_style(
		'wordpoints-top-users'
		,$assets_url . 'css/top-users.css'
		,null
		,WORDPOINTS_VERSION
	);

	wp_register_style(
		'wordpoints-points-logs'
		,$assets_url . 'css/points-logs.css'
		,null
		,WORDPOINTS_VERSION
	);
}

/**
 * Get the custom caps added by the points component.
 *
 * @since 1.3.0
 *
 * @return array The custom capabilities as keys, WP core counterparts as values.
 */
function wordpoints_points_get_custom_caps() {

	return array(
		'set_wordpoints_points'                  => 'manage_options',
		'manage_network_wordpoints_points_hooks' => 'manage_network_options',
		'manage_wordpoints_points_types'         => ( is_wordpoints_network_active() ) ? 'manage_network_options' : 'manage_options',
	);
}

/**
 * Format points for display.
 *
 * @since 1.0.0
 *
 * @WordPress\filter wordpoints_format_points 5 Runs before default of 10, but you
 *                   should remove the filter if you will always be overriding it.
 *
 * @param string $formatted The formatted points value.
 * @param int    $points    The raw points value.
 * @param string $type      The type of $points.
 *
 * @return string $points formatted with prefix and suffix.
 */
function wordpoints_format_points_filter( $formatted, $points, $type ) {

	$points_type = wordpoints_get_points_type( $type );

	if ( isset( $points_type['prefix'], $points_type['suffix'] ) ) {

		if ( $points < 0 ) {

			$points = abs( $points );
			$points_type['prefix'] = '-' . $points_type['prefix'];
		}

		$formatted = esc_html( $points_type['prefix'] . $points . $points_type['suffix'] );
	}

	return $formatted;
}

/**
 * Display a dropdown of points types.
 *
 * The $args parameter accepts an extra argument, 'options', which will be added to
 * the points types in the dropdown.
 *
 * @since 1.0.0
 *
 * @param array $args The arguments for the dropdown {@see
 *        WordPoints_Dropdown_Builder::$args}.
 */
function wordpoints_points_types_dropdown( array $args ) {

	$points_types = array();

	foreach ( wordpoints_get_points_types() as $slug => $settings ) {

		$points_types[ $slug ] = $settings['name'];
	}

	if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
		$points_types = $args['options'] + $points_types;
	}

	$dropdown = new WordPoints_Dropdown_Builder( $points_types, $args );

	$dropdown->display();
}

/**
 * Delete points logs and meta when a user is deleted.
 *
 * @since 1.2.0
 *
 * @WordPress\action deleted_user
 *
 * @param int $user_id The ID of the user just deleted.
 */
function wordpoints_delete_points_logs_for_user( $user_id ) {

	global $wpdb;

	$query_args = array( 'fields' => 'id', 'user_id' => $user_id );

	// If the user is being deleted from all blogs on multisite.
	if ( is_multisite() && ! get_userdata( $user_id ) ) {
		$query_args['blog_id'] = 0;
	}

	// Delete log meta.
	$query = new WordPoints_Points_Logs_Query( $query_args );

	foreach ( $query->get( 'col' ) as $log_id ) {
		wordpoints_points_log_delete_all_metadata( $log_id );
	}

	$where = array( 'user_id' => $user_id );

	if ( ! isset( $query_args['blog_id'] ) ) {
		$where['blog_id'] = $wpdb->blogid;
	}

	// Now delete the logs.
	$wpdb->delete(
		$wpdb->wordpoints_points_logs
		,$where
		,'%d'
	);

	wordpoints_flush_points_logs_caches( array( 'user_id' => $user_id ) );
}

/**
 * Delete logs and meta for a blog when it is deleted.
 *
 * @since 1.2.0
 *
 * @WordPress\action delete_blog
 *
 * @param int $blog_id The ID of the blog being deleted.
 */
function wordpoints_delete_points_logs_for_blog( $blog_id ) {

	global $wpdb;

	// Delete log meta.
	$query = new WordPoints_Points_Logs_Query( array( 'fields' => 'id' ) );

	foreach ( $query->get( 'col' ) as $log_id ) {
		wordpoints_points_log_delete_all_metadata( $log_id );
	}

	// Now delete the logs.
	$wpdb->delete(
		$wpdb->wordpoints_points_logs
		,array( 'blog_id' => $blog_id )
		,'%d'
	);

	wordpoints_flush_points_logs_caches();
}

/**
 * Display a message with a points type's settings when it uses a custom meta key.
 *
 * @since 1.3.0
 *
 * @WordPress\action wordpoints_points_type_form_top
 *
 * @param string $points_type The type of points the settings are being shown for.
 */
function wordpoints_points_settings_custom_meta_key_message( $points_type ) {

	$custom_key = wordpoints_get_points_type_setting( $points_type, 'meta_key' );

	if ( ! empty( $custom_key ) ) {
		echo '<p>' . esc_html( sprintf( __( 'This points type uses a custom meta key: %s', 'wordpoints' ), $custom_key ) ) . '</p>';
	}
}

/**
 * Show a message on the points logs admin panel when a type uses a custom meta key.
 *
 * @since 1.3.0
 *
 * @WordPress\action wordpoints_admin_points_logs_tab
 *
 * @param string $points_type The type of points whose logs are being displayed.
 */
function wordpoints_points_logs_custom_meta_key_message( $points_type ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$custom_key = wordpoints_get_points_type_setting( $points_type, 'meta_key' );

	if ( ! empty( $custom_key ) ) {
		wordpoints_show_admin_error( esc_html( sprintf( __( 'This points type uses a custom meta key (&#8220;%s&#8221;). If this key is also used by another plugin, changes made by it will not be logged. Only transactions performed by WordPoints are included in the logs.', 'wordpoints' ), $custom_key ) ) );
	}
}

/**
 * Register the global cache groups used by this component.
 *
 * @since 1.5.0
 *
 * @WordPress\action init 5 Earlier than the default so that the groups will be
 *                          registered before any other code runs.
 */
function wordpoints_points_add_global_cache_groups() {

	if ( function_exists( 'wp_cache_add_global_groups' ) ) {

		$groups = array(
			'wordpoints_network_points_logs_query',
			'wordpoints_points_log_meta',
		);

		if ( is_wordpoints_network_active() ) {
			$groups[] = 'wordpoints_points_top_users';
		}

		wp_cache_add_global_groups( $groups );
	}
}

// EOF
