<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
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
            ($result[0]->personal == 'Test Student') &&
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

        $this->assertInternalType('array', $result);
        $this->assertInternalType('object', $result[0]);
        $this->assertEquals($result[0]->personal, '');
        $this->assertInternalType('array', $result[0]->comment);
        $this->assertEquals($result[0]->comment, array());
        $this->assertEquals($result[0]->mailbox, 'user');
        $this->assertEquals($result[0]->host, 'example.com');

        /* Address groups. */
        $address = 'My Group: "Richard" <richard@localhost> (A comment), ted@example.com (Ted Bloggs), Barney;';
        $result = $parser->parseAddressList($address, array(
            'default_domain' => null
        ));

        $this->assertInternalType('array', $result);
        $this->assertInternalType('object', $result[0]);
        $this->assertEquals($result[0]->groupname, 'My Group');
        $this->assertInternalType('array', $result[0]->addresses);

        $this->assertInternalType('object', $result[0]->addresses[0]);
        $this->assertEquals($result[0]->addresses[0]->personal, 'Richard');
        $this->assertInternalType('array', $result[0]->addresses[0]->comment);
        $this->assertEquals($result[0]->addresses[0]->comment[0], 'A comment');
        $this->assertEquals($result[0]->addresses[0]->mailbox, 'richard');
        $this->assertEquals($result[0]->addresses[0]->host, 'localhost');

        $this->assertInternalType('object', $result[0]->addresses[1]);
        $this->assertEquals($result[0]->addresses[1]->personal, '');
        $this->assertInternalType('array', $result[0]->addresses[1]->comment);
        $this->assertEquals($result[0]->addresses[1]->comment[0], 'Ted Bloggs');
        $this->assertEquals($result[0]->addresses[1]->mailbox, 'ted');
        $this->assertEquals($result[0]->addresses[1]->host, 'example.com');

        $this->assertInternalType('object', $result[0]->addresses[2]);
        $this->assertEquals($result[0]->addresses[2]->personal, '');
        $this->assertInternalType('array', $result[0]->addresses[2]->comment);
        $this->assertEquals($result[0]->addresses[2]->comment, array());
        $this->assertEquals($result[0]->addresses[2]->mailbox, 'Barney');
        $this->assertEquals($result[0]->addresses[2]->host, 'localhost');

        /* A valid address with spaces in the local part. */
        $address = '<"Jon Parise"@php.net>';
        $result = $parser->parseAddressList($address, array(
            'default_domain' => null
        ));

        $this->assertInternalType('array', $result);
        $this->assertInternalType('object', $result[0]);
        $this->assertEquals($result[0]->personal, '');
        $this->assertInternalType('array', $result[0]->comment);
        $this->assertEquals($result[0]->comment, array());
        $this->assertEquals($result[0]->mailbox, 'Jon Parise');
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
        $this->assertInternalType('array', $res);
        $this->assertEquals(count($res), 3);
    }

    public function testBug9525()
    {
        $parser = new Horde_Mail_Rfc822();

        try {
            $ob = $parser->parseAddressList(
                'ß <test@example.com>',
                array(
                    'default_domain' => 'example.com'
                )
            );

            $this->fail('Expecting Exception.');
        } catch (Horde_Mail_Exception $e) {}

        /* This technically shouldn't validate, but the parser is very liberal
         * about accepting characters within quotes. */
        $ob = $parser->parseAddressList(
            '"ß" <test@example.com>',
            array(
                'default_domain' => 'example.com'
            )
        );
    }

    public function testBug10534()
    {
        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList('');

        $this->assertEquals(
            0,
            count($ob)
        );
    }

    public function testNoValidation()
    {
        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList(
            '"ß" <test@example.com>',
            array(
                'default_domain' => 'example.com',
                'validate' => false
            )
        );

        $this->assertEquals(
            'ß',
            $ob[0]->personal
        );

        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList(
            'ß ß <test@example.com>',
            array(
                'default_domain' => 'example.com',
                'validate' => false
            )
        );

        $this->assertEquals(
            'ß ß',
            $ob[0]->personal
        );
    }

    public function testLimit()
    {
        $email = array_fill(0, 10, 'A <example.com>');

        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList(
            implode(', ', $email),
            array(
                'limit' => 5
            )
        );

        $this->assertEquals(
            5,
            count($ob)
        );
    }

    public function testLargeParse()
    {
        $email = array_fill(0, 1000, 'A <foo@example.com>, "A B" <foo@example.com>, foo@example.com, Group: A <foo@example.com>;, Group2: "A B" <foo@example.com>;');

        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList(
            implode(', ', $email)
        );

        $this->assertEquals(
            5000,
            count($ob)
        );
    }

    public function testArrayAccess()
    {
        $parser = new Horde_Mail_Rfc822();
        $ob = $parser->parseAddressList(
            'A <test@example.com>',
            array(
                'default_domain' => 'example.com',
                'validate' => false
            )
        );

        $this->assertEquals(
            'A',
            $ob[0]['personal']
        );

        $this->assertEquals(
            'example.com',
            $ob[0]['host']
        );

        $this->assertTrue(
            isset($ob[0]['mailbox'])
        );

        $this->assertFalse(
            isset($ob[0]['bar'])
        );
    }

}
