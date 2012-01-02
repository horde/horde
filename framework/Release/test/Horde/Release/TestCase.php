<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
Cli
 */

/**
 * Basic test case.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_TestCase
extends Horde_Test_Case
{
    protected function getTemporaryDirectory()
    {
        return Horde_Util::createTempDir();
    }
}
