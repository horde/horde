<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
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
class Horde_Support_UuidTest extends PHPUnit_Framework_TestCase
{
    public function testLength()
    {
        $this->assertEquals(36, strlen(new Horde_Support_Uuid()));
    }

    public function testDuplicates()
    {
        $values = array();

        for ($i = 0; $i < 10000; ++$i) {
            $id = strval(new Horde_Support_Uuid());
            $this->assertArrayNotHasKey($id, $values);
            $values[$id] = 1;
        }
    }
}
