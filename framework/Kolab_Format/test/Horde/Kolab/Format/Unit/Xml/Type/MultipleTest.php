<?php
/**
 * Test the handler for attributes with multiple values.
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
 * Test the handler for attributes with multiple values.
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
class Horde_Kolab_Format_Unit_Xml_Type_MultipleTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadMultiple()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array('array' => array('type' => Horde_Kolab_Format_Xml::TYPE_STRING)),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><multiple>a</multiple></kolab>'
        );
        $attributes = array();
        $result->load('multiple', $attributes, $rootNode);
        $this->assertEquals(array('a'), $attributes['multiple']);
    }

    public function testLoadSeveralMultiple()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array('array' => array('type' => Horde_Kolab_Format_Xml::TYPE_STRING)),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0">
<multiple>a</multiple>
<multiple>Ü</multiple>
<multiple>SOME<a/>STRANGE<b/>ONE</multiple>
<multiple></multiple>
</kolab>'
        );
        $attributes = array();
        $result->load('multiple', $attributes, $rootNode);
        $this->assertEquals(array('a', 'Ü', 'SOME', ''), $attributes['multiple']);
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('X'),
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('multiple', $attributes, $rootNode);
        $this->assertEquals(array('X'), $attributes['multiple']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('multiple', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
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
        $result->load('multiple', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['multiple']));
    }

    public function testSave()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
            )
        );
        $this->assertEquals(
            array(),
            $result->save('multiple', array('multiple' => array()), $rootNode)
        );
    }

    public function testSaveMultiple()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
            )
        );
        $result->save('multiple', array('multiple' => array('a', 'B', 'Ü', '')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><multiple>a</multiple><multiple>B</multiple><multiple>Ü</multiple><multiple></multiple></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c</kolab>'
        );
        $result->save('multiple', array('multiple' => array('a', 'B', 'Ü', '')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<multiple>a</multiple><multiple>B</multiple><multiple>Ü</multiple><multiple></multiple></kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('multiple', array(), $rootNode);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidMultiple()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_BOOLEAN,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('multiple', array('multiple' => array('INVALID')), $rootNode);
    }

    public function testSaveInvalidMultipleRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_BOOLEAN,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('multiple', array('multiple' => array('INVALID')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><multiple>INVALID</multiple></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNodeList', 
            $result->save('multiple', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('multiple', array(), $rootNode));
    }

    public function testDeleteMultiple()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultMultiple(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c<y/><multiple>a</multiple></kolab>'
        );
        $result->save('multiple', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<y/></kolab>
',
            $doc->saveXML()
        );
    }


    private function _getDefaultMultiple($params = array(), $previous = null)
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
        $result = new Horde_Kolab_Format_Xml_Type_Multiple($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
