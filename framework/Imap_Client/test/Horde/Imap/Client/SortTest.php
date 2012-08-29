<?php
/**
 * Tests for the IMAP sorting class.
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
 * Tests for the IMAP sorting class.
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

        Horde_Imap_Client_Sort::sortMailboxes($mboxes, array(
            'delimiter' => '.'
        ));

        $this->assertEquals(
            array_values($expected),
            array_values($mboxes)
        );
    }

}
