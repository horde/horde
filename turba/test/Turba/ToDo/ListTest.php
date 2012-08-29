<?php

require_once __DIR__ . '/TestBase.php';

/**
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Turba
 * @subpackage UnitTests
 */
class Turba_ToDo_ListTest extends Turba_TestBase {

    function setUp()
    {
        $this->markTestIncomplete('Convert to use Horde_Test.');
        parent::setUp();
        $this->setUpDatabase();
    }

    function test_sort_should_sort_according_to_passed_parameters()
    {
        $this->assertSortsList(array($this, 'sortList'));
    }

    function sortList($order)
    {
        $list = $this->getList();
        $list->sort($order);
        return $list;
    }

}
