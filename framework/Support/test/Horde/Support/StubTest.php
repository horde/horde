<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_StubTest extends PHPUnit_Framework_TestCase
{
    public function testAnyOffsetIsGettable()
    {
        $stub = new Horde_Support_Stub;
        unset($php_errormsg);
        $oldTrackErrors = ini_set('track_errors', 1);
        $php_errormsg = null;
        $this->assertNull($stub->{uniqid()});
        $this->assertNull($php_errormsg);
    }

    public function testAnyMethodIsCallable()
    {
        $stub = new Horde_Support_Stub;
        $this->assertTrue(is_callable(array($stub, uniqid())));
        $this->assertNull($stub->{uniqid()}());
    }

    public function testAnyStaticMethodIsCallable()
    {
        $this->assertTrue(is_callable(array('Horde_Support_Stub', uniqid())));
        $unique = uniqid();
        $this->assertNull(Horde_Support_Stub::$unique());
    }

    public function testToString()
    {
        $this->assertEquals('', (string)new Horde_Support_Stub());
    }

    public function testArrayAccess()
    {
        $stub = new Horde_Support_Stub();

        // Set
        $stub['foo'] = 'bar';

        // Isset
        $this->assertFalse(isset($stub['foo']));

        // Get
        $this->assertNull($stub['foo']);

        // Count
        $this->assertEquals(
            0,
            count($stub)
        );

        // Iteration
        $this->assertEmpty(iterator_to_array($stub));

        // Unset
        unset($stub['foo']);
    }

}
