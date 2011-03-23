<?php
/**
 * Test the core content list handler for package.xml files.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the core content list handler for package.xml files.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Package_Contents_ListTest
extends Horde_Pear_TestCase
{
    public function testCount()
    {
        $this->assertEquals(5, count($this->_getList()->getContents()));
    }

    public function testList()
    {
        $this->_assertListContent(
            array('.', '..', 'Old.php', 'Stays.php', 'package.xml'),
            $this->_getList()->getContents()
        );
    }

    private function _assertListContent($content, $list)
    {
        $elements = array();
        foreach ($list as $element) {
            $elements[] = $element->getFilename();
        }
        sort($content);
        sort($elements);
        $this->assertEquals($content, $elements);
    }

    private function _getList($root = null)
    {
        if ($root === null) {
            $root = dirname(__FILE__) . '/../../../fixture/simple';
        }
        return new Horde_Pear_Package_Contents_List(
            $root,
            new Horde_Pear_Package_Contents_Include_All(),
            new Horde_Pear_Package_Contents_Ignore_Nothing()
        );
    }
}
