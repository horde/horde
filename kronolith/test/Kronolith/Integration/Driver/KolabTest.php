<?php
/**
 * Test the Kolab driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test the Kolab driver.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Kronolith_Integration_Driver_KolabTest extends Kronolith_Integration_Driver_Base
{
    protected $backupGlobals = false;

    public static function setUpBeforeClass()
    {
        return;
        parent::setUpBeforeClass();
        self::createKolabShares(self::$setup);
        list($share, $other_share) = self::_createDefaultShares();
        self::$driver = Kronolith::getDriver('Kolab', $share->getName());
        self::$type = 'Kolab';
    }

    public function setUp()
    {
        $this->markTestIncomplete('Unserialization error from Kolab share objects.');
    }
}
