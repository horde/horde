<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Base class for URL tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
abstract class Horde_Imap_Client_Url_TestBase
extends PHPUnit_Framework_TestCase
{
    protected $protocol;

    /**
     * @dataProvider testUrlProvider
     */
    public function testUrlParsing($in, $url, $expected)
    {
        $ob = new Horde_Imap_Client_Url($in);

        foreach ($expected as $key => $val) {
            $this->assertEquals(
                $val,
                $ob->$key
            );
        }

        $this->assertEquals(
            is_null($url) ? $in : $url,
            strval($ob)
        );
    }

    abstract public function testUrlProvider();

    public function testSerialize()
    {
        $url = unserialize(serialize(
            new Horde_Imap_Client_Url($this->protocol . '://test.example.com/')
        ));

        $this->assertEquals(
            $this->protocol,
            $url->protocol
        );

        $this->assertEquals(
            'test.example.com',
            $url->hostspec
        );
    }

}
