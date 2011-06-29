<?php
/**
 * Test the handler for attributes with composite values.
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
 * Test the handler for attributes with composite values.
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
class Horde_Kolab_Format_Unit_Xml_Type_CompositeTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadComposite()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'array' => array(
                    'uid' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                    'test' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                )
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><composite><uid>a&amp;</uid><test>TEST</test></composite></kolab>'
        );
        $attributes = array();
        $result->load('composite', $attributes, $rootNode);
        $this->assertEquals(array('test' => 'TEST', 'uid' => 'a&'), $attributes['composite']);
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('X' => 'Y'),
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('composite', $attributes, $rootNode);
        $this->assertEquals(array('X' => 'Y'), $attributes['composite']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('composite', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'relaxed' => true
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('composite', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['composite']));
    }

    public function testSaveComposite()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'array' => array(
                    'uid' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                    'test' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                )
            )
        );
        $result->save('composite', array('composite' => array()), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><composite><uid></uid><test></test></composite></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveModifiesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'array' => array(
                    'uid' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                    'test' => array(
                        'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                        'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                        'default' => '',
                    ),
                ),
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c</kolab>'
        );
        $result->save('composite', array('composite' => array('uid' => 1, 'test' => '&')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/><uid>1</uid><test>&amp;</test></composite>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('composite', array(), $rootNode);
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('composite', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('composite', array(), $rootNode));
    }

    public function testDeleteComposite()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultComposite(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c<y/><composite>a</composite></kolab>'
        );
        $result->save('composite', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<y/></kolab>
',
            $doc->saveXML()
        );
    }


    private function _getDefaultComposite($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $params['factory'] = new Horde_Kolab_Format_Factory();
        $result = new Horde_Kolab_Format_Xml_Type_Composite($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
