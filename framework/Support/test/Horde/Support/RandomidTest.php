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
require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_RandomidTest extends PHPUnit_Framework_TestCase
{
    public function testLength()
    {
        $this->assertEquals(23, strlen(new Horde_Support_Randomid()));
    }

    public function testDuplicates()
    {
        $values = array();
        $cnt = 0;

        for ($i = 0; $i < 10000; ++$i) {
            $id = strval(new Horde_Support_Randomid());
            if (isset($values[$id])) {
                $cnt++;
            } else {
                $values[$id] = 1;
            }
        }

        $this->assertEquals(0, $cnt);
    }
}
