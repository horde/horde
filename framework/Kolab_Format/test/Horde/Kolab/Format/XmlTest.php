<?php
/**
 * Test the XML format implementation.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/XmlTest.php,v 1.5 2009/01/06 17:49:23 jan Exp $
 *
 * @package Kolab_Format
 */

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Framework.php';

require_once 'Horde/Nls.php';
require_once 'Horde/Kolab/Format.php';
require_once 'Horde/Kolab/Format/XML.php';

/**
 * Test the XML format.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/XmlTest.php,v 1.5 2009/01/06 17:49:23 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_XmlTest extends PHPUnit_Framework_TestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        Horde_Nls::setCharset('utf-8');
    }


    /**
     * Check the preparation of the basic XML structure
     */
    public function testBasic()
    {
        $xml = &new Horde_Kolab_Format_XML();
        $xml->_prepareSave();
        $base = $xml->_xmldoc->dump_mem(true);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\"/>\n", $base);
    }

    /**
     * The resulting XML string should be readable.
     */
    public function testReadable()
    {
        $xml = &new Horde_Kolab_Format_XML();
        $xml->_prepareSave();
        $base = $xml->_xmldoc->dump_mem(true);
        $xml->_parseXml($base);
        $this->assertEquals($base, $xml->_xmldoc->dump_mem(true));

    }

    /**
     * Test adding nodes.
     */
    public function testAdd()
    {
        $xml = &new Horde_Kolab_Format_XML();
        $xml->_prepareSave();
        $root = $xml->_xmldoc->document_element();
        $base = $xml->_xmldoc->dump_mem(true);

        // A missing attribute should cause no change if it
        // is allowed to be empty
        $xml->_updateNode($root,
                          array(),
                          'empty1',
                          array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING));
        $this->assertEquals($base, $xml->_xmldoc->dump_mem(true));

        // A missing attribute should cause an error if it
        // is not allowed to be empty
        try {
            $xml->_updateNode($root,
                              array(),
                              'empty1',
                              array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(is_a($e, 'Horde_Exception'));
        }

        $xml->_updateNode($root,
                         array(),
                         'empty1',
                         array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                               'default' => 'empty1', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        try {
            $xml->_updateNode($root,
                              array(),
                              'empty1',
                              array('value' => Horde_Kolab_Format_Xml::VALUE_CALCULATED,
                                    'save' => '_unknown'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(is_a($e, 'Horde_Exception'));
        }
    }


    /**
     * Test node operations
     */
    public function testNodeOps()
    {
        $dxml = new Horde_Kolab_Format_XML_dummy();
        $dxml->_prepareSave();
        $droot = $dxml->_xmldoc->document_element();

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
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty2>empty2: , missing</empty2>\n  <present1>present1: present1</present1>\n</kolab>\n",  $dxml->_xmldoc->dump_mem(true));

        $xml = &new Horde_Kolab_Format_XML();
        $xml->_prepareSave();
        $root = $xml->_xmldoc->document_element();
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
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>present1</present1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        // Test overwriting a value
        $xml->_updateNode($root,
                         array('present1' => 'new1'),
                         'present1',
                         array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                               'default' => 'empty1', 'type' => 0));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>new1</present1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        // Test saving a date
        $xml->_updateNode($root,
                         array('date1' => 1175080008),
                         'date1',
                         array('value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                               'default' => 'empty1', 
                               'type' => Horde_Kolab_Format_Xml::TYPE_DATETIME));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <empty1>empty1</empty1>\n  <present1>new1</present1>\n  <date1>2007-03-28T11:06:48Z</date1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        // Now load the data back in
        $children = $root->child_nodes();

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
            $this->assertTrue(is_a($e, 'Horde_Exception'));
        }

        // Test loading a missing value with a default
        $this->assertEquals(0 ,$xml->_getXmlData($children,
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
     */
    public function testReleod()
    {
        // Save an object and reload it
        $xml = new Horde_Kolab_Format_XML();
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
     */
    public function testComplex()
    {
        // Continue with complex values
        $xml = new Horde_Kolab_Format_XML();
        $xml->_prepareSave();
        $root = $xml->_xmldoc->document_element();

        // Test saving a composite value
        $xml->_updateNode($root,
                         array('composite1' => array('display-name' => 'test', 'smtp-address' => 'test@example.com')),
                         'composite1', $xml->_fields_simple_person);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <composite1>\n    <display-name>test</display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <uid></uid>\n  </composite1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        // Test saving multiple values
        $xml->_updateNode($root,
                         array('attendee1' => array(array('display-name' => 'test'), array('smtp-address' => 'test@example.com'))),
                         'attendee1', $xml->_fields_attendee);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<kolab version=\"1.0\">\n  <composite1>\n    <display-name>test</display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <uid></uid>\n  </composite1>\n  <attendee1>\n    <display-name>test</display-name>\n    <smtp-address></smtp-address>\n    <status>none</status>\n    <request-response>true</request-response>\n    <role>required</role>\n  </attendee1>\n  <attendee1>\n    <display-name></display-name>\n    <smtp-address>test@example.com</smtp-address>\n    <status>none</status>\n    <request-response>true</request-response>\n    <role>required</role>\n  </attendee1>\n</kolab>\n",  $xml->_xmldoc->dump_mem(true));

        $children = $root->child_nodes();

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
}

/**
 * A dummy XML type
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/XmlTest.php,v 1.5 2009/01/06 17:49:23 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_XML_dummy extends Horde_Kolab_Format_XML
{
    function _saveValue($node, $name, $value, $missing)
    {
        $result='';
        $result .= $name . ': ';
        $result .= $value;
        if ($missing) $result .= ', missing';

        return $this->_saveDefault($node, 
                                   $name, 
                                   $result, 
                                   array('type' => self::TYPE_STRING));
    }
}
