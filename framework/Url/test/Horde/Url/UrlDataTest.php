<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Url
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Url_Data class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_UrlDataTest extends PHPUnit_Framework_TestCase
{
    public function testParsingDataUrl()
    {
        $data = array(
            'data:text/plain;base64,VGhpcyBpcyBhIHRlc3Qu',
            'data:text/plain,This%20is%20a%20test.'
        );

        foreach ($data as $val) {
            $ob = new Horde_Url_Data($val);

            $this->assertEquals(
                'This is a test.',
                $ob->data
            );

            $this->assertEquals(
                'text/plain',
                $ob->type
            );
        }
    }

    public function testCreatingDataUrl()
    {
        $data = Horde_Url_Data::create('text/plain', 'This is a test.');

        $this->assertEquals(
            'data:text/plain;base64,VGhpcyBpcyBhIHRlc3Qu',
            strval($data)
        );

        $data = Horde_Url_Data::create('text/plain', 'This is a test.', false);

        $this->assertEquals(
            'data:text/plain,This%20is%20a%20test.',
            strval($data)
        );
    }

    public function testIsData()
    {
        $this->assertFalse(Horde_Url_Data::isData(new Horde_Url()));
        $this->assertFalse(Horde_Url_Data::isData('foo'));
        $this->assertFalse(Horde_Url_Data::isData(333));
        $this->assertFalse(Horde_Url_Data::isData(array(new Horde_Url_Data())));

        $this->assertTrue(Horde_Url_Data::isData(new Horde_Url_Data()));
        $this->assertTrue(Horde_Url_Data::isData('data:text/plain,Foo'));
    }

}
