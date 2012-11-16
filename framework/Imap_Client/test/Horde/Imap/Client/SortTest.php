<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Tests for IMAP mailbox sorting.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_SortTest extends PHPUnit_Framework_TestCase
{
    public function testNumericComponentSorting()
    {
        $mboxes = array(
            'Foo.002',
            'Foo.00002',
            'Foo.0002'
        );

        $expected = array(
            'Foo.002',
            'Foo.0002',
            'Foo.00002'
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $list_ob->sort(array(
            'delimiter' => '.'
        ));

        $this->assertEquals(
            $expected,
            array_values(iterator_to_array($list_ob))
        );
    }

    public function testInboxSort()
    {
        $mboxes = array(
            'A',
            'INBOX'
        );
        $expected = array(
            'INBOX',
            'A'
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $sorted = $list_ob->sort(array(
            'inbox' => true,
        ));

        $this->assertEquals(
            $expected,
            array_values($sorted)
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $sorted = $list_ob->sort(array(
            'inbox' => false,
        ));

        $this->assertEquals(
            $mboxes,
            $sorted
        );
    }

    public function testIndexAssociation()
    {
        $mboxes = array(
            'Z' => 'Z',
            'A' => 'A'
        );
        $expected = array(
            'A',
            'Z'
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $sorted = $list_ob->sort();

        $this->assertEquals(
            $expected,
            array_values($sorted)
        );

        $this->assertEquals(
            $expected,
            array_keys($sorted)
        );
    }

    public function testNoUpdateOfListObject()
    {
        $mboxes = array(
            'Z',
            'A'
        );
        $expected = array(
            'A',
            'Z'
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $sorted = $list_ob->sort(array(
            'noupdate' => true
        ));

        $this->assertEquals(
            $expected,
            array_values($sorted)
        );
        $this->assertEquals(
            $mboxes,
            array_values(iterator_to_array($list_ob))
        );

        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $sorted = $list_ob->sort();

        $this->assertEquals(
            $expected,
            array_values($sorted)
        );
        $this->assertEquals(
            $expected,
            array_values(iterator_to_array($list_ob))
        );
    }

}
