<?php
/**
 * Tests for Horde_Registry.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Registry
 */

/**
 * Tests for Horde_Registry.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Registry
 */
class Horde_Core_RegistryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __DIR__ . '/Stub/Registry.php';
    }

    public function testBug10381()
    {
        $a1 = array(
            'conf' => array(
                'foo' => 'a',
                'bar' => 'b',
                'foobar' => array(
                    'a', 'b', 'c'
                ),
                'foobar2' => array(
                    'a' => 1,
                    'b' => 2
                )
            ),
            'a1_only' => array(
                'a' => 1,
                'b' => array(
                    'c' => 2
                )
            )
        );

        $a2 = array(
            'conf' => array(
                'bar' => 'c',
                'baz' => 'g',
                'foobar' => array(
                    'd', 'e'
                ),
                'foobar2' => array(
                    'a' => 3,
                    'c' => 4
                )
            ),
            'a2_only' => array(
                'a' => 1,
                'b' => array(
                    'c' => 2
                )
            )
        );

        $ob = new Horde_Core_Stub_Registry();

        $this->assertEquals(
            array(
                'conf' => array(
                    'foo' => 'a',
                    'bar' => 'c',
                    'baz' => 'g',
                    'foobar' => array(
                        'd', 'e'
                    ),
                    'foobar2' => array(
                        'a' => 3,
                        'b' => 2,
                        'c' => 4
                    )
                ),
                'a1_only' => array(
                    'a' => 1,
                    'b' => array(
                    'c' => 2
                    )
                ),
                'a2_only' => array(
                    'a' => 1,
                    'b' => array(
                        'c' => 2
                    )
                )
            ),
            $ob->mergeConfig($a1, $a2)
        );
    }

}
