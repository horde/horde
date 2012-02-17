<?php
/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

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
        $this->assertEquals(48, strlen(new Horde_Support_Guid()));
        $this->assertRegExp('/\d{14}\.[-_0-9a-zA-Z]{23}@localhost/', (string)new Horde_Support_Guid());
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
        $this->assertRegExp('/\d{14}\.prefix\.[-_0-9a-zA-Z]{23}@localhost/', (string)new Horde_Support_Guid(array('prefix' => 'prefix')));
    }
}
