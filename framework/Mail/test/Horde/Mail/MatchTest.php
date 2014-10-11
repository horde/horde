<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_MatchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider matchProvider
     */
    public function testMatch($in, $match, $expected)
    {
        $address = new Horde_Mail_Rfc822_Address($in);

        $this->assertEquals(
            $expected,
            $address->match($match)
        );
    }

    public function matchProvider()
    {
        $test1 = 'Test <test@example.com>';
        $test2 = 'Test <täst@example.com>';

        return array(
            array(
                $test1,
                'Foo <test@example.com>',
                true
            ),
            array(
                $test1,
                'Foo <test@EXAMPLE.COM>',
                true
            ),
            array(
                $test1,
                'Foo <Test@example.com>',
                false
            ),
            array(
                $test2,
                'Foo <test@example.com>',
                false
            ),
            array(
                $test2,
                'täst@example.com',
                true
            )
        );
    }

    /**
     * @dataProvider insensitiveMatchProvider
     */
    public function testInsensitiveMatch($in, $match, $expected)
    {
        $address = new Horde_Mail_Rfc822_Address($in);

        $this->assertEquals(
            $expected,
            $address->matchInsensitive($match)
        );
    }

    public function insensitiveMatchProvider()
    {
        $test1 = 'Test <test@example.com>';
        $test2 = 'Test <täst@example.com>';

        return array(
            array(
                $test1,
                'Foo <test@example.com>',
                true
            ),
            array(
                $test1,
                'Foo <test@EXAMPLE.COM>',
                true
            ),
            array(
                $test1,
                'Foo <Test@example.com>',
                true
            ),
            array(
                $test1,
                'test1@example.com',
                false
            ),
            array(
                $test2,
                'TäST@EXAMPLE.cOm',
                true
            )
        );
    }

}
