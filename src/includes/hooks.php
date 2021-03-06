<?php

/**
 * Hooks API functions.
 *
 * @package wordpoints
 * @since 2.1.0
 */

/**
 * Get the hooks app.
 *
 * @since 2.1.0
 *
 * @return WordPoints_Hooks The hooks app.
 */
function wordpoints_hooks() {

	if ( ! isset( WordPoints_App::$main ) ) {
		wordpoints_apps();
	}

	return WordPoints_App::$main->get_sub_app( 'hooks' );
}

/**
 * Initialize the hooks API.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_modules_loaded
 */
function wordpoints_init_hooks() {

	$hooks = wordpoints_hooks();

	// Just accessing this causes it to be initialized. We need to do that so
	// the actions will be registered and hooked up. The rest of the API can be
	// lazy-loaded as it is needed.
	$hooks->get_sub_app( 'actions' );
}

/**
 * Register hook extension when the extension registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-extensions
 *
 * @param WordPoints_Class_Registry_Persistent $extensions The extension registry.
 */
function wordpoints_hook_extensions_init( $extensions ) {

	$extensions->register( 'blocker', 'WordPoints_Hook_Extension_Blocker' );
	$extensions->register( 'repeat_blocker', 'WordPoints_Hook_Extension_Repeat_Blocker' );
	$extensions->register( 'reversals', 'WordPoints_Hook_Extension_Reversals' );
	$extensions->register( 'conditions', 'WordPoints_Hook_Extension_Conditions' );
	$extensions->register( 'periods', 'WordPoints_Hook_Extension_Periods' );
}

/**
 * Register hook conditions when the conditions registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-conditions
 *
 * @param WordPoints_Class_Registry_Children $conditions The conditions registry.
 */
function wordpoints_hook_conditions_init( $conditions ) {

	$conditions->register(
		'text'
		, 'contains'
		, 'WordPoints_Hook_Condition_String_Contains'
	);

	$conditions->register(
		'text'
		, 'equals'
		, 'WordPoints_Hook_Condition_Equals'
	);

	$conditions->register(
		'entity'
		, 'equals'
		, 'WordPoints_Hook_Condition_Equals'
	);

	$conditions->register(
		'entity_array'
		, 'contains'
		, 'WordPoints_Hook_Condition_Entity_Array_Contains'
	);
}

/**
 * Register hook actions when the action registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-actions
 *
 * @param WordPoints_Hook_Actions $actions The action registry.
 */
function wordpoints_hook_actions_init( $actions ) {

	$actions->register(
		'user_register'
		, 'WordPoints_Hook_Action'
		, array(
			'action' => 'user_register',
			'data'   => array(
				'arg_index' => array( 'user' => 0 ),
			),
		)
	);

	$actions->register(
		'user_delete'
		, 'WordPoints_Hook_Action'
		, array(
			'action' => is_multisite() ? 'wpmu_delete_user' : 'delete_user',
			'data'   => array(
				'arg_index' => array( 'user' => 0 ),
			),
		)
	);

	$actions->register(
		'user_visit'
		, 'WordPoints_Hook_Action'
		, array(
			'action' => 'wp',
		)
	);

	// Register actions for all of the public post types.
	$post_types = get_post_types( array( 'public' => true ) );

	/**
	 * Filter which post types to register hook actions for.
	 *
	 * @since 2.1.0
	 *
	 * @param string[] The post type slugs ("names").
	 */
	$post_types = apply_filters( 'wordpoints_register_hook_actions_for_post_types', $post_types );

	foreach ( $post_types as $slug ) {
		wordpoints_register_post_type_hook_actions( $slug );
	}
}

/**
 * Register the hook actions for a post type.
 *
 * @since 2.1.0
 *
 * @param string $slug The slug of the post type.
 */
function wordpoints_register_post_type_hook_actions( $slug ) {

	$actions = wordpoints_hooks()->get_sub_app( 'actions' );

	if ( post_type_supports( $slug, 'comments' ) ) {

		$actions->register(
			"comment_approve\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Type_Comment'
			, array(
				'action' => 'transition_comment_status',
				'data'   => array(
					'arg_index'    => array( "comment\\{$slug}" => 2 ),
					'requirements' => array( 0 => 'approved' ),
				),
			)
		);

		$actions->register(
			"comment_new\\{$slug}"
			, 'WordPoints_Hook_Action_Comment_New'
			, array(
				'action' => 'wp_insert_comment',
				'data'   => array(
					'arg_index' => array( "comment\\{$slug}" => 1 ),
				),
			)
		);

		$actions->register(
			"comment_deapprove\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Type_Comment'
			, array(
				'action' => 'transition_comment_status',
				'data'   => array(
					'arg_index'    => array( "comment\\{$slug}" => 2 ),
					'requirements' => array( 1 => 'approved' ),
				),
			)
		);
	}

	// This works for all post types except attachments.
	if ( 'attachment' !== $slug ) {

		$actions->register(
			"post_publish\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Type'
			, array(
				'action' => 'transition_post_status',
				'data'   => array(
					'arg_index'    => array( "post\\{$slug}" => 2 ),
					'requirements' => array( 0 => 'publish' ),
				),
			)
		);

		$actions->register(
			"post_depublish\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Type'
			, array(
				'action' => 'transition_post_status',
				'data'   => array(
					'arg_index'    => array( "post\\{$slug}" => 2 ),
					'requirements' => array( 1 => 'publish' ),
				),
			)
		);

		$actions->register(
			"post_depublish_delete\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Depublish_Delete'
			, array(
				'action' => 'delete_post',
				'data'   => array(
					'arg_index' => array( "post\\{$slug}" => 0 ),
				),
			)
		);

	} else {

		$actions->register(
			'add_attachment'
			, 'WordPoints_Hook_Action'
			, array(
				'action' => 'add_attachment',
				'data'   => array(
					'arg_index' => array( 'post\attachment' => 0 ),
				),
			)
		);

		$actions->register(
			"post_delete\\{$slug}"
			, 'WordPoints_Hook_Action_Post_Type'
			, array(
				'action' => 'delete_post',
				'data'   => array(
					'arg_index' => array( "post\\{$slug}" => 0 ),
				),
			)
		);
	}

	/**
	 * Fires when registering the hook actions for a post type.
	 *
	 * @since 2.1.0
	 *
	 * @param string $slug The slug ("name") of the post type.
	 */
	do_action( 'wordpoints_register_post_type_hook_actions', $slug );
}

