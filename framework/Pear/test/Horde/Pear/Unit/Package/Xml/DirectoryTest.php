<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */

/**
 * Test the directory handler.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Package_Xml_DirectoryTest
extends Horde_Pear_TestCase
{
    public function testGetFiles()
    {
        $this->assertEquals(
            array(
                '/lib/Old.php',
                '/lib/Stays.php',
                '/test.php'
            ),
            $this->_getList(__DIR__ . '/../../../fixture/horde/framework/directory')->getFiles()
        );
    }

    private function _getList($package)
    {
        $xml = new Horde_Pear_Package_Xml(
            fopen($package . '/package.xml', 'r')
        );
        $element = new Horde_Pear_Package_Xml_Element_Directory('/');
        $element->setDocument($xml);
        $element->setDirectoryNode(
            $xml->findNode('/p:package/p:contents/p:dir')
        );
        return new Horde_Pear_Package_Xml_Directory($element, $xml);
    }
}
