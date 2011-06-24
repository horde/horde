<?php

require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Turba
 * @subpackage UnitTests
 */
class Turba_ViewListTest extends Turba_TestBase {

    function setUp()
    {
        parent::setUp();
        $this->setUpDatabase();
        require_once dirname(__FILE__) . '/../View/List.php';
    }

    function callView_List($method, &$numDisplayed, $param = null)
    {
        $GLOBALS['source'] = '_test_sql';
        $GLOBALS['cfgSources'] = array('_test_sql' => $this->getDriverConfig());

        $list = $this->getList();
        $sources = Turba::getColumns();
        $columns = isset($sources['_test_sql']) ? $sources['_test_sql'] : array();
        $view = new Turba_View_List($list, null, $columns);
        $this->_output = $view->$method($numDisplayed, $param);
        $this->assertOk($this->_output);
        $this->assertNoUnwantedPattern('/Fatal error/', $this->_output);
        $this->assertNoUnwantedPattern('/Warning/', $this->_output);
        return $view;
    }

    function test_getAddSources_returns_sources_sorted_by_name()
    {
        $result = Turba_View_List::getAddSources();
        if (!$this->assertOk($result)) {
            return;
        }

        list($addToList, $addToListSources) = $result;

        $groups = $this->_groups;
        sort($groups);
        foreach ($addToList as $item) {
            if (!empty($groups) && !empty($item['name']) &&
                $groups[0] == $item['name']) {
                array_shift($groups);
            }
        }

        $this->assertTrue(empty($groups),
                          "Some group not found or not found in right order.");
    }

    function test_getPage_renders_all_list_items()
    {
        $this->callView_List('getPage', $numDisplayed);
        foreach ($this->_sortedByLastname as $name) {
            $this->assertWantedPattern('/' . preg_quote($name, '/') . '/',
                                       $this->_output);
        }

        $this->assertEqual(count($this->_sortedByLastname), $numDisplayed);
    }

    function test_getAlpha_renders_filtered_items()
    {
        $this->callView_List('getAlpha', $numDisplayed, 'j');
        $count = 0;
        foreach ($this->_sortedByLastname as $name) {
            if (Horde_String::lower($name{0}) == 'j') {
                $this->assertWantedPattern('/' . preg_quote($name, '/') . '/',
                                           $this->_output);
                $count++;
            } else {
                $this->assertNoUnwantedPattern('/' . preg_quote($name, '/') .
                                               '/', $this->_output);
            }
        }

        $this->assertEqual($count, $numDisplayed);
        $this->assertNotEqual(0, $count);
    }

}
