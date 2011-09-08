<?php
/**
 * Test the Kolab driver.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the Kolab driver.
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
class Nag_Unit_Driver_KolabTest extends Nag_Unit_Driver_Base
{
    protected $backupGlobals = false;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$setup->setup(
            array(
                'Horde_Kolab_Storage' => array(
                    'factory' => 'KolabStorage',
                    'params' => array(
                        'imapuser' => 'test',
                    )
                ),
                'Horde_Share_Base' => array(
                    'factory' => 'Share',
                    'method' => 'Kolab',
                ),
            )
        );
        self::$setup->makeGlobal(
            array(
                'nag_shares' => 'Horde_Share_Base',
            )
        );

        $GLOBALS['conf']['tasklists']['driver'] = 'kolab';

        list($share, $other_share) = self::_createDefaultShares();
        self::$driver = new Nag_Driver_Kolab($share->getName());
    }
}
