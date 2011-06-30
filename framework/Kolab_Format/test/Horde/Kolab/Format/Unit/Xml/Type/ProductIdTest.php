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
extends Horde_Kolab_Format_TestCase
{
    public function testLoadProductId()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id>SOMETHING</product-id>c</kolab>'
        );
        $this->assertEquals('SOMETHING', $attributes['product-id']);
    }

    public function testLoadStrangeProductId()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>'
        );
        $this->assertEquals('STRANGE', $attributes['product-id']);
    }

    public function testLoadMissingProductId()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertEquals('', $attributes['product-id']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->_saveToReturn()
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><product-id>Horde_Kolab_Format_Xml-@version@ (api version: 2)</product-id></kolab>
',
            $this->_saveToXml()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange">Horde_Kolab_Format_Xml-@version@ (api version: 2)<b/><a/></product-id>c</kolab>
',
            $this->_saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>'
            )
        );
    }

    private function _load($previous)
    {
        list($params, $root_node, $type) = $this->_getProductId($previous);
        $attributes = array();
        $params['api-version'] = 2;
        $type->load('product-id', $attributes, $root_node, $params);
        return $attributes;
    }

    private function _saveToXml($previous = null)
    {
        list($params, $root_node, $type) = $this->_getProductId($previous);
        $params['api-version'] = 2;
        $attributes = array();
        $type->save('product-id', $attributes, $root_node, $params);
        return (string)$params['helper'];
    }

    private function _saveToReturn($previous = null)
    {
        list($params, $root_node, $type) = $this->_getProductId($previous);
        $params['api-version'] = 2;
        $attributes = array();
        return $type->save('product-id', $attributes, $root_node, $params);
    }

    private function _getProductId(
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        return $this->getXmlType(
            'Horde_Kolab_Format_Xml_Type_ProductId',
            $previous,
            $kolab_type,
            $version
        );
    }
}
