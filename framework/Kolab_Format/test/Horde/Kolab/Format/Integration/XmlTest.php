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
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the XML format.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Integration_XmlTest
extends PHPUnit_Framework_TestCase
{
    /**
     * Check the preparation of the basic XML structure
     *
     * @return NULL
     */
    public function testBasic()
    {
        $xml = $this->_getPlain();
        $xml->save(array(), array('relaxed' => true));
        $base = $xml->_xmldoc->saveXML();
        $this->assertContains(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0">
  <body></body>
  <categories></categories>',
            $base
        );
    }

    /**
     * The resulting XML string should be readable.
     *
     * @return NULL
     */
    public function testReadable()
    {
        $xml = $this->_getPlain();
        $xml->save(array(), array('relaxed' => true));
        $base = $xml->_xmldoc->saveXML();
        $xml->load($base, array('relaxed' => true));
        $this->assertEquals($base, $xml->_xmldoc->saveXML());

    }

    /**
     * Test load/save
     *
     * @return NULL
     */
    public function testReleod()
    {
        // Save an object and reload it
        $xml = $this->_getPlain();
        $result = $xml->save(array('uid'=>'test',
                                   'body' => 'body',
                                   'dummy' => 'hello',
                                   'creation-date' => 1175080008,
                                   'last-modification-date' => 1175080008,
                             ));
        $object = $xml->load($result);
        $this->assertEquals('body', $object['body']);
        $this->assertTrue(empty($object['dummy']));
        $this->assertEquals('public', $object['sensitivity']);
        $this->assertEquals(1175080008, $object['creation-date']);
        $this->assertTrue($object['last-modification-date'] != 1175080008);
        $this->assertEquals('Horde::Kolab', $object['product-id']);
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

