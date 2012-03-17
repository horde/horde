<?php
/**
 * Test the core Turba class with the Kolab backend.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Test the core Turba class with the Kolab backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Turba_Unit_Turba_KolabTest extends Turba_Unit_Turba_Base
{
    protected $backupGlobals = false;

    /**
     * The default share name expected to be used.
     *
     * @var string
     */
    protected $default_name = 'Contacts';

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        parent::setUpBeforeClass();
        self::createKolabShares(self::$setup);
    }
}
