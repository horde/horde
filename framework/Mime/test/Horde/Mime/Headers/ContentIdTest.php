<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Headers_ContentId class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_Headers_ContentIdTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider valueProvider
     */
    public function testValue($input, $expected_val)
    {
        $ob = new Horde_Mime_Headers_ContentId(null, $input);

        $this->assertEquals(
            $expected_val,
            $ob->value
        );

        $this->assertFalse($ob->isDefault());
    }

    public function valueProvider()
    {
        return array(
            array(
                'foo',
                '<foo>'
            ),
            array(
                '<foo>',
                '<foo>'
            ),
            array(
                '<<foo',
                '<foo>'
            )
        );
    }

    public function testClone()
    {
        $ob = new Horde_Mime_Headers_ContentId(null, 'foo');

        $ob2 = clone $ob;

        $ob->setValue('bar');

        $this->assertEquals(
            '<foo>',
            $ob2->value
        );
    }

    public function testSerialize()
    {
        $ob = new Horde_Mime_Headers_ContentId(null, 'foo');

        $ob2 = unserialize(serialize($ob));

        $this->assertEquals(
            '<foo>',
            $ob2->value
        );
    }

    public function testStaticCreateMethod()
    {
        $ob = Horde_Mime_Headers_ContentId::create();

        $this->assertStringMatchesFormat(
            '<%s@%a>',
            $ob->value
        );
    }

}
