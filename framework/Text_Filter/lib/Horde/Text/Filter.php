<?php
/**
 * Horde_Text_Filter:: is a parent class for defining stackable text filters.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Text_Filter
 */
class Horde_Text_Filter
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return.
     *                       This is based on the filter driver ($driver). The
     *                       code is dynamically included. If $driver is an
     *                       array, then we will look in $driver[0] for the
     *                       subclass implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration
     *                       parameters a subclass might need.
     *
     * @return Text_Filter  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = basename($driv_name);
        } else {
            $driver = basename($driver);
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Text_Filter_' . ucfirst($driver);
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Constructor.
     *
     * @param array $params  Any parameters that the filter instance needs.
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Applies a set of patterns to a block of text.
     *
     * @param string $text     The text to filter.
     * @param array $patterns  The array of patterns to filter with.
     *
     * @return string  The transformed text.
     */
    public function filter($text, $filters = array(), $params = array())
    {
        if (!is_array($filters)) {
            $filters = array($filters);
            $params = array($params);
        }

        foreach ($filters as $num => $filter) {
            try {
                $filterOb = self::factory($filter, isset($params[$num]) ? $params[$num] : array());
            } catch (Horde_Exception $e) {
                return $e->getMessage();
            }
            $patterns = $filterOb->getPatterns();

            /* Pre-processing. */
            $text = $filterOb->preProcess($text);

            /* str_replace() simple patterns. */
            if (isset($patterns['replace'])) {
                $text = str_replace(array_keys($patterns['replace']), array_values($patterns['replace']), $text);
            }

            /* preg_replace complex patterns. */
            if (isset($patterns['regexp'])) {
                $text = preg_replace(array_keys($patterns['regexp']), array_values($patterns['regexp']), $text);
            }

            /* Post-processing. */
            $text = $filterOb->postProcess($text);
        }

        return $text;
    }

    /**
     * Executes any code necessaray before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        return $text;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        return array();
    }

    /**
     * Executes any code necessaray after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        return $text;
    }

}
