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
 * Tests for the Horde_Mime_Headers_ContentParam_ContentDisposition class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_Headers_ContentDispositionTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parsingOfInputProvider
     */
    public function testParsingOfInput($input, $expected_val, $expected_params)
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentDisposition(
            'Content-Disposition',
            $input
        );

        $this->assertEquals(
            $expected_val,
            $ob->value
        );

        $params = $ob->params;
        ksort($params);

        $this->assertEquals(
            $expected_params,
            $params
        );
    }

    public function parsingOfInputProvider()
    {
        return array(
            array(
                'inline',
                'inline',
                array()
            ),
            array(
                '    INLINE',
                'inline',
                array()
            ),
            array(
                'attachment',
                'attachment',
                array()
            ),
            array(
                ' AtTaChMeNt   ',
                'attachment',
                array()
            ),
            array(
                'bogus',
                '',
                array()
            ),
            array(
                " iNLINe   ;   filename=\"foo\";\n bar=33;    size = 22",
                'inline',
                array(
                    'bar' => '33',
                    'filename' => 'foo',
                    'size' => 22
                )
            ),
            array(
                "attachMENT;Filename=\"foo\";bar=33;SIZE=\"22\"",
                'attachment',
                array(
                    'bar' => '33',
                    'filename' => 'foo',
                    'size' => 22
                )
            )
        );
    }

    /**
     * @dataProvider fullValueProvider
     */
    public function testFullValue($value, $params, $expected)
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentDisposition(
            'Content-Disposition',
            ''
        );

        $ob->setContentParamValue($value);
        foreach ($params as $key => $val) {
            $ob[$key] = $val;
        }

        $this->assertEquals(
            $expected,
            $ob->full_value
        );
    }

    public function fullValueProvider()
    {
        return array(
            array(
                'attachment',
                array('foo' => 'bar'),
                'attachment; foo=bar'
            ),
            array(
                'inline',
                array(
                    'Foo' => 'BAR',
                    'BAZ' => 345
                ),
                'inline; Foo=BAR; BAZ=345'
            ),
            array(
                '',
                array(
                    'Foo' => 'BAR'
                ),
                'attachment; Foo=BAR'
            ),
            array(
                'inline; foo=bar',
                array(),
                'inline'
            )
        );
    }

    public function testSerialize()
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentDisposition(
            'Content-Disposition',
            'inline; foo=bar;'
        );

        $ob2 = unserialize(serialize($ob));

        $this->assertEquals(
            'inline',
            $ob2->value
        );
        $this->assertEquals(
            array('foo' => 'bar'),
            $ob2->params
        );
    }

    public function testClone()
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentDisposition(
            'Content-Disposition',
            'inline; foo=bar;'
        );

        $ob2 = clone $ob;

        $ob->setContentParamValue('attachment');
        $ob['foo'] = 123;

        $this->assertEquals(
            'inline',
            $ob2->value
        );
        $this->assertEquals(
            array('foo' => 'bar'),
            $ob2->params
        );
    }

    /**
     * @dataProvider isDefaultProvider
     */
    public function testIsDefault($value, $is_default)
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentDisposition(
            'Content-Disposition',
            $value
        );

        if ($is_default) {
            $this->assertTrue($ob->isDefault());
        } else {
            $this->assertFalse($ob->isDefault());
        }
    }

    public function isDefaultProvider()
    {
        return array(
            array(
                '',
                true
            ),
            array(
                'attachment',
                false
            ),
            array(
                'attachment; foo=bar',
                false
            ),
            array(
                'inline',
                false
            ),
            array(
                'inline; foo=bar',
                false
            )
        );
    }

}
