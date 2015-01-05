<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/license/bsd.
 *
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    xxhash
 * @subpackage UnitTests
 */

/**
 * Tests for the horde_xxhash extension.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    xxhash
 * @subpackage UnitTests
 */
class horde_xxhash_UnitTest extends PHPUnit_Framework_TestCase
{
    private $data;

    public function setUp()
    {
        if (!extension_loaded('horde_xxhash')) {
            $this->markTestSkipped('horde_xxhash extension not installed.');
        }
    }

    public function testXxhash()
    {
        $data = array(
            '123' => 'b6855437',
            '1234' => '01543429',
            'ABC' => '80712ed5',
            'ABCD' => 'aa960ca6'
        );

        foreach ($data as $key => $val) {
            $this->assertEquals(
                $val,
                horde_xxhash($key)
            );
        }
    }

}
