<?php
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
    const SORT_STOCKID = 100;
    /** Sort by stock name. */
    const SORT_NAME = 101;
    /** Sort by stock note. */
    const SORT_NOTE = 102;
    /** Sort in ascending order. */
    const SORT_ASCEND = 0;
    /** Sort in descending order. */
    const SORT_DESCEND = 1;

    // Search Field Constants

    const SEARCH_ID = 1;
    const SEARCH_NAME = 2;
    const SEARCH_NOTE = 4;
    const SEARCH_PROPERTY = 8;

    /**
     * This function will return the list of available categories.
     *
     * @return mixed  Array of categories on success; PEAR_Error on failure.
     */
    public function listCategories()
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
    public function getStringlistArray($string)
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
    protected function _sortByStockID($a, $b)
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
    protected function _rsortByStockID($a, $b)
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
    protected function _sortByName($a, $b)
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
    protected function _rsortByName($a, $b)
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
    protected function _sortByProperty($a, $b)
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
    protected function _rsortByProperty($a, $b)
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
    protected function _sortByNote($a, $b)
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
    protected function _rsortByNote($a, $b)
    {
        if ($a['note'] == $b['note']) return 0;
        return ($a['note'] > $b['note']) ? -1 : 1;
    }

    public static function isAdmin($permLevel = Horde_Perms::DELETE)
    {
        return ($GLOBALS['registry']->isAdmin() || $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('sesha:admin', $GLOBALS['registry']->getAuth(), $permLevel));
    }
}
