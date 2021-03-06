<?php

/**
 * Test the points log meta functions.
 *
 * @package WordPoints\Tests\Points
 * @since 1.0.0
 */

/**
 * Points log meta test case.
 *
 * @since 1.0.0
 *
 * @group points
 */
class WordPoints_Points_Logs_Meta_Test extends WordPoints_Points_UnitTestCase {

	/**
	 * Test the log meta flow.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_add_points_log_meta
	 * @covers ::wordpoints_get_points_log_meta
	 * @covers ::wordpoints_update_points_log_meta
	 */
	public function test_log_meta() {

		$log_id = 1;

		wordpoints_add_points_log_meta( $log_id, 'test', 'one' );
		$this->assertEquals( 'one', wordpoints_get_points_log_meta( $log_id, 'test', true ) );

		wordpoints_update_points_log_meta( $log_id, 'test', 'two' );
		$this->assertEquals( 'two', wordpoints_get_points_log_meta( $log_id, 'test', true ) );

		$this->assertEquals( array( 'two' ), wordpoints_get_points_log_meta( $log_id, 'test' ) );
		$this->assertEquals( array( 'test' => array( 'two' ) ), wordpoints_get_points_log_meta( $log_id ) );

		$result = wordpoints_update_points_log_meta( $log_id, 'test', 'three', 'one' );
		$this->assertEquals( 0, $result );
		$this->assertEquals( 'two', wordpoints_get_points_log_meta( $log_id, 'test', true ) );

		$result = wordpoints_update_points_log_meta( $log_id, 'test', 'three', 'two' );
		$this->assertEquals( 1, $result );
		$this->assertEquals( 'three', wordpoints_get_points_log_meta( $log_id, 'test', true ) );
	}

	/**
	 * Test deleting all log meta.
	 *
	 * @since 1.8.0
	 *
	 * @covers ::wordpoints_points_log_delete_all_metadata
	 */
	public function test_delete_all_log_meta() {

		$log_id = 1;

		wordpoints_update_points_log_meta( $log_id, 'test', 'one' );
		$this->assertEquals( 'one', wordpoints_get_points_log_meta( $log_id, 'test', true ) );

		wordpoints_update_points_log_meta( $log_id, 'test_2', 'two' );
		$this->assertEquals( 'two', wordpoints_get_points_log_meta( $log_id, 'test_2', true ) );

		wordpoints_points_log_delete_all_metadata( $log_id );

		$this->assertEquals( '', wordpoints_get_points_log_meta( $log_id, 'test', true ) );
		$this->assertEquals( '', wordpoints_get_points_log_meta( $log_id, 'test_2', true ) );
		$this->assertEquals( array(), wordpoints_get_points_log_meta( $log_id ) );
	}

	/**
	 * Test that the data isn't expected slashed.
	 *
	 * @since 2.1.0
	 *
	 * @covers ::wordpoints_add_points_log_meta
	 * @covers ::wordpoints_get_points_log_meta
	 * @covers ::wordpoints_update_points_log_meta
	 * @covers ::wordpoints_delete_points_log_meta
	 */
	public function test_log_meta_slashing() {

		$log_id = 1;

		wordpoints_add_points_log_meta( $log_id, 'slash\test', 'test\slashing' );
		$this->assertEquals( 'test\slashing', wordpoints_get_points_log_meta( $log_id, 'slash\test', true ) );

		wordpoints_update_points_log_meta( $log_id, 'slash\test', 'test\slashing2' );
		$this->assertEquals( 'test\slashing2', wordpoints_get_points_log_meta( $log_id, 'slash\test', true ) );

		wordpoints_delete_points_log_meta( $log_id, 'slash\test', 'test\slashing2' );
		$this->assertEquals( '', wordpoints_get_points_log_meta( $log_id, 'slash\test', true ) );
	}
}

// EOF
