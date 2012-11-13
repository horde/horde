<?php
/**
 * Test the XML format implementation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the XML format.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Integration_XmlTest
extends Horde_Kolab_Format_TestCase
{
    public function testBasic()
    {
        $this->assertContains(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0">
  <body></body>
  <categories></categories>',
            $this->_getPlain()->save(array(), array('relaxed' => true))
        );
    }

    public function testReadable()
    {
        $xml = $this->_getPlain();
        $first = $xml->save(array(), array('relaxed' => true));
        $object = $xml->load($first, array('relaxed' => true));
        $this->assertEquals('1.0', $object['_format-version']);
    }

    public function testRoundtrip()
    {
        $xml = $this->_getPlain();
        $first = $xml->save(array(), array('relaxed' => true));
        $second = $xml->save(
            $xml->load($first, array('relaxed' => true)),
            array('relaxed' => true)
        );
        $this->assertEquals(
            $this->removeLastModification($first),
            $this->removeLastModification($second)
        );
    }

    public function testRoundtripWithPrevious()
    {
        $xml = $this->_getPlain();
        $first = $xml->save(array(), array('relaxed' => true));
        $second = $xml->save(
            $xml->load($first, array('relaxed' => true)),
            array('relaxed' => true, 'previous' => $first)
        );
        $this->assertEquals(
            $this->removeLastModification($first),
            $this->removeLastModification($second)
        );
    }

    public function testRoundtripWithPreviousOnApiV1()
    {
        $xml = new Horde_Kolab_Format_Xml(
            new Horde_Kolab_Format_Xml_Parser(
                new DOMDocument('1.0', 'UTF-8')
            ),
            new Horde_Kolab_Format_Factory(),
            array('version' => 1)
        );
        $first = $xml->save(array('uid' => 1));
        $second = $xml->save($xml->load($first));
        $this->assertEquals(
            $this->removeLastModification($first),
            $this->removeLastModification($second)
        );
    }

    public function testReload()
    {
        $xml = $this->_getPlain();
        $cdate = new DateTime('1970-01-01T00:00:00Z');
        $cdate->setTimezone(new DateTimeZone('UTC'));
        $result = $xml->save(
            array(
                'uid'=>'test',
                'body' => 'body',
                'dummy' => 'hello',
                'creation-date' => $cdate
            )
        );
        $object = $xml->load($result);
        $this->assertEquals('body', $object['body']);
        $this->assertTrue(!isset($object['dummy']));
        $this->assertEquals('public', $object['sensitivity']);
        $this->assertEquals($cdate, $object['creation-date']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->assertTrue(
            $object['last-modification-date']->format('U') <= $now->format('U')
        );
        $this->assertEquals(
            'Horde_Kolab_Format_Xml-@version@ (api version: 2)',
            $object['product-id']
        );
    }

    private function _getPlain()
    {
        return new Horde_Kolab_Format_Xml(
            new Horde_Kolab_Format_Xml_Parser(
                new DOMDocument('1.0', 'UTF-8')
            ),
            new Horde_Kolab_Format_Factory()
        );
    }
}

