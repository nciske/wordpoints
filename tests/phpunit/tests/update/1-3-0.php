<?php

/**
 * A test case for WordPoints updates.
 *
 * @package WordPoints\Tests
 * @since 1.5.0
 */

/**
 * Test that the plugin updates itself properly.
 *
 * Since 1.3.0 this was called simply WordPoints_Update_Test.
 *
 * @since 1.5.0
 *
 * @group update
 *
 * @covers WordPoints_Un_Installer::update_single_to_1_3_0
 */
class WordPoints_1_3_0_Update_Test extends WordPoints_UnitTestCase {

	/**
	 * @since 2.0.0
	 */
	protected $previous_version = '1.2.0';

	/**
	 * Test the update to 1.3.0.
	 *
	 * @since 1.5.0
	 */
	public function test_update_to_1_3_0() {

		// First remove the capabilities if they have been added.
		wordpoints_remove_custom_caps( array_keys( wordpoints_get_custom_caps() ) );

		$administrator = get_role( 'administrator' );
		$this->assertFalse( $administrator->has_cap( 'install_wordpoints_modules' ) );
		$this->assertFalse( $administrator->has_cap( 'activate_wordpoints_modules' ) );
		$this->assertFalse( $administrator->has_cap( 'delete_wordpoints_modules' ) );

		// Mock an update.
		$this->update_wordpoints();

		// Check that the capabilities were added.
		$administrator = get_role( 'administrator' );
		$this->assertTrue( $administrator->has_cap( 'install_wordpoints_modules' ) );
		$this->assertTrue( $administrator->has_cap( 'activate_wordpoints_modules' ) );
		$this->assertTrue( $administrator->has_cap( 'delete_wordpoints_modules' ) );
	}
}

// EOF
