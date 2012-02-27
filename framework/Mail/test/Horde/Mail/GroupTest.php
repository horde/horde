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
        $groupname = 'Testing';

        $group_ob = new Horde_Mail_Rfc822_Group($groupname, $addresses);

        $this->assertEquals(
            'Testing: =?utf-8?b?Rm9vw6M=?= <test@example.com>, foo@example.com;',
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

}
