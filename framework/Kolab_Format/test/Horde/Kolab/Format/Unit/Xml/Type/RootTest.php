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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the Document root handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_Type_RootTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadRoot()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>A</uid></kolab>'
        );
        $this->assertEquals('A', $attributes['uid']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testMissingRootNode()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?><test/>'
        );
    }

    public function testLoadApiVersion()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>A</uid></kolab>'
        );
        $this->assertEquals('2', $attributes['_api-version']);
    }

    public function testLoadLowerApiVersion()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>A</uid></kolab>',
            array('api-version' => '1')
        );
        $this->assertEquals('1', $attributes['_api-version']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testLoadHigherVersion()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>'
        );
    }

    public function testLoadHigherVersionRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>',
            array('relaxed' => true)
        );
        $this->assertEquals('2.0', $attributes['_format-version']);
    }

    public function testLoadVersion()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><uid>A</uid>c</kolab>'
        );
        $this->assertEquals('1.0', $attributes['_format-version']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(null, array('uid' => 'A'))
        );
    }

    public function testSaveCreatesNewNode()
    {
        $this->assertRegexp(
            '#<kolab version="1.0"><uid>A</uid><body></body><categories></categories>#',
            $this->saveToXml(null, array('uid' => 'A'))
        );
    }

    public function testSaveDoesNotTouchExistingNode()
    {
        $this->assertRegexp(
            '#<kolab version="1.0" a="b">#',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>',
                array('uid' => 'A')
            )
        );
    }

    public function testAddNewType()
    {
        $this->assertRegexp(
            '#<old version="1.0" a="b">c</old>
<kolab version="1.0"#',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<old version="1.0" a="b">c</old>',
                array('uid' => 'A')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_InvalidRoot
     */
    public function testOverwriteHigherVersion()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>',
            array()
        );
    }

    public function testOverwriteHigherVersionRelaxed()
    {
        $this->assertRegexp(
            '#<kolab version="1.0"#',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="2.0" a="b">c</kolab>',
                array('uid' => 'A'),
                array('relaxed' => true)
            )
        );
    }

    public function testSetHigherVersion()
    {
        $this->assertRegexp(
            '#<kolab version="2.0" a="b"#',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>',
                array('uid' => 'A'),
                array('expected-version' => '2.0')
            )
        );
    }

    public function testSaveNewVersion()
    {
        $this->assertRegexp(
            '#<kolab version="2.0"#',
            $this->saveToXml(
                null,
                array('uid' => 'A'),
                array('expected-version' => '2.0')
            )
        );
    }

    protected function getXmlType(
        $type,
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        $factory = new Horde_Kolab_Format_Factory();
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $helper = $factory->createXmlHelper($doc);
        $type = $factory->createXmlType($type);
        return array($helper, $doc, $type);
    }

    protected function getTestType($previous, &$params)
    {
        list($params, $root_node, $type, $helper) = parent::getTestType($previous, $params);
        if (!isset($params['expected-version'])) {
            $params['expected-version'] = '1.0';
        }
        if (!isset($params['api-version'])) {
            $params['api-version'] = 2;
        }
        $params['element'] = 'kolab';
        return array($params, $root_node, $type, $helper);
    }


    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Root';
    }
}
