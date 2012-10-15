<?php
/**
 * Tests for the IMAP data format objects.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for the IMAP data format objects.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_DataFormatTest extends PHPUnit_Framework_TestCase
{
    public function testAstring()
    {
        $ob = new Horde_Imap_Client_Data_Format_Astring('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            'Foo',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        // Require quoting
        $ob = new Horde_Imap_Client_Data_Format_Astring('Foo(');

        $this->assertEquals(
            'Foo(',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo("',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        /* This is an invalid atom, but valid (non-quoted) astring. */
        $ob = new Horde_Imap_Client_Data_Format_Astring('Foo]');

        $this->assertEquals(
            'Foo]',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        /* Empty string should be quoted. */
        $ob = new Horde_Imap_Client_Data_Format_Astring('');

        $this->assertEquals(
            '""',
            $ob->escape()
        );

        $stream = $ob->escapeStream();

        $this->assertEquals(
            '""',
            stream_get_contents($stream, -1, 0)
        );
    }

    public function testAtom()
    {
        $ob = new Horde_Imap_Client_Data_Format_Atom('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            'Foo',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        // Illegal atom character
        $ob = new Horde_Imap_Client_Data_Format_Atom('Foo(');
        try {
            // Expecting exception.
            $ob->verify();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        $ob = new Horde_Imap_Client_Data_Format_Atom('');

        $this->assertEquals(
            '',
            strval($ob)
        );
        $this->assertEquals(
            '""',
            $ob->escape()
        );
    }

    public function testDate()
    {
        $date = new Horde_Imap_Client_DateTime('January 1, 2010');
        $ob = new Horde_Imap_Client_Data_Format_Date($date);

        $this->assertSame(
            $date,
            $ob->getData()
        );
        $this->assertEquals(
            '1-Jan-2010',
            strval($ob)
        );
        $this->assertEquals(
            '1-Jan-2010',
            $ob->escape()
        );

        $ob = new Horde_Imap_Client_Data_Format_Date('@1262304000');

        $this->assertEquals(
            '1-Jan-2010',
            strval($ob)
        );
    }

    public function testDateTime()
    {
        $date = new Horde_Imap_Client_DateTime('January 1, 2010');
        $ob = new Horde_Imap_Client_Data_Format_DateTime($date);

        $this->assertSame(
            $date,
            $ob->getData()
        );
        $this->assertEquals(
            '1-Jan-2010 00:00:00 +0000',
            strval($ob)
        );
        $this->assertEquals(
            '"1-Jan-2010 00:00:00 +0000"',
            $ob->escape()
        );

        $ob = new Horde_Imap_Client_Data_Format_DateTime('@1262304000');

        $this->assertEquals(
            '"1-Jan-2010 00:00:00 +0000"',
            $ob->escape()
        );
    }

    public function testList()
    {
        $ob = new Horde_Imap_Client_Data_Format_List();

        $this->assertEquals(
            0,
            count($ob)
        );

        $ob->add(new Horde_Imap_Client_Data_Format_Atom('Foo'));
        $ob->add(new Horde_Imap_Client_Data_Format_Atom('Bar'));
        $ob->add(new Horde_Imap_Client_Data_Format_String('Baz'));

        $this->assertEquals(
            3,
            count($ob)
        );

        $this->assertEquals(
            'Foo Bar "Baz"',
            strval($ob)
        );

        $this->assertEquals(
            'Foo Bar "Baz"',
            $ob->escape()
        );

        foreach ($ob as $key => $val) {
            switch ($key) {
            case 0:
            case 1:
                $this->assertEquals(
                    'Horde_Imap_Client_Data_Format_Atom',
                    get_class($val)
                );
                break;

            case 2:
                $this->assertEquals(
                    'Horde_Imap_Client_Data_Format_String',
                    get_class($val)
                );
                break;
            }
        }

        $ob = new Horde_Imap_Client_Data_Format_List('Foo');

        $this->assertEquals(
            1,
            count($ob)
        );

        $ob_array = iterator_to_array($ob);
        $this->assertEquals(
            'Horde_Imap_Client_Data_Format_Atom',
            get_class(reset($ob_array))
        );

        $ob->add(array(
            'Foo',
            new Horde_Imap_Client_Data_Format_List(array('Bar'))
        ));

        $this->assertEquals(
            3,
            count($ob)
        );

        $this->assertEquals(
            'Foo Foo (Bar)',
            $ob->escape()
        );

        $ob = new Horde_Imap_Client_Data_Format_List(array(
            'Foo',
            new Horde_Imap_Client_Data_Format_List(array(
                'Foo1'
            )),
            'Bar',
            new Horde_Imap_Client_Data_Format_List(array(
                new Horde_Imap_Client_Data_Format_String('Bar1'),
                new Horde_Imap_Client_Data_Format_List(array(
                    'Baz'
                ))
            ))
        ));

        $this->assertEquals(
            4,
            count($ob)
        );

        $this->assertEquals(
            'Foo (Foo1) Bar ("Bar1" (Baz))',
            $ob->escape()
        );
    }

    public function testListMailbox()
    {
        $ob = new Horde_Imap_Client_Data_Format_ListMailbox('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            'Foo',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        // Require quoting
        $ob = new Horde_Imap_Client_Data_Format_ListMailbox('Foo(');

        $this->assertEquals(
            'Foo(',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo("',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        $ob = new Horde_Imap_Client_Data_Format_ListMailbox('Foo]');

        $this->assertEquals(
            'Foo]',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        // Don't throw Exception
        $ob->verify();

        /* Don't escape either '*' or '%'. */
        $ob = new Horde_Imap_Client_Data_Format_ListMailbox('Foo%Bar');
        $this->assertEquals(
            'Foo%Bar',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        $ob = new Horde_Imap_Client_Data_Format_ListMailbox('Foo*Bar');
        $this->assertEquals(
            'Foo*Bar',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());
    }

    public function testMailbox()
    {
        $ob = new Horde_Imap_Client_Data_Format_Mailbox('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            'Foo',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());

        $ob = new Horde_Imap_Client_Data_Format_Mailbox('Foo(');

        $this->assertEquals(
            'Foo(',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo("',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        $ob = new Horde_Imap_Client_Data_Format_Mailbox('Envoyé');

        $this->assertEquals(
            'Envoyé',
            strval($ob)
        );
        $this->assertEquals(
            'Envoy&AOk-',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());
    }

    public function testNil()
    {
        $ob = new Horde_Imap_Client_Data_Format_Nil();

        $this->assertEquals(
            '',
            strval($ob)
        );
        $this->assertEquals(
            'NIL',
            $ob->escape()
        );
    }

    public function testNstring()
    {
        $ob = new Horde_Imap_Client_Data_Format_Nstring('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo"',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        // Require quoting
        $ob = new Horde_Imap_Client_Data_Format_Nstring('Foo(');

        $this->assertEquals(
            'Foo(',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo("',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        /* This is an invalid atom. */
        $ob = new Horde_Imap_Client_Data_Format_Nstring('Foo]');

        $this->assertEquals(
            '"Foo]"',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        $ob = new Horde_Imap_Client_Data_Format_Nstring();

        $this->assertEquals(
            '',
            strval($ob)
        );
        $this->assertEquals(
            'NIL',
            $ob->escape()
        );

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertFalse($ob->quoted());
    }

    public function testNumber()
    {
        $ob = new Horde_Imap_Client_Data_Format_Number(1);

        $this->assertEquals(
            '1',
            strval($ob)
        );
        $this->assertEquals(
            '1',
            $ob->escape()
        );
        $ob->verify();

        $ob = new Horde_Imap_Client_Data_Format_Number('1');

        $this->assertEquals(
            '1',
            strval($ob)
        );
        $this->assertEquals(
            '1',
            $ob->escape()
        );
        $ob->verify();

        $ob = new Horde_Imap_Client_Data_Format_Number('Foo');

        try {
            // Expected exception
            $ob->verify();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}
    }

    public function testString()
    {
        $ob = new Horde_Imap_Client_Data_Format_String('Foo');

        $this->assertEquals(
            'Foo',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo"',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        // Require quoting
        $ob = new Horde_Imap_Client_Data_Format_String('Foo(');

        $this->assertEquals(
            'Foo(',
            strval($ob)
        );
        $this->assertEquals(
            '"Foo("',
            $ob->escape()
        );

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        /* This is an invalid atom, but valid string. */
        $ob = new Horde_Imap_Client_Data_Format_String('Foo]');

        $this->assertEquals(
            '"Foo]"',
            $ob->escape()
        );

        $this->assertTrue(is_resource($ob->escapeStream()));

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertFalse($ob->literal());
        $this->assertTrue($ob->quoted());

        /* This string requires a literal. */
        $ob = new Horde_Imap_Client_Data_Format_String("Foo\n]");

        try {
            // Expected Exception
            $ob->escape();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        try {
            // Expected Exception
            $ob->escapeStream();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        // Don't throw Exception
        $ob->verify();

        $this->assertFalse($ob->binary());
        $this->assertTrue($ob->literal());
        $this->assertFalse($ob->quoted());

        /* This string requires a binary literal. */
        $ob = new Horde_Imap_Client_Data_Format_String("12\x00\n3");

        try {
            // Expected Exception
            $ob->escape();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        try {
            // Expected Exception
            $ob->escapeStream();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        // Don't throw Exception
        $ob->verify();

        $this->assertTrue($ob->binary());
        $this->assertTrue($ob->literal());
        $this->assertFalse($ob->quoted());
    }

}
