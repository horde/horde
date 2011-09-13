<?php
/**
 * Test the date-time attribute handler.
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
 * Test the date-time attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_DateTimeTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime>2011-06-29</datetime>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertTrue($attributes['datetime']['date-only']);
    }

    public function testLoadDateValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime>2011-06-29</datetime>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals(
            '2011-06-29T00:00:00+00:00',
            $attributes['datetime']['date']->format('c')
        );
    }

    public function testLoadTimezoneValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime tz="Europe/Berlin">2011-06-29</datetime>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals(
            '2011-06-29T00:00:00+02:00',
            $attributes['datetime']['date']->format('c')
        );
    }

    public function testLoadStrangeDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>2011-06-29<a/></datetime>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals(
            '2011-06-29T00:00:00+00:00',
            $attributes['datetime']['date']->format('c')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadEmptyDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime></datetime></kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
    }

    public function testLoadMissingDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertFalse(isset($attributes['datetime']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('date' => new DateTime())
            )
        );
        $this->assertInstanceOf('DateTime', $attributes['datetime']['date']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            )
        );
        $this->assertFalse(isset($attributes['datetime']));
    }

    public function testSaveDateTime()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime tz="UTC">2011-06-29T11:11:11Z</datetime></kolab>
',
            $this->saveToXml(
                null,
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('UTC')
                        )
                    )
                ),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveTimeZone()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime tz="Europe/Berlin">2011-06-29T11:11:11</datetime></kolab>
',
            $this->saveToXml(
                null,
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('Europe/Berlin')
                        )
                    )
                ),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange" tz="Europe/Berlin">2011-06-29<b/><a/></datetime>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>',
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'date-only' => true
                    )
                ),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
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
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>',
                array(),
                array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,)
            )
        );
    }

    public function testDeleteNode()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>',
                array(),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertFalse(
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array(),
                array(
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_DateTime';
    }
}
