<?php
/**
 * Test the UID attribute handler.
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
 * Test the UID attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_UidTest
extends PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid();
        $this->assertInstanceOf(
            'DOMNode', 
            $uid->save('uid', array('uid' => 1), $rootNode)
        );
    }

    public function testSaveXml()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid();
        $uid->save('uid', array('uid' => 1), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><uid>1</uid></kolab>
', 
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testSaveMissingData()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid();
        $uid->save('uid', array(), $rootNode);
    }

    public function testInvalidXml()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(array('relaxed' => true));
        $uid->save('uid', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>
', 
            $doc->saveXML()
        );
    }

    public function testNewUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<uid>TEST</uid></kolab>
', 
            $doc->saveXML()
        );
    }

    public function testOldUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>
', 
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testOverwriteOldUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>OLD</uid>c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
    }

    public function testOverwriteOldUidRelaxed()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>OLD</uid>c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>
', 
            $doc->saveXML()
        );
    }

    public function testOverwriteStrangeUidRelaxed()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/>OLD<a/></uid>c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange">TEST<b/><a/></uid>c</kolab>
', 
            $doc->saveXML()
        );
    }

    public function testOverwriteStrangeUidRelaxedTwo()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/><a/></uid>c</kolab>'
        );
        $uid->save('uid', array('uid' => 'TEST'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange">TEST<b/><a/></uid>c</kolab>
', 
            $doc->saveXML()
        );
    }

    public function testLoadUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>'
        );
        $attributes = array();
        $uid->load('uid', $attributes, $rootNode);
        $this->assertEquals(
            'TEST', 
            $attributes['uid']
        );
    }

    public function testLoadStrangeUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/>STRANGE<a/></uid>c</kolab>'
        );
        $attributes = array();
        $uid->load('uid', $attributes, $rootNode);
        $this->assertEquals(
            'STRANGE', 
            $attributes['uid']
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testLoadMissingUidText()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid></uid>c</kolab>'
        );
        $attributes = array();
        $uid->load('uid', $attributes, $rootNode);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testLoadMissingUid()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>'
        );
        $attributes = array();
        $uid->load('uid', $attributes, $rootNode);
    }

    public function testLoadMissingUidRelaxed()
    {
        list($doc, $rootNode, $uid) = $this->_getDefaultUid(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>'
        );
        $attributes = array();
        $uid->load('uid', $attributes, $rootNode);
        $this->assertTrue(!isset($attributes['uid']));
    }


    private function _getDefaultUid($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $uid = new Horde_Kolab_Format_Xml_Type_Uid($doc, $params);
        return array($doc, $rootNode, $uid);
    }
}
