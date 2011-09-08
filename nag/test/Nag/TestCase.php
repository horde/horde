<?php
/**
 * Basic Nag test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Basic Nag test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Nag_TestCase
extends PHPUnit_Framework_TestCase
{
    static protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    static public function _createDefaultShares()
    {
        $share = self::_createShare(
            'Tasklist of Tester', 'test@example.com'
        );
        $other_share = self::_createShare(
            'Other tasklist of Tester', 'test@example.com'
        );
        return array($share, $other_share);
    }

    static private function _createShare($name, $owner)
    {
        $share = $GLOBALS['nag_shares']->newShare(
            $owner, strval(new Horde_Support_Randomid()), $name
        );
        $GLOBALS['nag_shares']->addShare($share);
        return $share;
    }
}