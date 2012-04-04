<?php

// Sorting Constants

/** Sort by stock id. */
define('SESHA_SORT_STOCKID', 100);
/** Sort by stock name. */
define('SESHA_SORT_NAME', 101);
/** Sort by stock note. */
define('SESHA_SORT_NOTE', 102);
/** Sort in ascending order. */
define('SESHA_SORT_ASCEND', 0);
/** Sort in descending order. */
define('SESHA_SORT_DESCEND', 1);

// Search Field Constants

define('SESHA_SEARCH_ID', 1);
define('SESHA_SEARCH_NAME', 2);
define('SESHA_SEARCH_NOTE', 4);
define('SESHA_SEARCH_PROPERTY', 8);

/**
 * This is the base Sesha class.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
class Sesha
{
    /** Sort by stock id. */
    const SESHA_SORT_STOCKID = 100;
    /** Sort by stock name. */
    const SESHA_SORT_NAME = 101;
    /** Sort by stock note. */
    const SESHA_SORT_NOTE = 102;
    /** Sort in ascending order. */
    const SESHA_SORT_ASCEND = 0;
    /** Sort in descending order. */
    const SESHA_SORT_DESCEND = 1;

    // Search Field Constants

    const SESHA_SEARCH_ID = 1;
    const SESHA_SEARCH_NAME = 2;
    const SESHA_SEARCH_NOTE = 4;
    const SESHA_SEARCH_PROPERTY = 8;


    /**
     * This function will return the inventory based on current category
     * filters.
     *
     * @param constant $sortby       The field to sort the inventory on.
     * @param constant $sortdir      The direction to sort the inventory.
     * @param integer  $category_id  The category ID of stock to fetch.
     * @param string   $what         The criteria to search on.
     * @param integer  $where        The locations to search in (bitmask).
     *
     * @return mixed  Array of inventory on success; PEAR_Error on failure.
     */
    function listStock($sortby = null, $sortdir = null, $category_id = null,
                       $what = null, $where = null)
    {
        global $prefs;

        if (is_null($sortby)) {
            $sortby = $prefs->getValue('sortby');
        }
        if (is_null($sortdir)) {
            $sortdir = $prefs->getValue('sortdir');
        }

        // Sorting functions
        $sort_functions = array(
            SESHA_SORT_STOCKID => 'ByStockID',
            SESHA_SORT_NAME    => 'ByName',
            SESHA_SORT_NOTE    => 'ByNote',
        );

        $list_property_ids = @unserialize($prefs->getValue('list_properties'));

        // Retrieve the inventory from the storage driver
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
        if (!is_null($what) && !is_null($where)) {
            $inventory = $sesha_driver->searchStock($what, $where, $list_property_ids);
        } else {
            $inventory = $sesha_driver->listStock($category_id, $list_property_ids);
        }

        // Sort the inventory if there is a sort function defined
        if (count($inventory)) {
            $prefix = ($sortdir == self::SESHA_SORT_DESCEND) ? '_rsort' : '_sort';
            if (isset($sort_functions[$sortby])) {
                uasort($inventory, array('Sesha', $prefix .
                    $sort_functions[$sortby]));
            } elseif (substr($sortby, 0, 1) == 'p' && in_array(substr($sortby, 1), $list_property_ids)) {
                $GLOBALS['_sort_property'] = $sortby;
                uasort($inventory, array('Sesha', $prefix . 'ByProperty'));
            }
        }

        return $inventory;
    }

    /**
     * This function will return the list of available categories.
     *
     * @return mixed  Array of categories on success; PEAR_Error on failure.
     */
    function listCategories()
    {
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
        return $sesha_driver->getCategories();
    }

    /**
     * Returns a Hord_Form_Type_stringlist value split to an array.
     *
     * @param string $string  A comma separated string list.
     *
     * @return array  The string list as an array.
     */
    function getStringlistArray($string)
    {
        $string = str_replace("'", "\'", $string);
        $values = explode(',', $string);

        foreach ($values as $value) {
            $value = trim($value);
            $value_array[$value] = $value;
        }

        return $value_array;
    }

   /**
     * Comparison function for sorting inventory stock by id.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  1 if item one is greater, -1 if item two is greater;
     *                  0 if they are equal.
     */
    function _sortByStockID($a, $b)
    {
        if ($a['stock_id'] == $b['stock_id']) return 0;
        return ($a['stock_id'] > $b['stock_id']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting stock by id.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  -1 if item one is greater, 1 if item two is greater;
     *                  0 if they are equal.
     */
    function _rsortByStockID($a, $b)
    {
        if ($a['stock_id'] == $b['stock_id']) return 0;
        return ($a['stock_id'] > $b['stock_id']) ? -1 : 1;
    }

    /**
     * Comparison function for sorting inventory stock by name.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  1 if item one is greater, -1 if item two is greater;
     *                  0 if they are equal.
     */
    function _sortByName($a, $b)
    {
        if ($a['stock_name'] == $b['stock_name']) return 0;
        return ($a['stock_name'] > $b['stock_name']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting stock by name.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  -1 if item one is greater, 1 if item two is greater;
     *                  0 if they are equal.
     */
    function _rsortByName($a, $b)
    {
        if ($a['stock_name'] == $b['stock_name']) return 0;
        return ($a['stock_name'] > $b['stock_name']) ? -1 : 1;
    }

    /**
     * Comparison function for sorting inventory stock by a property.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  1 if item one is greater, -1 if item two is greater;
     *                  0 if they are equal.
     */
    function _sortByProperty($a, $b)
    {
        if ($a[$GLOBALS['_sort_property']] == $b[$GLOBALS['_sort_property']]) return 0;
        return ($a[$GLOBALS['_sort_property']] > $b[$GLOBALS['_sort_property']]) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting stock by a property.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  -1 if item one is greater, 1 if item two is greater;
     *                  0 if they are equal.
     */
    function _rsortByProperty($a, $b)
    {
        if ($a[$GLOBALS['_sort_property']] == $b[$GLOBALS['_sort_property']]) return 0;
        return ($a[$GLOBALS['_sort_property']] > $b[$GLOBALS['_sort_property']]) ? -1 : 1;
    }

    /**
     * Comparison function for sorting inventory stock by note.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  1 if item one is greater, -1 if item two is greater;
     *                  0 if they are equal.
     */
    function _sortByNote($a, $b)
    {
        if ($a['note'] == $b['note']) return 0;
        return ($a['note'] > $b['note']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting stock by note.
     *
     * @param array $a  Item one.
     * @param array $b  Item two.
     *
     * @return integer  -1 if item one is greater, 1 if item two is greater;
     *                  0 if they are equal.
     */
    function _rsortByNote($a, $b)
    {
        if ($a['note'] == $b['note']) return 0;
        return ($a['note'] > $b['note']) ? -1 : 1;
    }

    /**
     * Build Sesha's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $registry, $conf, $browser, $print_link, $perms;
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $menu = new Horde_Menu();
        $menu->add(Horde::url('list.php'), _("_List Stock"), 'sesha.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        if (Sesha::isAdmin(Horde_Perms::READ)|| $perms->hasPermission('sesha:addStock', $GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            $menu->add(Horde::url(Horde_Util::addParameter('stock.php', 'actionId', 'add_stock')), _("_Add Stock"), 'stock.png');
            $menu->add(Horde::url('admin.php'), _("Admin"), 'sesha.png');
        }
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        /* Print. */
        if ($conf['menu']['print'] && isset($print_link) && $browser->hasFeature('javascript')) {
            $menu->add("javascript:popup('$print_link'); return false;", _("_Print"), 'print.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    public static function isAdmin($permLevel = Horde_Perms::DELETE)
    {
        return ($GLOBALS['registry']->isAdmin() || $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('sesha:admin', $GLOBALS['registry']->getAuth(), $permLevel));
    }
}
