<?php
/**
 * Test the product ID attribute handler.
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
 * Test the product ID attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_ProductIdTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadProductId()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id>SOMETHING</product-id>c</kolab>'
        );
        $attributes = array();
        $pid->load('product-id', $attributes, $rootNode);
        $this->assertEquals('SOMETHING', $attributes['product-id']);
    }

    public function testLoadStrangeProductId()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>'
        );
        $attributes = array();
        $pid->load('product-id', $attributes, $rootNode);
        $this->assertEquals('STRANGE', $attributes['product-id']);
    }

    public function testLoadMissingProductId()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $pid->load('product-id', $attributes, $rootNode);
        $this->assertEquals('', $attributes['product-id']);
    }

    public function testSave()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array('type' => 'kolab', 'version' => '1.0', 'api_version' => 2)
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $pid->save('product-id', array(), $rootNode)
        );
    }

    public function testSaveXml()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array('type' => 'kolab', 'version' => '1.0', 'api_version' => 2)
        );
        $pid->save('product-id', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><product-id>Horde_Kolab_Format_Xml-@version@ (type: kolab, format version: 1.0, api version: 2)</product-id></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $pid) = $this->_getDefaultProduct(
            array('type' => 'kolab', 'version' => '1.0', 'api_version' => 2),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>'
        );
        $pid->save('product-id', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange">Horde_Kolab_Format_Xml-@version@ (type: kolab, format version: 1.0, api version: 2)<b/><a/></product-id>c</kolab>
',
            $doc->saveXML()
        );
    }

    private function _getDefaultProduct($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $mdate = new Horde_Kolab_Format_Xml_Type_ProductId($doc, $params);
        return array($doc, $rootNode, $mdate);
    }
}
