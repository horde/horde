<?php
/**
 * Test the core Nag class with various backends.
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
 * Test the core Nag class with various backends.
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
class Nag_Unit_Nag_Base extends Nag_TestCase
{
    public static function setUpBeforeClass()
    {
        $GLOBALS['prefs'] = new Horde_Prefs('kronolith', new Horde_Prefs_Storage_Null('test'));
        $GLOBALS['registry'] = new Nag_Stub_Registry();
        $GLOBALS['injector'] = self::getInjector();
        parent::setUpBeforeClass();
    }

    public function tearDown()
    {
        foreach ($GLOBALS['nag_shares']->listShares('test') as $share) {
            $GLOBALS['nag_shares']->removeShare($share);
        }
        parent::tearDown();
    }

    public function testCreateDefaultShare()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Nag::initialize();
        $this->assertEquals(1, count($GLOBALS['display_tasklists']));
    }

    public function testDefaultShareName()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Nag::initialize();
        $shares = $GLOBALS['nag_shares']->listShares('test');
        $default = array_pop($shares);
        $this->assertEquals('Task list of test', $default->get('name'));
    }
}
