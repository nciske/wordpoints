<?php

/**
 * Functions to integrate the ranks component with the points component.
 *
 * @package WordPoints\Ranks
 * @since 2.1.0
 */

/**
 * Add support for the %rank% placeholder in the My Points widget.
 *
 * @since 1.7.0
 *
 * @WordPress\filter wordpoints_points_widget_text 30 After esc_html().
 */
function wordpoints_ranks_points_widget_text_filter( $text, $instance ) {

	if ( is_user_logged_in() ) {

		$rank = wordpoints_get_formatted_user_rank(
			get_current_user_id()
			, "points_type-{$instance['points_type']}"
			, 'my-points-widget'
			, array( 'widget_settings' => $instance )
		);

		$text = str_replace( '%rank%', $rank, $text );
	}

	return $text;
}

/**
 * Display a message explaining the %rank% placeholder.
 *
 * @since 1.7.0
 *
 * @WordPress\action wordpoints_my_points_widget_below_text_field
 */
function wordpoints_ranks_my_points_widget_below_text_field() {

	?>
	<br />
	<small><i><?php echo esc_html( sprintf( __( '%s will be replaced with the rank of the logged in user', 'wordpoints' ), '%rank%' ) ); ?></i></small>
	<?php
}

/**
 * Add a user's rank to their name in the points top users table.
 *
 * @since 1.7.0
 *
 * @WordPress\filter wordpoints_points_top_users_username
 */
function wordpoints_ranks_points_top_users_username_filter( $name, $user_id, $points_type, $context ) {

	$rank = wordpoints_get_formatted_user_rank(
		$user_id
		, "points_type-{$points_type}"
		, $context
	);

	$name = "{$name} ({$rank})";

	return $name;
}

/**
 * Add support for the points_type attribute to the wordpoints_user_rank shortcode.
 *
 * @since 1.8.0
 *
 * @WordPress\filter shortcode_atts_wordpoints_user_rank
 */
function wordpoints_user_rank_shortcode_points_type_attr( $out, $pairs, $atts ) {

	if ( empty( $out['rank_group'] ) ) {

		if ( isset( $atts['points_type'] ) ) {

			$out['rank_group'] = "points_type-{$atts['points_type']}";

		} else {

			$points_type = wordpoints_get_default_points_type();

			if ( $points_type ) {
				$out['rank_group'] = "points_type-{$points_type}";
			}
		}
	}

	return $out;
}

/**
 * Register the points type rank groups.
 *
 * @since 1.9.0
 *
 * @WordPress\action wordpoints_ranks_register
 */
function wordpoints_register_points_ranks() {

	foreach ( wordpoints_get_points_types() as $slug => $points_type ) {

		WordPoints_Rank_Groups::register_group(
			"points_type-{$slug}"
			, array(
				'name' => $points_type['name'],
				'description' => sprintf(
					__(
						'This rank group is associated with the &#8220;%s&#8221; points type.'
						, 'wordpoints'
					)
					, $points_type['name']
				),
			)
		);

		WordPoints_Rank_Types::register_type(
			"points-{$slug}"
			, 'WordPoints_Points_Rank_Type'
			, array( 'points_type' => $slug )
		);

		WordPoints_Rank_Groups::register_type_for_group(
			"points-{$slug}",
			"points_type-{$slug}"
		);
	}
}

// EOF
