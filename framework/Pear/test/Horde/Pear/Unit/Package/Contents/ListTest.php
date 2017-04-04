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
 * Test the core content list handler for package.xml files.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Package_Contents_ListTest
extends Horde_Pear_TestCase
{
    public function testCount()
    {
        $this->assertEquals(2, count($this->_getList()->getContents()));
    }

    public function testList()
    {
        $this->_assertListContent(
            array(
                '/lib/Old.php' => array(
                    'role' => 'php',
                    'as' => 'Old.php'
                ),
                '/lib/Stays.php' => array(
                    'role' => 'php',
                    'as' => 'Stays.php'
                ),
            ),
            $this->_getList()->getContents()
        );
    }

    private function _assertListContent($content, $list)
    {
        ksort($content);
        ksort($list);
        $this->assertEquals($content, $list);
    }

    private function _getList($root = null)
    {
        if ($root === null) {
            $root = __DIR__ . '/../../../fixture/horde/framework/simple';
        }
        return new Horde_Pear_Package_Contents_List(
            new Horde_Pear_Package_Type_Horde($root)
        );
    }
}
