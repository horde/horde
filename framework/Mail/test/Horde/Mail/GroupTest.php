<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_GroupTest extends PHPUnit_Framework_TestCase
{
    public function testWriteAddress()
    {
        $addresses = array(
            'Test <test@example.com>',
            'foo@example.com'
        );
        $groupname = 'Testing';

        $group_ob = new Horde_Mail_Rfc822_Group($groupname, $addresses);

        $this->assertEquals(
            'Testing: Test <test@example.com>, foo@example.com;',
            $group_ob->writeAddress()
        );
    }

    public function testWriteAddressEncode()
    {
        $addresses = array(
            'Fooã <test@example.com>',
            'foo@example.com'
        );
        $groupname = 'Group "Foo"';

        $group_ob = new Horde_Mail_Rfc822_Group($groupname, $addresses);

        $this->assertEquals(
            '"Group \"Foo\"": =?utf-8?b?Rm9vw6M=?= <test@example.com>, foo@example.com;',
            $group_ob->writeAddress(array('encode' => true))
        );
    }

    public function testValid()
    {
        $group_ob = new Horde_Mail_Rfc822_Group();

        $this->assertTrue($group_ob->valid);

        $group_ob->groupname = '';

        $this->assertFalse($group_ob->valid);
    }

    public function testEmptyGroupCount()
    {
        $group_ob = new Horde_Mail_Rfc822_Group('Group');
        $this->assertEquals(
            0,
            count($group_ob)
        );
    }

    /**
     * @dataProvider encodingGroupnameProvider
     */
    public function testEncodingGroupname($in, $expected)
    {
        $group_ob = new Horde_Mail_Rfc822_Group($in);

        $this->assertEquals(
            $expected,
            $group_ob->groupname_encoded
        );
    }

    public function encodingGroupnameProvider()
    {
        return array(
            array('Foo', 'Foo'),
            array('Aäb', '=?utf-8?b?QcOkYg==?=')
        );
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch($compare, $result)
    {
        $ob = new Horde_Mail_Rfc822_Group(
            'Testing',
            array(
                'foo@example.com',
                'bar@example.com'
            )
        );

        if ($result) {
            $this->assertTrue($ob->match($compare));
        } else {
            $this->assertFalse($ob->match($compare));
        }
    }

    public function matchProvider()
    {
        return array(
            array(array('foo@example.com'), false),
            array(array('bar@example.com'), false),
            array(array('foo@example.com', 'bar@example.com'), true),
            array(array('bar@example.com', 'foo@example.com'), true)
        );
    }

}
