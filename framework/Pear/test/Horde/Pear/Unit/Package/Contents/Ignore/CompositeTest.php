<?php
/**
 * Test the composite ignore handler for package contents.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the composite ignore handler for package contents.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Package_Contents_Ignore_CompositeTest
extends Horde_Pear_TestCase
{
    public function testAny()
    {
        $this->_checkNotIgnored('/a/ANY');
    }

    public function testTemporary()
    {
        $this->_checkIgnored('/a/ANY~');
    }

    public function testConfPhp()
    {
        $this->_checkIgnored('/a/conf.php');
    }

    public function testCVS()
    {
        $this->_checkIgnored('/a/APP/CVS/test');
    }

    public function testDotDot()
    {
        $this->_checkIgnored('/a/..');
    }

    public function testHidden()
    {
        $this->_checkIgnored('/a/.hidden');
    }

    private function _checkIgnored($file)
    {
        $this->assertTrue(
            $this->_getIgnore()->isIgnored(new SplFileInfo($file))
        );
    }

    private function _checkNotIgnored($file)
    {
        $this->assertFalse(
            $this->_getIgnore()->isIgnored(new SplFileInfo($file))
        );
    }

    private function _getIgnore()
    {
        return new Horde_Pear_Package_Contents_Ignore_Composite(
            array(
                new Horde_Pear_Package_Contents_Ignore_Patterns(
                    array('*~', 'conf.php', 'CVS/*'), '/'
                ),
                new Horde_Pear_Package_Contents_Ignore_Dot(),
                new Horde_Pear_Package_Contents_Ignore_Hidden()
            )
        );
    }
}
