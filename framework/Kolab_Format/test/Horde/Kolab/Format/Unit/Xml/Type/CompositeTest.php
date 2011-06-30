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
extends Horde_Kolab_Format_TestCase
{
    public function testLoadComposite()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><composite><uid>a&amp;</uid><test>TEST</test></composite></kolab>',
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
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
        $this->assertEquals(array('test' => 'TEST', 'uid' => 'a&'), $attributes['composite']);
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'array' => array(),
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('X' => 'Y'),
            )
        );
        $this->assertEquals(array('X' => 'Y'), $attributes['composite']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadNotEmpty()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'array' => array(),
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
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true
            )
        );
        $this->assertFalse(isset($attributes['composite']));
    }

    public function testSaveComposite()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><composite><uid></uid><test></test></composite></kolab>
',
            $this->saveToXml(
                null,
                array('composite' => array()),
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
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
            )
        );
    }

    public function testSaveModifiesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/><uid>1</uid><test>&amp;</test></composite>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c</kolab>',
                array('composite' => array('uid' => 1, 'test' => '&')),
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
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
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
            array(
                'array' => array(),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c</kolab>',
                array(),
                array(
                    'array' => array(),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
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
                    'array' => array(),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true,
                )
            )
        );
    }

    public function testDeleteComposite()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<y/></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><composite type="strange"><b/>STRANGE<a/></composite>c<y/><composite>a</composite></kolab>',
                array(),
                array(
                    'array' => array(),
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Composite';
    }
}
