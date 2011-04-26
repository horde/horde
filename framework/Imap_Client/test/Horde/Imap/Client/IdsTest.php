<?php
/**
 * Tests for the Ids object.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for the Ids object.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_IdsTest extends PHPUnit_Framework_TestCase
{
    public function testBasicAddingOfIds()
    {
        $ids = new Horde_Imap_Client_Ids(array(1, 3, 5));

        $this->assertEquals(
            3,
            count($ids)
        );

        $this->assertEquals(
            '1,3,5',
            strval($ids)
        );

        $this->assertFalse($ids->isEmpty());
    }

    public function testEmptyIdsArray()
    {
        $ids = new Horde_Imap_Client_Ids(array());

        $this->assertEquals(
            0,
            count($ids)
        );

        $this->assertEquals(
            '',
            strval($ids)
        );

        $this->assertTrue($ids->isEmpty());
    }

    public function testSequenceParsing()
    {
        $ids = new Horde_Imap_Client_Ids('12:10');

        $this->assertEquals(
            3,
            count($ids)
        );

        $this->assertEquals(
            array(10, 11, 12),
            iterator_to_array($ids)
        );

        $ids = new Horde_Imap_Client_Ids('12,11,10');

        $this->assertEquals(
            3,
            count($ids)
        );

        $this->assertEquals(
            array(12, 11, 10),
            iterator_to_array($ids)
        );

        $ids = new Horde_Imap_Client_Ids('10:12,10,11,12,10:12');

        $this->assertEquals(
            3,
            count($ids)
        );

        $ids = new Horde_Imap_Client_Ids('10:10');

        $this->assertEquals(
            1,
            count($ids)
        );
    }

}
