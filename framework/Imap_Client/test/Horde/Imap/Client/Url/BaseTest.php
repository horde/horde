<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for base URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Url_BaseTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider badUrlProvider
     */
    public function testBadUrl($classname)
    {
        $url = new $classname('NOT A VALID URL');

        $this->assertNull($url->hostspec);
    }

    public function badUrlProvider()
    {
        return array(
            array('Horde_Imap_Client_Url'),
            array('Horde_Imap_Client_Url_Imap'),
            array('Horde_Imap_Client_Url_Imap_Relative'),
            array('Horde_Imap_Client_Url_Pop3')
        );
    }

}
