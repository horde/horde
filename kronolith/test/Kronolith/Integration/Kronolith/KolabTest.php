<?php
/**
 * Test the core Kronolith class with the Kolab backend.
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
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the core Kronolith class with the Kolab backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Kronolith_Integration_Kronolith_KolabTest extends Kronolith_Integration_Kronolith_Base
{
    protected $backupGlobals = false;

    /**
     * The default share name expected to be used.
     *
     * @var string
     */
    protected $default_name = 'Calendar';

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        parent::setUpBeforeClass();
        self::createKolabShares(self::$setup);
    }
}
