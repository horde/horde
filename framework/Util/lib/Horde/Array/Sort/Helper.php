<?php
/**
 * Helper class for sorting arrays on arbitrary criteria for usort/uasort.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Util
 */
class Horde_Array_Sort_Helper
{
    /**
     * The array key to sort by.
     *
     * @var string
     */
    public $key;

    /**
     * Compare two associative arrays by the array key defined in self::$key.
     *
     * @param array $a
     * @param array $b
     */
    public function compare($a, $b)
    {
        return strcoll(Horde_String::lower($a[$this->key], true), Horde_String::lower($b[$this->key], true));
    }

    /**
     * Compare, in reverse order, two associative arrays by the array key
     * defined in self::$key.
     *
     * @param scalar $a  TODO
     * @param scalar $b  TODO
     *
     * @return TODO
     */
    public function reverseCompare($a, $b)
    {
        return strcoll(Horde_String::lower($b[$this->key], true), Horde_String::lower($a[$this->key], true));
    }

    /**
     * Compare array keys case insensitively for uksort.
     *
     * @param scalar $a  TODO
     * @param scalar $b  TODO
     *
     * @return TODO
     */
    public function compareKeys($a, $b)
    {
        return strcoll(Horde_String::lower($a, true), Horde_String::lower($b, true));
    }

    /**
     * Compare, in reverse order, array keys case insensitively for uksort.
     *
     * @param scalar $a  TODO
     * @param scalar $b  TODO
     *
     * @return TODO
     */
    public function reverseCompareKeys($a, $b)
    {
        return strcoll(Horde_String::lower($b, true), Horde_String::lower($a, true));
    }
}
