<?php

/**
 * Test case for WordPoints_Hook_Event_Args.
 *
 * @package WordPoints\PHPUnit\Tests
 * @since 2.1.0
 */

/**
 * Tests WordPoints_Hook_Event_Args.
 *
 * @since 2.1.0
 *
 * @covers WordPoints_Hook_Event_Args
 */
class WordPoints_Hook_Event_Args_Test extends WordPoints_PHPUnit_TestCase_Hooks {

	/**
	 * Test checking if an event is repeatable when it has no stateful args.
	 *
	 * @since 2.1.0
	 */
	public function test_is_repeatable() {

		$this->factory->wordpoints->entity->create( array( 'slug' => 'test' ) );

		$arg = new WordPoints_PHPUnit_Mock_Hook_Arg( 'test' );

		$args = new WordPoints_Hook_Event_Args( array( $arg ) );

		$this->assertFalse( $args->is_event_repeatable() );

		$this->assertEquals( $arg->get_entity(), $args->get_primary_arg() );
		$this->assertSame( array(), $args->get_stateful_args() );
	}

	/**
	 * Test checking if an event is repeatable when it has only stateful args.
	 *
	 * @since 2.1.0
	 */
	public function test_is_repeatable_stateful_args() {

		$this->factory->wordpoints->entity->create( array( 'slug' => 'test' ) );

		$arg = new WordPoints_PHPUnit_Mock_Hook_Arg( 'test' );
		$arg_2 = new WordPoints_PHPUnit_Mock_Hook_Arg( 'another:test' );

		$arg->is_stateful = $arg_2->is_stateful = true;

		$args = new WordPoints_Hook_Event_Args( array( $arg, $arg_2 ) );

		$this->assertTrue( $args->is_event_repeatable() );

		$this->assertFalse( $args->get_primary_arg() );
		$this->assertEquals(
			array(
				'test' => $arg->get_entity(),
				'another:test' => $arg_2->get_entity(),
			)
			, $args->get_stateful_args()
		);
	}

	/**
	 * Test checking if an event is repeatable when it has both types of args.
	 *
	 * @since 2.1.0
	 */
	public function test_is_repeatable_primary_and_stateful() {

		$this->factory->wordpoints->entity->create( array( 'slug' => 'test' ) );

		$arg = new WordPoints_PHPUnit_Mock_Hook_Arg( 'test' );
		$arg_2 = new WordPoints_PHPUnit_Mock_Hook_Arg( 'another:test' );

		$arg_2->is_stateful = true;

		$args = new WordPoints_Hook_Event_Args( array( $arg, $arg_2 ) );

		$this->assertFalse( $args->is_event_repeatable() );

		$this->assertEquals( $arg->get_entity(), $args->get_primary_arg() );
		$this->assertEquals(
			array( 'another:test' => $arg_2->get_entity() )
			, $args->get_stateful_args()
		);
	}

	/**
	 * Test checking if an event is repeatable when it has no args.
	 *
	 * @since 2.1.0
	 */
	public function test_is_repeatable_no_args() {

		$this->factory->wordpoints->entity->create();

		$args = new WordPoints_Hook_Event_Args( array() );

		$this->assertTrue( $args->is_event_repeatable() );
		$this->assertFalse( $args->get_primary_arg() );
		$this->assertSame( array(), $args->get_stateful_args() );
	}

	/**
	 * Test getting the entities in the hierarchy.
	 *
	 * @since 2.1.0
	 */
	public function test_get_entities() {

		$this->factory->wordpoints->entity->create( array( 'slug' => 'test' ) );

		$arg = new WordPoints_PHPUnit_Mock_Hook_Arg( 'test' );
		$arg_2 = new WordPoints_PHPUnit_Mock_Hook_Arg( 'another:test' );

		$args = new WordPoints_Hook_Event_Args( array( $arg, $arg_2 ) );

		$this->assertEquals(
			array(
				'test' => $arg->get_entity(),
				'another:test' => $arg_2->get_entity(),
			)
			, $args->get_entities()
		);
	}

