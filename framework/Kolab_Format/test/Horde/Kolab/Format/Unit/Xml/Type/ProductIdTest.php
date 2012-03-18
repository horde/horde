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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the product ID attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_ProductIdTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadProductId()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id>SOMETHING</product-id>c</kolab>',
            array('element' => 'product-id')
        );
        $this->assertEquals('SOMETHING', $attributes['product-id']);
    }

    public function testLoadStrangeProductId()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>',
            array('element' => 'product-id')
        );
        $this->assertEquals('STRANGE', $attributes['product-id']);
    }

    public function testLoadMissingProductId()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('element' => 'product-id')
        );
        $this->assertEquals('', $attributes['product-id']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->saveToReturn(
                null,
                array(),
                array(
                    'api-version' => 2,
                    'element' => 'product-id',
                )
            )
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><product-id>Horde_Kolab_Format_Xml-@version@ (api version: 2)</product-id></kolab>
',
            $this->saveToXml(
                null,
                array(),
                array(
                    'api-version' => 2,
                    'element' => 'product-id',
                )
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange">Horde_Kolab_Format_Xml-@version@ (api version: 2)<b/><a/></product-id>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><product-id type="strange"><b/>STRANGE<a/></product-id>c</kolab>',
                array(),
                array(
                    'api-version' => 2,
                    'element' => 'product-id',
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_ProductId';
    }
}
