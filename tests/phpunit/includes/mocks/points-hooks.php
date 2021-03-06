<?php

/**
 * Contains empty classes that extend the hook classes and are used as test doubles.
 *
 * @package WordPoints\tests
 * @since 1.9.0
 */

/**
 * Test double for the base points hook class.
 *
 * @since 1.9.0
 */
class WordPoints_Points_Hook_TestDouble extends WordPoints_Points_Hook {}

/**
 * Test double for the base Post hook class.
 *
 * @since 1.9.0
 */
class WordPoints_Post_Type_Points_Hook_TestDouble
	extends WordPoints_Post_Type_Points_Hook_Base {

	/**
	 * @since 1.9.0
	 */
	protected $defaults = array( 'post_type' => 'ALL', 'auto_reverse' => 1 );
}

// EOF
