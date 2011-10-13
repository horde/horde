<?php
/**
 * Tests for the Imap Client ACL object(s).
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
 * Tests for the Imap Client ACL object(s).
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Imap_Client_AclTest extends PHPUnit_Framework_TestCase
{
    public function testBug10079()
    {
        // RFC 2086 rights string
        $rights = 'lrswipcda';

        $ob = new Horde_Imap_Client_Data_Acl($rights);

        $this->assertEquals(
            'lrswipakxte',
            strval($ob)
        );

        // RFC 4314 rights string
        $rights = 'lrswipakte';

        $ob = new Horde_Imap_Client_Data_Acl($rights);

        $this->assertEquals(
            'lrswipakte',
            strval($ob)
        );
    }

}
