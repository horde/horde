<?php
/**
 * Basic Wicked test case.
 *
 * PHP version 5
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Basic Wicked test case.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Wicked_TestCase extends PHPUnit_Framework_TestCase
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
        if ($result instanceof PEAR_Error) {
            $this->fail(sprintf('Test failed with: %s', $result->getMessage()));
        }
        return $result;
    }
}