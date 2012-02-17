<?php
/**
 * Tests for the Imap Client DateTime object.
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
 * Tests for the Imap Client DateTime object.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Imap_Client_DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testBug9847()
    {
        $date = 'Fri, 06 Oct 2006 12:15:13 +0100 (GMT+01:00)';
        $ob = new Horde_Imap_Client_DateTime($date);

        $this->assertEquals(
            1160133313,
            intval(strval($ob))
        );
    }

}
