<?php
/**
 * Test the hidden file ignore handler for package contents.
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
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the hidden file ignore handler for package contents.
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
class Horde_Pear_Unit_Package_Contents_Ignore_HiddenTest
extends Horde_Pear_TestCase
{
    public function testAny()
    {
        $this->_checkNotIgnored('ANY');
    }

    public function testHidden()
    {
        $this->_checkIgnored('.ANY');
    }

    public function testDot()
    {
        $this->_checkIgnored('.');
    }

    public function testDotDot()
    {
        $this->_checkIgnored('..');
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
        return new Horde_Pear_Package_Contents_Ignore_Hidden();
    }
}
