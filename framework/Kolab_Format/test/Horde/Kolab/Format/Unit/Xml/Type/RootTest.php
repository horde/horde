<?php
/**
 * Test the Document root handler.
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
 * Test the Document root handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_RootTest
extends PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $root->save()
        );
    }

    public function testSaveCreatesNewNode()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->save();
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>
', 
            $doc->saveXML()
        );
    }

    public function testSaveDoesNotTouchExistingNode()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->save();
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>
', 
            $doc->saveXML()
        );
    }

    public function testAddNewType()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<old version="1.0" a="b">c</old>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->save();
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<old version="1.0" a="b">c</old>
<kolab version="1.0"/>
', 
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testOverwriteHigherVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->save();
    }

    public function testOverwriteHigherVersionRelaxed()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0', 'relaxed' => true)
        );
        $root->save();
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>
', 
            $doc->saveXML()
        );
    }

    public function testSetHigherVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '2.0')
        );
        $root->save();
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>
', 
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testMissingRootNode()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?><test/>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->load();
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testLoadHigherVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->load();
    }

    public function testLoadHigherVersionRelaxed()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0', 'relaxed' => true)
        );
        $root->load();
        $this->assertEquals('2.0', $root->getVersion());
    }

    public function testInitialVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $this->assertNull($root->getVersion());
    }

    public function testLoadVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $root->load();
        $this->assertEquals('1.0', $root->getVersion());
    }

    public function testSaveNewVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '2.0')
        );
        $root->save();
        $this->assertEquals('2.0', $root->getVersion());
    }

    public function testUpdateVersion()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>');
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '2.0')
        );
        $root->save();
        $this->assertEquals('2.0', $root->getVersion());
    }
}
