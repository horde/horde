<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Tests for the Imap Client ACL object(s).
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
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
