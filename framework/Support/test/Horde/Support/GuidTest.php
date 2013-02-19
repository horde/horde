<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
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
class Horde_Support_GuidTest extends PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $guid = new Horde_Support_Guid(array('server' => 'localhost'));
        $this->assertEquals(48, strlen($guid));
        $this->assertRegExp('/\d{14}\.[-_0-9a-zA-Z]{23}@localhost/', (string)$guid);
    }

    public function testDuplicates()
    {
        $values = array();
        $cnt = 0;

        for ($i = 0; $i < 10000; ++$i) {
            $id = strval(new Horde_Support_Guid());
            if (isset($values[$id])) {
                $cnt++;
            } else {
                $values[$id] = 1;
            }
        }

        $this->assertEquals(0, $cnt);
    }

    public function testOptions()
    {
        $this->assertStringEndsWith('example.com', (string)new Horde_Support_Guid(array('server' => 'example.com')));
        $this->assertRegExp('/\d{14}\.prefix\.[-_0-9a-zA-Z]{23}@localhost/', (string)new Horde_Support_Guid(array('prefix' => 'prefix', 'server' => 'localhost')));
    }
}
