<?php
/**
 * Basic Wicked test case.
 *
 * PHP version 5
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Basic Wicked test case.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Wicked_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function unstrictPearTestingMode()
    {
        $this->_old_errorreporting = error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
    }

    protected function revertTestingMode()
    {
        if ($this->_old_errorreporting !== null) {
            error_reporting($this->_old_errorreporting);
        }
    }

    protected function protectAgainstPearError($result)
    {
        if ($result instanceOf PEAR_Error) {
            $this->fail(sprintf('Test failed with: %s', $result->getMessage()));
        }
        return $result;
    }
}