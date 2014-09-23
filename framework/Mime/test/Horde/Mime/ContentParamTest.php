<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_ContentParam class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2014 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_ContentParamTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider encodeProvider
     */
    public function testEncode($params, $opts, $expected)
    {
        $cp = new Horde_Mime_ContentParam($params);

        $this->assertEquals(
            $expected,
            $cp->encode($opts)
        );
    }

    public function encodeProvider()
    {
        return array(
            array(
                array(
                    'bar' => 'foo',
                    'test' => str_repeat('a', 100) . '.txt'
                ),
                array(
                    'broken_rfc2231' => true
                ),
                array(
                    'bar' => 'foo',
                    'test' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt',
                    'test*0' =>'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'test*1' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt'
                )
            ),
            array(
                array(
                    'bar' => 'foo',
                    'test' => str_repeat('a', 100) . '.txt'
                ),
                array(
                    'broken_rfc2231' => false
                ),
                array(
                    'bar' => 'foo',
                    'test*0' =>'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'test*1' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt'
                )
            ),
            array(
                array(
                    'foo' => "\x01"
                ),
                array(),
                array(
                    'foo' => "\"\x01\""
                )
            ),
            // Bug #12127 (part 1)
            array(
                array(
                    'foo' => 'test'
                ),
                array(
                    'broken_rfc2231' => true,
                    'charset' => 'UTF-16LE'
                ),
                array(
                    'foo' => 'test'
                )
            ),
            // Bug #12127 (part 2)
            array(
                array(
                    'foo' => 'ā'
                ),
                array(
                    'broken_rfc2231' => true,
                    'charset' => 'UTF-16LE'
                ),
                array(
                    'foo*' => "utf-16le''%01%01",
                    'foo' => '"=?utf-16le?b?AQE=?="'
                )
            )
        );
    }

    /**
     * @dataProvider decodeProvider
     */
    public function testDecode($in, $val_expected, $params_expected)
    {
        $cp = new Horde_Mime_ContentParam($in);

        $this->assertEquals(
            $val_expected,
            $cp->value
        );

        $this->assertEquals(
            $params_expected,
            $cp->params
        );
    }

    public function decodeProvider()
    {
        return array(
            array(
                'foo',
                'foo',
                array()
            ),
            array(
                'foo=bar',
                null,
                array(
                    'foo' => 'bar'
                )
            ),
            array(
                'test ; foo = bar ; baz = "goo"',
                'test',
                array(
                    'baz' => 'goo',
                    'foo' => 'bar'
                )
            ),
            array(
                'test ; foo*1=B; foo*0="A"; foo*3=D; foo*2="C";foo*5=F;' .
                'foo*4="E"; foo*7=H; foo*6="G"; foo*9=J; foo*8=I; foo*11=L; ' .
                'bar  =  Z  ;  foo*10=K;',
                'test',
                array(
                    'bar' => 'Z',
                    'foo' => 'ABCDEFGHIJKL'
                )
            ),
            // Bug #13587
            array(
                "multipart/mixed; boundary=\"EPOC32-8'4Lqb7RwmJkJ+8bx'NRLMC2SXt1Ls'Gfpd0RMtxgP6JQFKj\"",
                'multipart/mixed',
                array(
                    'boundary' => "EPOC32-8'4Lqb7RwmJkJ+8bx'NRLMC2SXt1Ls'Gfpd0RMtxgP6JQFKj"
                )
            ),
            // Adapted from Dovecot's src/lib-mail/test-rfc2231-parser.c
            array(
                "key4*=us-ascii''foo" .
                "; key*2=ba%" .
                "; key2*0=a" .
                "; key3*0*=us-ascii'en'xyz" .
                "; key*0=\"foo\"" .
                "; key2*1*=b%25" .
                "; key3*1=plop%" .
                "; key*1=baz",
                null,
                array(
                    'key' => 'foobazba%',
                    'key2' => 'ab%',
                    'key3' => 'xyzplop%',
                    'key4' => 'foo'
                )
            )
        );
    }

}
