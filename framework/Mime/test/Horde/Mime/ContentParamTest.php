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
 * Tests for the Horde_Mime_Headers_ContentParam class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
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
        $cp = new Horde_Mime_Headers_ContentParam('NOT_USED', $params);

        ksort($expected);
        $params = $cp->encode($opts);
        ksort($params);

        $this->assertEquals(
            $expected,
            $params
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
        $cp = new Horde_Mime_Headers_ContentParam('NOT_USED', $in);

        $this->assertEquals(
            $val_expected,
            $cp->value
        );


        ksort($params_expected);
        $params = $cp->params;
        ksort($params);

        $this->assertEquals(
            $params_expected,
            $params
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
            array(
                "attachment; size=147502;\n filename*=utf-8''Factura%20n%C2%BA%2010.pdf",
                'attachment',
                array(
                    'size' => '147502',
                    'filename' => 'Factura nº 10.pdf'
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
            // Gmail
            array(
                // Content-Disposition
                "attachment;\n filename=\"=?UTF-8?Q?Vantagens_da_Caixa_para_Empresas_e_alterac=CC=A7a=CC=83o_do_prec=CC=A7?=\n =?UTF-8?Q?a=CC=81rio=2Epdf?=\"",
                'attachment',
                array(
                    'filename' => 'Vantagens da Caixa para Empresas e alteração do preçário.pdf'
                )
            ),
            array(
                // Content-Type
                "application/pdf;\nname=\"=?UTF-8?Q?Vantagens_da_Caixa_para_Empresas_e_alterac=CC=A7a=CC=83o_do_prec=CC=A7?=\n =?UTF-8?Q?a=CC=81rio=2Epdf?=\"",
                'application/pdf',
                array(
                    'name' => 'Vantagens da Caixa para Empresas e alteração do preçário.pdf'
                )
            ),
            // mail.app
            // Note: filename/name parameter value is NOT the same UTF-8
            // string as the Gmail examples above; character length is the
            // same, but Gmail byte-length is 4 bytes longer (they are using
            // different Unicode points to display the 4 non-ASCII chars)
            array(
                // Content-Disposition
                "inline;\n filename*=iso-8859-1''Vantagens%20da%20Caixa%20para%20Empresas%20e%20altera%E7%E3o%20do%20pre%E7%E1rio.pdf",
                'inline',
                array(
                    'filename' => 'Vantagens da Caixa para Empresas e alteração do preçário.pdf'
                )
            ),
            array(
                // Content-Type
                "application/pdf;\n name=\"=?iso-8859-1?Q?Vantagens_da_Caixa_para_Empresas_e_altera=E7=E3o_?=\n =?iso-8859-1?Q?do_pre=E7=E1rio=2Epdf?=\"",
                'application/pdf',
                array(
                    'name' => 'Vantagens da Caixa para Empresas e alteração do preçário.pdf'
                )
            ),
            // Params with different cases (params are case-insensitive)
            array(
                "kEy*1=b; KEY*0=a; key*2=c",
                null,
                array(
                    'key' => 'abc'
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
