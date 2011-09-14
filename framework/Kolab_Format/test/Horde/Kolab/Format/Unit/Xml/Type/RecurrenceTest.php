<?php
/**
 * Test the recurrence handler.
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
 * Test the recurrence handler.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_Xml_Type_RecurrenceTest
extends Horde_Kolab_Format_TestCase
{
    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testEmptyInterval()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><recurrence>TEST</recurrence>c</kolab>'
        );
        $this->assertEquals(array(), $attributes['recurrence']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testIntervalBelowZero()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><recurrence><interval>-1</interval>TEST</recurrence>c</kolab>'
        );
        $this->assertEquals(array(), $attributes['recurrence']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testMissingCycle()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><recurrence><interval>2</interval>TEST</recurrence>c</kolab>'
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testMissingWeekday()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><recurrence cycle="weekly"><interval>1</interval>TEST</recurrence>c</kolab>'
        );
        $this->assertEquals(array(), $attributes['recurrence']);
    }

    public function testWeekly()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><recurrence cycle="weekly"><interval>1</interval><day>1</day>TEST</recurrence>c</kolab>'
        );
        $this->assertEquals(
            array(
                'complete' => '',
                'cycle' => 'weekly',
                'day' => array(1),
                'exclusion' => '',
                'interval' => '1',
                'range' => '',
                'type' => ''
            ),
            $attributes['recurrence']
        );
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('application' => 'TEST'),
                array('element' => 'application')
            )
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><application>TEST</application></kolab>
',
            $this->saveToXml(
                null,
                array('application' => 'TEST'),
                array('element' => 'application')
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><application type="strange">NEW<b/><a/></application>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><application type="strange"><b/>STRANGE<a/></application>c</kolab>',
                array('application' => 'NEW'),
                array('element' => 'application')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(),
            array('element' => 'application')
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><application type="strange"><b/>STRANGE<a/></application>c</kolab>',
                array(),
                array('element' => 'application')
            )
        );
    }

    public function testDeleteCategories()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<application>CAT</application></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><categories type="strange"><b/>CAT<a/></categories>c</kolab>',
                array('application' => 'CAT'),
                array('element' => 'application')
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array(),
                array(
                    'element' => 'application',
                    'relaxed' => true,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Composite_Recurrence';
    }
}
