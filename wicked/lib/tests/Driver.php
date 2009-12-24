<?php

class Wicked_Driver_TC extends HordeUnitTestCase {

    /**
     * Driver we are testing
     * @var object
     */
    var $wicked;

    function setUp()
    {
        @define('WICKED_BASE', dirname(__FILE__) . '/../..');
        @define('TEST_PAGE_1', 'driver-pages.phpt Test Page One');
        @define('TEST_PAGE_2', 'Renamed driver-pages.phpt Test Page (Called "Two")');

        require_once WICKED_BASE . '/lib/Driver.php';
        require_once WICKED_BASE . '/lib/Wicked.php';

        $this->wicked = Wicked_Driver::factory('sql', $this->getTestDatabaseSQLDriverConfig());
        $this->assertNotA($this->wicked, 'PEAR_Error');
    }

    function test_Driver_newPage_should_successfully_create_a_page()
    {
        $this->wicked->removeAllVersions(TEST_PAGE_1);

        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_1));

        $res = $this->wicked->newPage(TEST_PAGE_1, 'This is a test.');
        $this->assertNotA($res, 'PEAR_Error');
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_1));

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertEqual('This is a test.', $page['page_text']);
    }

    function test_updateText_should_also_update_history()
    {
        $res = $this->wicked->updateText(TEST_PAGE_1, 'Here\'s the new page text.',
                                   'Test change.', true);
        $this->assertNotA($res, 'PEAR_Error');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertEqual('Here\'s the new page text.', $page['page_text']);

        $last_version = sprintf('%d.%d', $page['page_majorversion'],
                                $page['page_minorversion']);

        $res = $this->wicked->updateText(TEST_PAGE_1, 'Here\'s the second change.',
                                   'Test change 2.', false);
        $this->assertNotA($res, 'PEAR_Error');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertEqual('Here\'s the second change.', $page['page_text']);

        $res = $this->wicked->retrieveHistory(TEST_PAGE_1, $last_version);
        $this->assertNotA($res, 'PEAR_Error');

        $this->assertNotEqual(0, count($res),
                              "no results from retrieveHistory()");
        $page = $res[0];
        $this->assertEqual('Here\'s the new page text.', $page['page_text']);
    }

    function testGetHistoryAndRemoveVersion()
    {
        $history = $this->wicked->getHistory(TEST_PAGE_1);
        $this->assertNotA($history, 'PEAR_Error');
        $this->assertFalse(count($history) < 2, "need more history to test");

        $nvers = count($history);
        $item_1 = $history[0];
        $item_1_ver = sprintf('%d.%d', $item_1['page_majorversion'],
                              $item_1['page_minorversion']);

        $res = $this->wicked->removeVersion(TEST_PAGE_1, $item_1_ver);
        $this->assertNotA($res, 'PEAR_Error');

        $history = $this->wicked->getHistory(TEST_PAGE_1);
        $this->assertNotA($history, 'PEAR_Error');
        $this->assertEqual(count($history), ($nvers - 1));

        foreach ($history as $page) {
            $testver = sprintf('%d.%d', $page['page_majorversion'],
                               $page['page_minorversion']);
            $this->assertNotEqual($testver, $item_1_ver,
                                  "removeVersion() version still there.");
        }
    }

    function testLock()
    {
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertFalse($page['locked']);

        $res = $this->wicked->lock(TEST_PAGE_1, true);
        $this->assertNotA($res, 'PEAR_Error');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertTrue($page['locked']);

        $res = $this->wicked->lock(TEST_PAGE_1, false);
        $this->assertNotA($res, 'PEAR_Error');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');
        $this->assertFalse($page['locked']);
    }

    function test_logPageView_should_increment_hit_counter()
    {
        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');

        $hits = $page['page_hits'];

        $res = $this->wicked->logPageView(TEST_PAGE_1);
        $this->assertNotA($res, 'PEAR_Error');

        $page = $this->wicked->retrieveByName(TEST_PAGE_1);
        $this->assertNotA($page, 'PEAR_Error');

        $this->assertEqual($page['page_hits'], $hits + 1);
    }

    function testRenamePage()
    {
        $res = $this->wicked->renamePage(TEST_PAGE_1, TEST_PAGE_2);
        $this->assertNotA($res, 'PEAR_Error');

        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_1));
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_2));

        $res = $this->wicked->renamePage(TEST_PAGE_2, TEST_PAGE_1);
        $this->assertTrue($this->wicked->pageExists(TEST_PAGE_1));
        $this->assertFalse($this->wicked->pageExists(TEST_PAGE_2));
    }

    function testGetPagesAndGetAllPages()
    {
        $pages = $this->wicked->getPages(false);
        $this->assertNotA($pages, 'PEAR_Error');

        $allPages = $this->wicked->getAllPages();
        $this->assertNotA($allPages, 'PEAR_Error');
        $this->assertEqual(count($allPages), count($pages));

        $allPageNames = array();
        foreach ($allPages as $allPage) {
            $allPageNames[] = $allPage['page_name'];
        }

        $this->assertFalse(count(array_diff($pages, $allPageNames)) > 0);
        $this->assertFalse(count(array_diff($allPageNames, $pages)) > 0);
    }

    function test_mostPopular_call_should_not_fail()
    {
        $res = $this->wicked->mostPopular();
        $this->assertNotA($res, 'PEAR_Error');
    }

    function test_leastPopular_call_should_not_fail()
    {
        $res = $this->wicked->leastPopular();
        $this->assertNotA($res, 'PEAR_Error');
    }

    function test_recentChanges_call_should_not_fail()
    {
        $res = $this->wicked->getRecentChanges();
        $this->assertNotA($res, 'PEAR_Error');
    }

    function testSearches()
    {
        $res = $this->wicked->searchTitles('.phpt');
        $this->assertNotA($res, 'PEAR_Error');

        $this->assertFalse(count($res) < 1, "didn't find all the pages.");

        $res = $this->wicked->searchText('second change');
        $this->assertNotA($res, 'PEAR_Error');
        $this->assertFalse(count($res) < 1, "didn't find all the pages.");

        $res = $this->wicked->getLikePages('Wiki');
        $this->assertNotA($res, 'PEAR_Error');
    }

    function test_removeAllVersions_should_not_leave_any_versions()
    {
        $res = $this->wicked->removeAllVersions(TEST_PAGE_1);
        $this->assertNotA($res, 'PEAR_Error');
        $this->assertFalse($this->wicked->pageExists("TEXT_PAGE_1"));
    }

}
