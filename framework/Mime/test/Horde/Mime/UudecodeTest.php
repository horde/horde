<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Uudecode class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_UudecodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider uudecodeProvider
     */
    public function testUudecode($data, $expected)
    {
        $uudecode = new Horde_Mime_Uudecode($data);

        $this->assertEquals(
            count($expected),
            count($uudecode)
        );

        $res = iterator_to_array($uudecode);

        foreach ($expected as $key => $val) {
            foreach (array('data', 'name', 'perm') as $key2 => $val2) {
                $this->assertArrayHasKey($val2, $res[$key]);
                $this->assertEquals(
                    $val[$key2],
                    $res[$key][$val2]
                );
            }
        }
    }

    public function uudecodeProvider()
    {
        return array(
            array(
                file_get_contents(__DIR__ . '/fixtures/uudecode.txt'),
                array(
                    array('Test string', 'test.txt', 644),
                    array('2nd string', 'test2.txt', 755)
                )
            )
        );
    }

}
