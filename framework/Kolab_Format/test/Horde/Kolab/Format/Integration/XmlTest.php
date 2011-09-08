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
        $xml->_prepareSave();
        $base = $xml->_xmldoc->saveXML();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\"/>\n",
                            $base);
    }

    /**
     * The resulting XML string should be readable.
     *
     * @return NULL
     */
    public function testReadable()
    {
        $this->markTestIncomplete('Roundtrip makes sense, but how to handle empty document?');
        $xml = $this->_getPlain();
        $xml->_prepareSave();
        $base = $xml->_xmldoc->saveXML();
        $xml->load($base);
        $this->assertEquals($base, $xml->_xmldoc->saveXML());

    }

    /**
     * Test adding nodes.
     *
     * @return NULL
     */
    public function testAdd()
    {
        $xml = $this->_getPlain();
        $root = $xml->_prepareSave();
        $base = $xml->_xmldoc->saveXML();

        // A missing attribute should cause no change if it
        // is allowed to be empty
        $xml->_updateNode($root,
                          array(),
                          'empty1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING));
        $this->assertEquals($base, $xml->_xmldoc->saveXML());

        // A missing attribute should cause an error if it
        // is not allowed to be empty
        try {
            $xml->_updateNode($root,
                              array(),
                              'empty1',
                              array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue($e instanceOf Horde_Kolab_Format_Exception);
        }

        $xml->_updateNode($root,
                         array(),
                         'empty1',
                         array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                               'default' => 'empty1', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        try {
            $xml->_updateNode($root,
                              array(),
                              'empty1',
                              array('value' => Horde_Kolab_Format_Xml::VALUE_CALCULATED,
                                    'save' => '_unknown'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue($e instanceOf Horde_Kolab_Format_Exception);
        }
    }


    /**
     * Test node operations
     *
     * @return NULL
     */
    public function testNodeOps()
    {
        $dxml = $this->_getDummy();
        $droot = $dxml->_prepareSave();

        // Test calculated nodes
        $dxml->_updateNode($droot,
                           array(),
                           'empty2',
                           array('value' => Horde_Kolab_Format_Xml::VALUE_CALCULATED,
                                 'save' => 'Value', 'type' => 0));
        $dxml->_updateNode($droot,
                           array('present1' => 'present1'),
                           'present1',
                           array('value' => Horde_Kolab_Format_Xml::VALUE_CALCULATED,
                                 'save' => 'Value', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty2>empty2: , missing</empty2>\n  <present1>present1: present1</present1>\n</kolab>\n",
                            $dxml->_xmldoc->saveXML());

        $xml  = $this->_getPlain();
        $root = $xml->_prepareSave();
        $xml->_updateNode($root,
                          array(),
                          'empty1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                'default' => 'empty1', 'type' => 0));

        // Back to the original object: Test saving a normal value
        $xml->_updateNode($root,
                          array('present1' => 'present1'),
                          'present1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                'default' => 'empty1', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>present1</present1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        // Test overwriting a value
        $xml->_updateNode($root,
                          array('present1' => 'new1'),
                          'present1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                'default' => 'empty1', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>new1</present1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        // Test saving a date
        $xml->_updateNode($root,
                          array('date1' => 1175080008),
                          'date1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                'default' => 'empty1', 
                                'type' => Horde_Kolab_Format_Xml::TYPE_DATETIME));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>new1</present1>\n  <date1>2007-03-28T11:06:48Z</date1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        // Now load the data back in
        $children = $root->childNodes;

        // Test loading a value that may be empty
        $this->assertEquals(null, $xml->_getXmlData($children,
                                                    'empty2',
                                                    array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                                                          'default' => '', 
                                                          'type' => Horde_Kolab_Format_Xml::TYPE_STRING)));

        // Test loading a value that may not be empty
        try {
            $xml->_getXmlData($children,
                              'empty2',
                              array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                                    'default' => '', 
                                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue($e instanceOf Horde_Kolab_Format_Exception);
        }

        // Test loading a missing value with a default
        $this->assertEquals(0, $xml->_getXmlData($children,
                                                 'date2',
                                                 array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                                       'default' => 0, 
                                                       'type' => Horde_Kolab_Format_Xml::TYPE_DATETIME)));

        // Test loading a calculated value
        $this->assertEquals('new1', $dxml->_getXmlData($children,
                                                       'present1',
                                                       array('value' => Horde_Kolab_Format_Xml::VALUE_CALCULATED,
                                                             'func' => '_calculate',
                                                             'type' => Horde_Kolab_Format_Xml::TYPE_STRING)));

        // Test loading a normal value
        $this->assertEquals('new1', $xml->_getXmlData($children,
                                                      'present1',
                                                      array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                                            'default' => 'empty',
                                                            'type' => Horde_Kolab_Format_Xml::TYPE_STRING)));

        // Test loading a date value
        $this->assertEquals(1175080008, $xml->_getXmlData($children,
                                                          'date1',
                                                          array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                                                                'default' => 0,
                                                                'type' => Horde_Kolab_Format_Xml::TYPE_DATETIME)));
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

    /**
     * Test complex values
     *
     * @return NULL
     */
    public function testComplex()
    {
        // Continue with complex values
        $xml = $this->_getPlain();
        $root = $xml->_prepareSave();

        // Test saving a composite value
        $xml->_updateNode($root,
                          array('composite1' => array('display-name' => 'test',
                                                      'smtp-address' => 'test@example.com')),
                          'composite1', $xml->_fields_simple_person);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <composite1>\n    <display-name>test</display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <uid></uid>\n  </composite1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        // Test saving multiple values
        $xml->_updateNode($root,
                          array('attendee1' => array(array('display-name' => 'test'),
                                                     array('smtp-address' => 'test@example.com'))),
                          'attendee1', $xml->_fields_attendee);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <composite1>\n    <display-name>test</display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <uid></uid>\n  </composite1>\n  <attendee1>\n    <display-name>test</display-name>\n    <smtp-address></smtp-address>\n    <status>none</status>\n    <request-response>true</request-response>\n    <role>required</role>\n  </attendee1>\n  <attendee1>\n    <display-name></display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <status>none</status>\n    <request-response>true</request-response>\n    <role>required</role>\n  </attendee1>\n</kolab>\n",
                            $xml->_xmldoc->saveXML());

        $children = $root->childNodes;

        // Load a composite value
        $data = $xml->_getXmlData($children,
                                  'composite1', 
                                  $xml->_fields_simple_person);

        $this->assertEquals(3, count($data));
        $this->assertEquals('test@example.com', $data['smtp-address']);

        // Load multiple values
        $data = $xml->_getXmlData($children,
                                  'attendee1', 
                                  $xml->_fields_attendee);
        $this->assertEquals(2, count($data));
        $this->assertEquals(5, count($data[0]));
        $this->assertEquals('', $data[0]['smtp-address']);
        $this->assertEquals('test@example.com', $data[1]['smtp-address']);
    }

    private function _getDummy()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Dummy');
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

