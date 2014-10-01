<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_AddressTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider domainMatchProvider
     */
    public function testDomainMatch($addr, $tests)
    {
        $address = new Horde_Mail_Rfc822_Address($addr);

        foreach ($tests as $val) {
            $match = $address->matchDomain($val[0]);
            if ($val[1]) {
                $this->assertTrue($match);
            } else {
                $this->assertFalse($match);
            }
        }
    }

    public function domainMatchProvider()
    {
        return array(
            array(
                'Test <test@example.com>',
                array(
                    array('example.com', true),
                    array('foo.example.com', false)
                )
            ),
            array(
                'Test <test@foo.example.com>',
                array(
                    array('example.com', true),
                    array('foo.example.com', true)
                )
            ),
            array(
                'Test <test@example.co.uk>',
                array(
                    array('example.co.uk', true),
                    array('foo.example.co.uk', false),
                    array('co.uk', true)
                )
            ),
            array(
                'Test <test@foo.example.co.uk>',
                array(
                    array('example.co.uk', true),
                    array('foo.example.co.uk', true),
                    array('co.uk', true)
                )
            )
        );
    }

    /**
     * @dataProvider personalIsSameAsEmailProvider
     */
    public function testPersonalIsSameAsEmail($addr, $expected)
    {
        $address = new Horde_Mail_Rfc822_Address($addr);

        $this->assertEquals(
            $expected,
            strval($address)
        );
    }

    public function personalIsSameAsEmailProvider()
    {
        return array(
            array(
                '"test@example.com" <test@example.com>',
                'test@example.com'
            ),
            array(
                '"TEST@EXAMPLE.COM" <test@example.com>',
                'test@example.com'
            )
        );
    }

    /**
     * @dataProvider labelProvider
     */
    public function testLabel($in, $expected)
    {
        $address = new Horde_Mail_Rfc822_Address($in);

        $this->assertEquals(
            $expected,
            $address->label
        );
    }

    public function labelProvider()
    {
        return array(
            array('foo@example.com', 'foo@example.com'),
            array('Foo <foo@example.com>', 'Foo')
        );
    }

    /**
     * @dataProvider personalEncodedProvider
     */
    public function testPersonalEncoded($in, $expected)
    {
        $address = new Horde_Mail_Rfc822_Address($in);

        $this->assertEquals(
            $expected,
            $address->personal_encoded
        );
    }

    public function personalEncodedProvider()
    {
        return array(
            array('Foo <foo@example.com>', 'Foo'),
            array('Aäb <bar@example.com>', '=?utf-8?b?QcOkYg==?=')
        );
    }

}
