<?php
/**
 * The Horde_Array:: class provides various methods for array manipulation.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Util
 */
class Horde_Array
{
    /**
     * Prepare a list of addresses for storage.
     * Namely, trims and lowercases all addresses and then sort.
     *
     * @param array $addr  The list of addresses.
     *
     * @return array  The list of addresses, prepared for storage.
     */
    static public function prepareAddressList(array $addr)
    {
        /* Remove any extra space in the address and make it lowercase. */
        $addr = array_map(array('Horde_String', 'lower'), array_map('trim', $addr));

        /* Remove duplicate entries. */
        $addr = array_keys(array_flip($addr));

        /* Sort the list. */
        usort($addr, array(__CLASS__, 'sortAddressList'));

        return $addr;
    }

    /**
     * Function used by usort() to sort an address list.
     *
     * @param string $a  Address #1.
     * @param string $b  Address #2.
     *
     * @return integer  -1, 0, or 1.
     */
    static public function sortAddressList($a, $b)
    {
        $a = explode('@', $a);
        $b = explode('@', $b);

        /* One of the addresses doesn't have a host name. */
        if (empty($a[0])) {
            array_shift($a);
        }
        if (empty($b[0])) {
            array_shift($b);
        }
        if (count($a) != count($b)) {
            return (count($a) > count($b));
        }

        /* The addresses have different hostname or not hostname and
         * different mailbox names. */
        if ($a[(count($a) - 1)] != $b[(count($b) - 1)]) {
            return strcmp($a[(count($a) - 1)], $b[(count($b) - 1)]);
        }

        /* Compare mailbox names. */
        return strcmp($a[0], $b[0]);
    }

    /**
     * Sorts an array on a specified key. If the key does not exist,
     * defaults to the first key of the array.
     *
     * @param array &$array   The array to be sorted, passed by reference.
     * @param string $key     The key by which to sort. If not specified then
     *                        the first key is used.
     * @param integer $dir    Sort direction:
     *                          0 = ascending (default)
     *                          1 = descending
     * @param boolean $assoc  Keep key value association?
     */
    static public function arraySort(array &$array, $key = null, $dir = 0,
                                     $assoc = true)
    {
        /* Return if the array is empty. */
        if (empty($array)) {
            return;
        }

        /* If no key to sort by is specified, use the first key of the
         * first element. */
        if (is_null($key)) {
            $keys = array_keys(reset($array));
            $key = array_shift($keys);
        }

        /* Call the appropriate sort function. */
        $helper = new Horde_Array_Sort_Helper();
        $helper->key = $key;
        $function = $dir ? 'reverseCompare' : 'compare';
        if ($assoc) {
            uasort($array, array($helper, $function));
        } else {
            usort($array, array($helper, $function));
        }
    }

    /**
     * Given an HTML type array field "example[key1][key2][key3]" breaks up
     * the keys so that they could be used to reference a regular PHP array.
     *
     * @param string $field  The field name to be examined.
     * @param string &$base  Will be set to the base element.
     * @param array &$keys   Will be set to the list of keys.
     *
     * @return boolean  True on sucess, false on error.
     */
    static public function getArrayParts($field, &$base, &$keys)
    {
        if (!preg_match('|([^\[]*)((\[[^\[\]]*\])+)|', $field, $matches)) {
            return false;
        }

        $base = $matches[1];
        $keys = explode('][', $matches[2]);
        $keys[0] = substr($keys[0], 1);
        $keys[count($keys) - 1] = substr($keys[count($keys) - 1], 0, strlen($keys[count($keys) - 1]) - 1);
        return true;
    }

    /**
     * Using an array of keys iterate through the array following the
     * keys to find the final key value. If a value is passed then set
     * that value.
     *
     * @param array &$array  The array to be used.
     * @param array &$keys   The key path to follow as an array.
     * @param array $value   If set the target element will have this value set
     *                       to it.
     *
     * @return mixed  The final value of the key path.
     */
    static public function getElement(&$array, array &$keys, $value = null)
    {
        if (count($keys)) {
            $key = array_shift($keys);
            return isset($array[$key])
                ? self::getElement($array[$key], $keys, $value)
                : false;
        }

        if (!is_null($value)) {
            $array = $value;
        }

        return $array;
    }

    /**
     * Returns a rectangle of a two-dimensional array.
     *
     * @param array   $array   The array to extract the rectangle from.
     * @param integer $row     The start row of the rectangle.
     * @param integer $col     The start column of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param integer $width   The width of the rectangle.
     *
     * @return array  The extracted rectangle.
     */
    static public function getRectangle(array $array, $row, $col, $height,
                                        $width)
    {
        $rec = array();
        for ($y = $row; $y < $row + $height; $y++) {
            $rec[] = array_slice($array[$y], $col, $width);
        }
        return $rec;
    }

    /**
     * Given an array, returns an associative array with each element key
     * derived from its value.
     * For example:
     *   array(0 => 'foo', 1 => 'bar')
     * would become:
     *   array('foo' => 'foo', 'bar' => 'bar')
     *
     * @param array $array  An array of values.
     *
     * @return array  An array with keys the same as values.
     */
    static public function valuesToKeys(array $array)
    {
        return $array
            ? array_combine($array, $array)
            : array();
    }

    /**
     * Backported array_replace_recursive().
     *
     * @todo Remove when requiring PHP 5.3.
     *
     * @param array $a1  The old array.
     * @param array $a2  The new array.
     *
     * @return array  The merged array.
     */
    static public function replaceRecursive(array $a1, array $a2)
    {
        if (function_exists('array_replace_recursive')) {
            return array_replace_recursive($a1, $a2);
        }

        foreach ($a2 as $key => $val) {
            if (!isset($a1[$key])) {
                $a1[$key] = array();
            }

            $a1[$key] = (is_array($val))
                ? self::replaceRecursive($a1[$key], $val)
                : $val;
        }

        return $a1;
    }

}
