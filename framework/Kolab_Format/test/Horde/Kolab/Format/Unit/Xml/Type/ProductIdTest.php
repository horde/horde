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
        list($params, $root_node, $pid) = $this->_getProductId();
        $params['api-version'] = 2;
        $this->assertInstanceOf(
            'DOMNode', 
            $pid->save('product-id', array(), $root_node, $params)
        );
    }

    public function testSaveXml()
    {
        list($params, $root_node, $pid) = $this->_getProductId();
        $params['api-version'] = 2;
        $pid->save('product-id', array(), $root_node, $params);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><product-id>Horde_Kolab_Format_Xml-@version@ (api version: 2)</product-id></kolab>
',
            (string)$params['helper']
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($params, $root_node, $pid) = $this->_getProductId(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>'
        );
        $params['api-version'] = 2;
        $pid->save('product-id', array(), $root_node, $params);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange">Horde_Kolab_Format_Xml-@version@ (api version: 2)<b/><a/></product-id>c</kolab>
',
            (string)$params['helper']
        );
    }

    private function _load($previous)
    {
        list($params, $root_node, $pid) = $this->_getProductId($previous);
        $attributes = array();
        $params['api-version'] = 2;
        $pid->load('product-id', $attributes, $root_node, $params);
        return $attributes;
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
