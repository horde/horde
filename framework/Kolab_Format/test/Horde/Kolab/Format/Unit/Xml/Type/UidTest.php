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
extends Horde_Kolab_Format_TestCase
{

    public function testLoadUid()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>'
        );
        $this->assertEquals('TEST', $attributes['uid']);
    }

    public function testLoadStrangeUid()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/>STRANGE<a/></uid>c</kolab>'
        );
        $this->assertEquals('STRANGE', $attributes['uid']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testLoadMissingUidText()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid></uid>c</kolab>'
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testLoadMissingUid()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>'
        );
    }

    public function testLoadMissingUidRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>',
            array('relaxed' => true)
        );
        $this->assertTrue(!isset($attributes['uid']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('uid' => 1)
            )
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><uid>1</uid></kolab>
',
            $this->saveToXml(
                null,
                array('uid' => 1)
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingUid
     */
    public function testSaveMissingData()
    {
        $this->saveToXml();
    }

    public function testInvalidXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>
',
            $this->saveToXml(
                null,
                array(),
                array('relaxed' => true)
            )
        );
    }

    public function testNewUid()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<uid>TEST</uid></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>',
                array('uid' => 'TEST')
            )
        );
    }

    public function testOldUid()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>',
                array('uid' => 'TEST')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testOverwriteOldUid()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>OLD</uid>c</kolab>',
            array('uid' => 'TEST')
        );
    }

    public function testOverwriteOldUidRelaxed()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>TEST</uid>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>OLD</uid>c</kolab>',
                array('uid' => 'TEST'),
                array('relaxed' => true)
            )
        );
    }

    public function testOverwriteStrangeUidRelaxed()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange">TEST<b/><a/></uid>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/>OLD<a/></uid>c</kolab>',
                array('uid' => 'TEST'),
                array('relaxed' => true)
            )
        );
    }

    public function testOverwriteStrangeUidRelaxedTwo()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange">TEST<b/><a/></uid>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid type="strange"><b/><a/></uid>c</kolab>',
                array('uid' => 'TEST'),
                array('relaxed' => true)
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Uid';
    }
}
