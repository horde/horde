<?php
/**
 * Copyright 1999-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */

/**
 * Horde_Text_Filter is a parent class for defining stackable text filters.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @deprecated
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use (class must extend Horde_Text_Filter_Base).
     * @param array $params   A hash containing any additional configuration
     *                        parameters a subclass might need.
     *
     * @return Horde_Text_Filter_Base  The newly created concrete instance.
     * @throws Horde_Text_Filter_Exception
     */
    public static function factory($driver, $params = array())
    {
        /* Base drivers (in Filter/ directory). */
        $class = __CLASS__ . '_' . Horde_String::ucfirst(basename($driver));
        if (class_exists($class)) {
            return new $class($params);
        }

        /* Explicit class name, */
        $class = $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Text_Filter_Exception(__CLASS__ . ': Class definition of ' . $driver . ' not found.');
    }

    /**
     * Applies a set of patterns to a block of text.
     *
     * @param string $text    The text to filter.
     * @param mixed $filters  The list of filters (or a single filter).
     * @param mixed $params   The list of params to use with each filter.
     *
     * @return string  The transformed text.
     * @throws Horde_Text_Filter_Exception
     */
    public static function filter($text, $filters = array(), $params = array())
    {
        if (!is_array($filters)) {
            $filters = array($filters);
            $params = array($params);
        }

        $params = array_values($params);

        foreach (array_values($filters) as $num => $filter) {
            $filterOb = self::factory($filter, isset($params[$num]) ? $params[$num] : array());
            $patterns = $filterOb->getPatterns();

            /* Pre-processing. */
            $text = $filterOb->preProcess($text);

            /* str_replace() simple patterns. */
            if (isset($patterns['replace'])) {
                $text = str_replace(array_keys($patterns['replace']), array_values($patterns['replace']), $text);
            }

            /* preg_replace complex patterns. */
            if (isset($patterns['regexp'])) {
                $new_text = preg_replace(array_keys($patterns['regexp']), array_values($patterns['regexp']), $text);
                if (strlen($new_text) ||
                    (preg_last_error() != PREG_BACKTRACK_LIMIT_ERROR)) {
                    $text = $new_text;
                }
            }

            /* preg_replace_callback complex patterns. */
            if (isset($patterns['regexp_callback'])) {
                foreach ($patterns['regexp_callback'] as $key => $val) {
                    $new_text = preg_replace_callback($key, $val, $text);
                    if (strlen($new_text) ||
                        (preg_last_error() != PREG_BACKTRACK_LIMIT_ERROR)) {
                        $text = $new_text;
                    }
                }
            }

            /* Post-processing. */
            $text = $filterOb->postProcess($text);
        }

        return $text;
    }

}