/**
 * Register hook events when the event registry is initialized.
 *
 * @since 2.1.0
 *
 * @WordPress\action wordpoints_init_app_registry-hooks-events
 *
 * @param WordPoints_Hook_Events $events The event registry.
 */
function wordpoints_hook_events_init( $events ) {

	$events->register(
		'user_register'
		, 'WordPoints_Hook_Event_User_Register'
		, array(
			'actions' => array(
				'toggle_on'  => 'user_register',
				'toggle_off' => 'user_delete',
			),
			'args' => array(
				'user' => 'WordPoints_Hook_Arg',
			),
		)
	);

	$events->register(
		'user_visit'
		, 'WordPoints_Hook_Event_User_Visit'
		, array(
			'actions' => array(
				'fire' => 'user_visit',
			),
			'args' => array(
				'current:user' => 'WordPoints_Hook_Arg_Current_User',
			),
		)
	);

	// Register events for all of the public post types.
	$post_types = get_post_types( array( 'public' => true ) );

	/**
	 * Filter which post types to register hook events for.
	 *
	 * @since 2.1.0
	 *
	 * @param string[] The post type slugs ("names").
	 */
	$post_types = apply_filters( 'wordpoints_register_hook_events_for_post_types', $post_types );

	foreach ( $post_types as $slug ) {
		wordpoints_register_post_type_hook_events( $slug );
	}
}

/**
 * Register the hook events for a post type.
 *
 * @since 2.1.0
 *
 * @param string $slug The slug of the post type.
 */
function wordpoints_register_post_type_hook_events( $slug ) {

	$events = wordpoints_hooks()->get_sub_app( 'events' );

	if ( 'attachment' === $slug ) {

		$events->register(
			'media_upload'
			, 'WordPoints_Hook_Event_Media_Upload'
			, array(
				'actions' => array(
					'toggle_on'  => 'add_attachment',
					'toggle_off' => "post_delete\\{$slug}",
				),
				'args'    => array(
					"post\\{$slug}" => 'WordPoints_Hook_Arg',
				),
			)
		);

	} else {

		$events->register(
			"post_publish\\{$slug}"
			, 'WordPoints_Hook_Event_Post_Publish'
			, array(
				'actions' => array(
					'toggle_on'  => "post_publish\\{$slug}",
					'toggle_off' => array(
						"post_depublish\\{$slug}",
						"post_depublish_delete\\{$slug}",
					),
				),
				'args'    => array(
					"post\\{$slug}" => 'WordPoints_Hook_Arg',
				),
			)
		);
	}

	if ( post_type_supports( $slug, 'comments' ) ) {

		$events->register(
			"comment_leave\\{$slug}"
			, 'WordPoints_Hook_Event_Comment_Leave'
			, array(
				'actions' => array(
					'toggle_on'  => array(
						"comment_approve\\{$slug}",
						"comment_new\\{$slug}",
					),
					'toggle_off' => "comment_deapprove\\{$slug}",
				),
				'args' => array(
					"comment\\{$slug}" => 'WordPoints_Hook_Arg',
				),
			)
		);
	}

	/**
	 * Fires when registering the hook events for a post type.
	 *
	 * @since 2.1.0
	 *
	 * @param string $slug The slug ("name") of the post type.
	 */
	do_action( 'wordpoints_register_post_type_hook_events', $slug );
}

/**
 * Get the GUID of the primary arg of an event, serialized as JSON.
 *
 * If the event does not have a primary arg, an empty string will be returned.
 *
 * @since 2.1.0
 *
 * @param WordPoints_Hook_Event_Args $event_args The event args.
 *
 * @return string The primary arg's GUID, JSON encoded.
 */
function wordpoints_hooks_get_event_primary_arg_guid_json( WordPoints_Hook_Event_Args $event_args ) {

	$entity = $event_args->get_primary_arg();

	if ( ! $entity ) {
		return '';
	}

	$the_guid = $entity->get_the_guid();

	if ( ! $the_guid ) {
		return '';
	}

	return wp_json_encode( $the_guid );
}

// EOF
