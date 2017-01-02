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
 * Tests for the Horde_Mime_Headers_ContentParam_ContentType class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_Headers_ContentTypeTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parsingOfInputProvider
     */
    public function testParsingOfInput($input, $expected_val, $expected_params)
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentType(
            'Content-Type',
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
                'text/plain',
                'text/plain',
                array()
            ),
            array(
                '    TEXT/PLAIN',
                'text/plain',
                array()
            ),
            array(
                ' modEL/hTmL   ',
                'model/html',
                array()
            ),
            array(
                'bogus/foo',
                'x-bogus/foo',
                array()
            ),
            array(
                " message/RFC822   ;   Filename=\"foo\";\n BAR=33 ; foo  = 22",
                'message/rfc822',
                array(
                    'bar' => '33',
                    'filename' => 'foo',
                    'foo' => '22'
                )
            ),
            array(
                'foo',
                Horde_Mime_Headers_ContentParam_ContentType::DEFAULT_CONTENT_TYPE,
                array()
            )
        );
    }

    public function testClone()
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentType(
            'Content-Type',
            'text/plain; foo=bar;'
        );

        $ob2 = clone $ob;

        $ob->setContentParamValue('image/jpeg');
        $ob['foo'] = 123;

        $this->assertEquals(
            'text/plain',
            $ob2->value
        );
        $this->assertEquals(
            array('foo' => 'bar'),
            $ob2->params
        );
    }

    public function testSerialize()
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentType(
            'Content-Type',
            'text/plain; foo=bar;'
        );

        $ob2 = unserialize(serialize($ob));

        $this->assertEquals(
            'text/plain',
            $ob2->value
        );
        $this->assertEquals(
            array('foo' => 'bar'),
            $ob2->params
        );
    }

    public function testStaticCreateMethod()
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();

        $this->assertEquals(
            $ob::DEFAULT_CONTENT_TYPE,
            $ob->value
        );

        $this->assertEmpty($ob->params);
    }

    public function testParamsReturnsACopyOfArray()
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob['foo'] = 'bar';

        $params = $ob->params;

        $params['foo'] = '123';

        $this->assertEquals(
            array('foo' => 'bar'),
            $ob->params
        );
    }

    /**
     * @dataProvider parsingContentTypeValueProvider
     */
    public function testParsingContentTypeValue($value, $primary, $sub)
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob->setContentParamValue($value);

        $this->assertEquals(
            $primary,
            $ob->ptype
        );
        $this->assertEquals(
            $sub,
            $ob->stype
        );
    }

    public function parsingContentTypeValueProvider()
    {
        return array(
            array(
                'text/plain',
                'text',
                'plain'
            ),
            array(
                'TEXT/HTML',
                'text',
                'html'
            ),
            array(
                'foo/bar',
                'x-foo',
                'bar'
            ),
            array(
                'text/plain; charset=utf-8',
                'text',
                'plain'
            )
        );
    }

    /**
     * @dataProvider typeCharsetPropertyProvider
     */
    public function testTypeCharsetProperty($value, $charset, $expected)
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob->setContentParamValue($value);
        $ob['charset'] = $charset;

        $this->assertEquals(
            $expected,
            $ob->type_charset
        );
    }

    public function typeCharsetPropertyProvider()
    {
        return array(
            array(
                'text/plain',
                'utf-8',
                'text/plain; charset=utf-8'
            ),
            array(
                'text/html',
                'utf-8',
                'text/html; charset=utf-8'
            ),
            array(
                'image/jpeg',
                'utf-8',
                'image/jpeg'
            )
        );
    }

    /**
     * @dataProvider multipartPartsHaveBoundary
     */
    public function testMultipartPartsHaveBoundary($value, $has_boundary)
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob->setContentParamValue($value);

        if ($has_boundary) {
            $this->assertArrayHasKey('boundary', $ob->params);
        } else {
            $this->assertArrayNotHasKey('boundary', $ob->params);
        }
    }

    public function multipartPartsHaveBoundary()
    {
        return array(
            array(
                'text/plain',
                false
            ),
            array(
                'image/jpeg',
                false
            ),
            array(
                'multipart/mixed',
                true
            )
        );
    }

    /**
     * @dataProvider charsetIsLowercaseProvider
     */
    public function testCharsetIsLowercase($charset, $expected)
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob->setContentParamValue('text/plain');
        $ob['charset'] = $charset;

        $this->assertEquals(
            $expected,
            $ob['charset']
        );
    }

    public function charsetIsLowercaseProvider()
    {
        return array(
            array(
                'utf-8',
                'utf-8'
            ),
            array(
                'ISO-8859-1',
                'iso-8859-1'
            ),
            array(
                'US-ASCII',
                null
            )
        );
    }

    public function testMultipartCantUnsetBoundary()
    {
        $ob = Horde_Mime_Headers_ContentParam_ContentType::create();
        $ob->setContentParamValue('multipart/mixed');

        $this->assertNotEmpty($ob['boundary']);

        unset($ob['boundary']);

        $this->assertNotEmpty($ob['boundary']);
    }

    /**
     * @dataProvider isDefaultProvider
     */
    public function testIsDefault($value, $is_default)
    {
        $ob = new Horde_Mime_Headers_ContentParam_ContentType(
            'Content-Type',
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
                'text/plain',
                true
            ),
            array(
                'text/plain; charset=us-ascii',
                true
            ),
            array(
                'text/plain; charset=us-ascii; foo=bar',
                false
            ),
            array(
                'text/plain; charset=utf-8',
                false
            ),
            array(
                'text/html',
                false
            ),
            array(
                'text/html; charset=us-ascii',
                false
            ),
            array(
                'image/jpeg',
                false
            ),
            array(
                'image/jpeg; charset=utf-8',
                false
            )
        );
    }

}
