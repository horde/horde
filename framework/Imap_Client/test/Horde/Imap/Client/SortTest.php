<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2012-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for IMAP mailbox sorting.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2012-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_SortTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider numericComponentSortingProvider
     */
    public function testNumericComponentSorting($mboxes, $expected)
    {
        $list_ob = new Horde_Imap_Client_Mailbox_List($mboxes);
        $list_ob->sort(array(
            'delimiter' => '.'
        ));

        $this->assertEquals(
            $expected,
            array_values(iterator_to_array($list_ob))
        );
    }

    public function numericComponentSortingProvider()
    {
        return array(
            array(
                array(
                    '100',
                    '1',
                    '10000',
                    '10',
                    '1000'
                ),
                array(
                    '1',
                    '10',
                    '100',
                    '1000',
                    '10000'
                )
            ),
            array(
                array(
                    'Foo.002',
                    'Foo.00002',
                    'Foo.0002'
                ),
                array(
                    'Foo.002',
                    'Foo.0002',
                    'Foo.00002'
                )
            )
        );
    }

    /**
     * @dataProvider inboxSortProvider
     */
    public function testInboxSort($mboxes, $expected)
    {
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

    public function inboxSortProvider()
    {
        return array(
            array(
                array(
                    'A',
                    'Z',
                    'INBOX',
                    'C'
                ),
                array(
                    'INBOX',
                    'A',
                    'C',
                    'Z'
                )
            )
        );
    }

    /**
     * @dataProvider indexAssociationProvider
     */
    public function testIndexAssociation($mboxes, $expected)
    {
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

    public function indexAssociationProvider()
    {
        return array(
            array(
                array(
                    'Z' => 'Z',
                    'A' => 'A'
                ),
                array(
                    'A',
                    'Z'
                )
            )
        );
    }

    /**
     * @dataProvider noUpdateOfListObjectProvider
     */
    public function testNoUpdateOfListObject($mboxes, $expected)
    {
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

    public function noUpdateOfListObjectProvider()
    {
        return array(
            array(
                array(
                    'Z',
                    'A'
                ),
                array(
                    'A',
                    'Z'
                )
            )
        );
    }

}