	/**
	 * Test getting the entities in the hierarchy when there are none.
	 *
	 * @since 2.1.0
	 */
	public function test_get_entities_none() {

		$args = new WordPoints_Hook_Event_Args( array() );

		$this->assertEquals( array(), $args->get_entities() );
	}

	/**
	 * Test getting the validator.
	 *
	 * @since 2.1.0
	 */
	public function test_get_validator() {

		$args = new WordPoints_Hook_Event_Args( array() );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );

		$args->set_validator( $validator );

		$this->assertSame( $validator, $args->get_validator() );
	}

	/**
	 * Test getting the validator when there is none.
	 *
	 * @since 2.1.0
	 */
	public function test_get_validator_none() {

		$args = new WordPoints_Hook_Event_Args( array() );

		$this->assertNull( $args->get_validator() );
	}

	/**
	 * Test descending.
	 *
	 * @since 2.1.0
	 */
	public function test_descend() {

		$entity = $this->factory->wordpoints->entity->create_and_get(
			array( 'slug' => 'test' )
		);

		$entities = wordpoints_entities();
		$entities->get_sub_app( 'children' )->register(
			'test'
			, 'child'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$args = new WordPoints_Hook_Event_Args( array() );
		$args->add_entity( $entity );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );

		$args->set_validator( $validator );

		$this->assertNull( $args->get_current() );
		$this->assertEquals( array(), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->descend( 'test' ) );

		$this->assertEquals( $entity, $args->get_current() );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->descend( 'child' ) );

		$this->assertInstanceOf(
			'WordPoints_PHPUnit_Mock_Entity_Child'
			, $args->get_current()
		);

		$this->assertEquals(
			array( 'test', 'child' )
			, $validator->get_field_stack()
		);

		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->ascend() );

		$this->assertEquals( $entity, $args->get_current() );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->ascend() );

		$this->assertNull( $args->get_current() );
		$this->assertEquals( array(), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );
	}

	/**
	 * Test descending when no validator is set.
	 *
	 * @since 2.1.0
	 */
	public function test_descend_no_validator() {

		$entity = $this->factory->wordpoints->entity->create_and_get(
			array( 'slug' => 'test' )
		);

		$entities = wordpoints_entities();
		$entities->get_sub_app( 'children' )->register(
			'test'
			, 'child'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$args = new WordPoints_Hook_Event_Args( array() );
		$args->add_entity( $entity );

		$this->assertNull( $args->get_current() );

		$this->assertTrue( $args->descend( 'test' ) );

		$this->assertEquals( $entity, $args->get_current() );

		$this->assertTrue( $args->descend( 'child' ) );

		$this->assertInstanceOf(
			'WordPoints_PHPUnit_Mock_Entity_Child'
			, $args->get_current()
		);

		$this->assertTrue( $args->ascend() );

		$this->assertEquals( $entity, $args->get_current() );

		$this->assertTrue( $args->ascend() );

		$this->assertNull( $args->get_current() );
	}

	/**
	 * Test descending when the entity isn't part of the hierarchy.
	 *
	 * @since 2.1.0
	 */
	public function test_descend_not_entity() {

		$args = new WordPoints_Hook_Event_Args( array() );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$args->set_validator( $validator );

		$this->assertFalse( $args->descend( 'test' ) );

		$this->assertNull( $args->get_current() );
		$this->assertEquals( array(), $validator->get_field_stack() );
		$this->assertCount( 1, $validator->get_errors() );
	}

	/**
	 * Test descending when the current entity is not a parent.
	 *
	 * @since 2.1.0
	 */
	public function test_descend_not_parent() {

		$entity = $this->factory->wordpoints->entity->create_and_get(
			array( 'slug' => 'test' )
		);

		$entities = wordpoints_entities();
		$entities->get_sub_app( 'children' )->register(
			'test'
			, 'child'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$args = new WordPoints_Hook_Event_Args( array() );
		$args->add_entity( $entity );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$args->set_validator( $validator );

		$this->assertNull( $args->get_current() );
		$this->assertEquals( array(), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->descend( 'test' ) );

		$this->assertEquals( $entity, $args->get_current() );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertTrue( $args->descend( 'child' ) );

		$this->assertInstanceOf(
			'WordPoints_PHPUnit_Mock_Entity_Child'
			, $args->get_current()
		);

		$this->assertEquals( array( 'test', 'child' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertFalse( $args->descend( 'grandchild' ) );

		$this->assertInstanceOf(
			'WordPoints_PHPUnit_Mock_Entity_Child'
			, $args->get_current()
		);

		$this->assertEquals( array( 'test', 'child' ), $validator->get_field_stack() );
		$this->assertCount( 1, $validator->get_errors() );
	}

	/**
	 * Test descending when the child doesn't exist.
	 *
	 * @since 2.1.0
	 */
	public function test_descend_child_nonexistent() {

		$entity = new WordPoints_PHPUnit_Mock_Entity( 'test' );

		$args = new WordPoints_Hook_Event_Args( array() );
		$args->add_entity( $entity );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$args->set_validator( $validator );

		$this->assertTrue( $args->descend( 'test' ) );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertEquals( $entity, $args->get_current() );

		$this->assertFalse( $args->descend( 'child' ) );

		$this->assertEquals( $entity, $args->get_current() );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertCount( 1, $validator->get_errors() );
	}

	/**
	 * Test ascending when the hierarchy is empty.
	 *
	 * @since 2.1.0
	 *
	 * @expectedIncorrectUsage WordPoints_Entity_Hierarchy::ascend
	 */
	public function test_ascend_empty_hierarchy() {

		$args = new WordPoints_Hook_Event_Args( array() );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$args->set_validator( $validator );

		$validator->push_field( 'test' );

		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertFalse( $args->ascend() );

		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );
	}

	/**
	 * Test getting an entity from an array of slugs.
	 *
	 * @since 2.1.0
	 */
	public function test_get_from_hierarchy() {

		$entity = $this->factory->wordpoints->entity->create_and_get(
			array( 'slug' => 'test' )
		);

		$entities = wordpoints_entities();
		$children = $entities->get_sub_app( 'children' );

		$children->register(
			'test'
			, 'child'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$children->register(
			'test'
			, 'child_2'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$args = new WordPoints_Hook_Event_Args( array() );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$args->set_validator( $validator );

		$args->add_entity( $entity );
		$args->descend( 'test' );
		$args->descend( 'child' );

		$this->assertEquals( 'child', $args->get_current()->get_slug() );
		$this->assertEquals( array( 'test', 'child' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$from_hierarchy = $args->get_from_hierarchy(
			array( 'test', 'child_2' )
		);

		$this->assertEquals( 'child_2', $from_hierarchy->get_slug() );

		$this->assertEquals( array( 'test', 'child' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$this->assertEquals( 'child', $args->get_current()->get_slug() );

		$args->ascend();

		$this->assertEquals( $entity, $args->get_current() );
		$this->assertEquals( array( 'test' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );
	}

	/**
	 * Test getting an entity from an invalid array of slugs.
	 *
	 * @since 2.1.0
	 */
	public function test_get_from_hierarchy_invalid() {

		$entity = $this->factory->wordpoints->entity->create_and_get(
			array( 'slug' => 'test' )
		);

		$entities = wordpoints_entities();
		$entities->get_sub_app( 'children' )->register(
			'test'
			, 'child'
			, 'WordPoints_PHPUnit_Mock_Entity_Child'
		);

		$args = new WordPoints_Hook_Event_Args( array() );
		$args->add_entity( $entity );

		$validator = new WordPoints_Hook_Reaction_Validator( array() );
		$validator->push_field( 'field' );
		$args->set_validator( $validator );

		$this->assertEquals( array( 'field' ), $validator->get_field_stack() );
		$this->assertEquals( array(), $validator->get_errors() );

		$from_hierarchy = $args->get_from_hierarchy(
			array( 'test', 'child_2' )
		);

		$this->assertNull( $from_hierarchy );

		$this->assertEquals( array( 'field' ), $validator->get_field_stack() );

		$errors = $validator->get_errors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( array( 'field' ), $errors[0]['field'] );
	}
}

// EOF
