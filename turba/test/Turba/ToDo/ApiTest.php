<?php
/**
 * Test the Turba API.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the Turba API.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Turba_ToDo_ApiTest extends Turba_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Convert to use Horde_Test.');
    }

    public function testSearchApi()
    {
        global $registry;

        /* HACK: ensure we've included this so that it won't get included
         * again, then override the globals it provides. */
        try {
            $pushed = $registry->pushApp('turba', array('check_perms' => false));
        } catch (Horde_Exception $e) {
            return;
        }

        $GLOBALS['source'] = '_test_sql';
        $GLOBALS['cfgSources'] = array('_test_sql' => $this->getDriverConfig());

        $this->fakeAuth();

        $results = _turba_search(array('Fabetes'));
        $this->assertNotEqual(0, count($results));
        if ($this->assertTrue(!empty($results['Fabetes']))) {
            $entry = array_shift($results['Fabetes']);
            $this->assertEqual('_test_sql', $entry['source']);
            $this->assertEqual('Joe Fabetes', $entry['name']);
        }

        if ($pushed) {
            $registry->popApp();
        }
    }

}
