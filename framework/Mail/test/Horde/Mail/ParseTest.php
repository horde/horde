<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_ParseTest extends PHPUnit_Framework_TestCase
{
    /* Test case for PEAR Mail:: bug #13659 */
    public function testParseBug13659()
    {
        $address = '"Test Student" <test@mydomain.com> (test)';

        $parser = new Horde_Mail_Rfc822();
        $result = $parser->parseAddressList($address, array(
           'default_domain' => 'anydomain.com'
        ));

        $this->assertTrue(is_array($result) &&
            is_object($result[0]) &&
            ($result[0]->personal == '"Test Student"') &&
            ($result[0]->mailbox == "test") &&
            ($result[0]->host == "mydomain.com") &&
            is_array($result[0]->comment) &&
            ($result[0]->comment[0] == 'test'));
    }

    /* Test case for PEAR Mail:: bug #9137 */
    public function testParseBug9137()
    {
        $addresses = array(
            array('name' => 'John Doe', 'email' => 'test@example.com'),
            array('name' => 'John Doe\\', 'email' => 'test@example.com'),
            array('name' => 'John "Doe', 'email' => 'test@example.com'),
            array('name' => 'John "Doe\\', 'email' => 'test@example.com'),
        );

        $parser = new Horde_Mail_Rfc822();

        foreach ($addresses as $val) {
            $address =
                '"' . addslashes($val['name']) . '" <' . $val['email'] . '>';

            /* Throws Exception on error. */
            $parser->parseAddressList($address);
        }
    }

    /* Test case for PEAR Mail:: bug #9137, take 2 */
    public function testParseBug9137Take2()
    {
        $addresses = array(
            array(
                'raw' => '"John Doe" <test@example.com>'
            ),
            array(
                'raw' => '"John Doe' . chr(92) . '" <test@example.com>',
                'fail' => true
            ),
            array(
                'raw' => '"John Doe' . chr(92) . chr(92) . '" <test@example.com>'
            ),
            array(
                'raw' => '"John Doe' . chr(92) . chr(92) . chr(92) . '" <test@example.com>',
                'fail' => true
            ),
            array(
                'raw' => '"John Doe' . chr(92) . chr(92) . chr(92) . chr(92) . '" <test@example.com>'
            ),
            array(
                'raw' => '"John Doe <test@example.com>',
                'fail' => true
            )
        );

        $parser = new Horde_Mail_Rfc822();

        foreach ($addresses as $val) {
            try {
                $parser->parseAddressList($val['raw']);
                if (!empty($val['fail'])) {
                    $this->fail('An expected exception was not raised.');
                }
            } catch (Horde_Mail_Exception $e) {
                if (empty($val['fail'])) {
                    $this->fail('An unexpected exception was raised.');
                }
            }
        }
    }

    public function testGeneralParsing()
    {
        $parser = new Horde_Mail_Rfc822();

        /* A simple, bare address. */
        $address = 'user@example.com';
        $result = $parser->parseAddressList($address, array(
            'default_domain' => null
        ));

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]);
        $this->assertEquals($result[0]->personal, '');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->comment);
        $this->assertEquals($result[0]->comment, array());
        $this->assertEquals($result[0]->mailbox, 'user');
        $this->assertEquals($result[0]->host, 'example.com');

        /* Address groups. */
        $address = 'My Group: "Richard" <richard@localhost> (A comment), ted@example.com (Ted Bloggs), Barney;';
        $result = $parser->parseAddressList($address, array(
            'default_domain' => null
        ));

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]);
        $this->assertEquals($result[0]->groupname, 'My Group');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->addresses);

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]->addresses[0]);
        $this->assertEquals($result[0]->addresses[0]->personal, '"Richard"');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->addresses[0]->comment);
        $this->assertEquals($result[0]->addresses[0]->comment[0], 'A comment');
        $this->assertEquals($result[0]->addresses[0]->mailbox, 'richard');
        $this->assertEquals($result[0]->addresses[0]->host, 'localhost');

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]->addresses[1]);
        $this->assertEquals($result[0]->addresses[1]->personal, '');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->addresses[1]->comment);
        $this->assertEquals($result[0]->addresses[1]->comment[0], 'Ted Bloggs');
        $this->assertEquals($result[0]->addresses[1]->mailbox, 'ted');
        $this->assertEquals($result[0]->addresses[1]->host, 'example.com');

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]->addresses[2]);
        $this->assertEquals($result[0]->addresses[2]->personal, '');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->addresses[2]->comment);
        $this->assertEquals($result[0]->addresses[2]->comment, array());
        $this->assertEquals($result[0]->addresses[2]->mailbox, 'Barney');
        $this->assertEquals($result[0]->addresses[2]->host, 'localhost');

        /* A valid address with spaces in the local part. */
        $address = '<"Jon Parise"@php.net>';
        $result = $parser->parseAddressList($address, array(
            'default_domain' => null
        ));

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $result[0]);
        $this->assertEquals($result[0]->personal, '');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result[0]->comment);
        $this->assertEquals($result[0]->comment, array());
        $this->assertEquals($result[0]->mailbox, '"Jon Parise"');
        $this->assertEquals($result[0]->host, 'php.net');

        /* An invalid address with spaces in the local part. */
        $address = '<Jon Parise@php.net>';
        try {
            $parser->parseAddressList($address, array(
                'default_domain' => null
            ));
            $this->fail('An expected exception was not raised.');
        } catch (Horde_Mail_Exception $e) {}

        /* A valid address with an uncommon TLD. */
        $address = 'jon@host.longtld';
        try {
            $parser->parseAddressList($address, array(
                'default_domain' => null
            ));
        } catch (Horde_Mail_Exception $e) {
            $this->fail('An unexpected exception was raised.');
        }
    }

    public function testValidateQuotedString()
    {
        $address_string = '"Joe Doe \(from Somewhere\)" <doe@example.com>, postmaster@example.com, root';

        $parser = new Horde_Mail_Rfc822();

        $res = $parser->parseAddressList($address_string, array(
            'default_domain' => 'example.com'
        ));
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $res);
        $this->assertEquals(count($res), 3);
    }

}
