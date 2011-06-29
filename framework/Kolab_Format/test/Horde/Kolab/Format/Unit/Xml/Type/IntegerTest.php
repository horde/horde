<?php
/**
 * Test the integer attribute handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the integer attribute handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_Type_IntegerTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadInteger()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer>1</integer>c</kolab>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
        $this->assertSame(1, $attributes['integer']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadStrangeInteger()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>false<a/></integer>c</kolab>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
        $this->assertSame(0, $attributes['integer']);
    }

    public function testLoadMissingInteger()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => 5
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
        $this->assertSame(5, $attributes['integer']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('integer', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testSave()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger();
        $this->assertInstanceOf(
            'DOMNode',
            $result->save('integer', array('integer' => 1), $rootNode)
        );
    }

    public function testSaveInteger()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger();
        $result->save('integer', array('integer' => 7), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><integer>7</integer></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>STRANGE<a/></integer>c</kolab>'
        );
        $result->save('integer', array('integer' => 7), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange">7<b/><a/></integer>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('integer', array(), $rootNode);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidInteger()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('integer', array('integer' => 'INVALID'), $rootNode);
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>STRANGE<a/></integer>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('integer', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultInteger(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('integer', array(), $rootNode));
    }

    private function _getDefaultInteger($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $result = new Horde_Kolab_Format_Xml_Type_Integer($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
