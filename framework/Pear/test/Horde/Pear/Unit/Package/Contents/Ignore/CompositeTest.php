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
 * Test the composite ignore handler for package contents.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
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
