<?php
/**
 * Test the SQL driver.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/gpl
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Jason Felice <jason.m.felice@gmail.com>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the restructured text renderer.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Wicked_Unit_Driver_SqlTest extends Wicked_TestCase
{
    /**
     * Driver we are testing
     * @var object
     */
    public $wicked;

    public function setUp()
    {
        $this->markTestIncomplete('This is a very old test case that certainly does not work in its current form. But it may still be useful to be rescued.');

        @define('WICKED_BASE', __DIR__ . '/../..');
        @define('TEST_PAGE_1', 'driver-pages.phpt Test Page One');
        @define('TEST_PAGE_2', 'Renamed driver-pages.phpt Test Page (Called "Two")');

        $this->wicked = Wicked_Driver::factory('sql', $this->getTestDatabaseSQLDriverConfig());
    }

    public function test_Driver_newPage_should_successfully_create_a_page()
    {
        $this->wicked->removeAllVersions(TEST_PAGE_1);

        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_1));

        $this->wicked->newPage(TEST_PAGE_1, 'This is a test.');
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_1));

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertEqual('This is a test.', $page['page_text']);
    }

    public function test_updateText_should_also_update_history()
    {
        $this->wicked->updateText(TEST_PAGE_1, 'Here\'s the new page text.',
                                  'Test change.');
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertEqual('Here\'s the new page text.', $page['page_text']);

        $last_version = $page['page_version'];
        $this->wicked->updateText(TEST_PAGE_1, 'Here\'s the second change.',
                                  'Test change 2.');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertEqual('Here\'s the second change.', $page['page_text']);

        $res = $this->wicked->retrieveHistory(TEST_PAGE_1, $last_version);
        $this->assertNotEqual(0, count($res),
                              "no results from retrieveHistory()");
        $page = $res[0];
        $this->assertEqual('Here\'s the new page text.', $page['page_text']);
    }

    public function testGetHistoryAndRemoveVersion()
    {
        $history = $this->wicked->getHistory(TEST_PAGE_1);
        $this->assertFalse(count($history) < 2, "need more history to test");

        $nvers = count($history);
        $item_1 = $history[0];
        $item_1_ver = $item_1['page_version'];

        $this->wicked->removeVersion(TEST_PAGE_1, $item_1_ver);
        $history = $this->wicked->getHistory(TEST_PAGE_1);
        $this->assertEqual(count($history), ($nvers - 1));

        foreach ($history as $page) {
            $testver = $page['page_version'];
            $this->assertNotEqual($testver, $item_1_ver,
                                  "removeVersion() version still there.");
        }
    }

    public function testLock()
    {
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertFalse($page['locked']);

        $this->wicked->lock(TEST_PAGE_1, true);
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertTrue($page['locked']);

        $this->wicked->lock(TEST_PAGE_1, false);
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertFalse($page['locked']);
    }

    public function test_logPageView_should_increment_hit_counter()
    {
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $hits = $page['page_hits'];
        $this->wicked->logPageView(TEST_PAGE_1);
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertEqual($page['page_hits'], $hits + 1);
    }

    public function testRenamePage()
    {
        $this->wicked->renamePage(TEST_PAGE_1, TEST_PAGE_2);
        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_1));
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_2));

        $this->wicked->renamePage(TEST_PAGE_2, TEST_PAGE_1);
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_1));
        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_2));
    }

    public function testGetPagesAndGetAllPages()
    {
        $pages = $this->wicked->getPages(false);
        $allPages = $this->wicked->getAllPages();
        $this->assertEqual(count($allPages), count($pages));

        $allPageNames = array();
        foreach ($allPages as $allPage) {
            $allPageNames[] = $allPage['page_name'];
        }

        $this->assertFalse(count(array_diff($pages, $allPageNames)) > 0);
        $this->assertFalse(count(array_diff($allPageNames, $pages)) > 0);
    }

    public function test_mostPopular_call_should_not_fail()
    {
        $this->wicked->mostPopular();
    }

    public function test_leastPopular_call_should_not_fail()
    {
        $this->wicked->leastPopular();
    }

    public function test_recentChanges_call_should_not_fail()
    {
        $this->wicked->getRecentChanges();
    }

    public function testSearches()
    {
        $res = $this->wicked->searchTitles('.phpt');
        $this->assertFalse(count($res) < 1, "didn't find all the pages.");

        $res = $this->wicked->searchText('second change');
        $this->assertFalse(count($res) < 1, "didn't find all the pages.");

        $this->wicked->getLikePages('Wiki');
    }

    public function test_removeAllVersions_should_not_leave_any_versions()
    {
        $this->wicked->removeAllVersions(TEST_PAGE_1);
        $this->assertFalse($this->wicked->pageExists("TEXT_PAGE_1"));
    }

}
