<?php
/**
 * Horde_Variables:: class. Provides OO-way to access form variables.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Util
 */
class Horde_Variables
{
    /**
     * TODO
     */
    protected $_vars;

    /**
     * The list of expected variables.
     *
     * @var array
     */
    protected $_expectedVariables = array();

    /**
     * TODO
     */
    static public function getDefaultVariables()
    {
        return new Horde_Variables(null);
    }

    /**
     * Constructor.
     *
     * @param array $vars  TODO
     */
    public function __construct($vars = array())
    {
        if (is_null($vars)) {
            $vars = Horde_Util::dispelMagicQuotes($_REQUEST);
        }

        if (isset($vars['_formvars'])) {
            $this->_expectedVariables = @unserialize($vars['_formvars']);
            unset($vars['_formvars']);
        }

        $this->_vars = $vars;
    }

    /**
     * Return the number of form variables.
     *
     * @return integer  The count of variables.
     */
    public function count()
    {
        return count($this->_vars);
    }

    /**
     * Alias of isset().
     *
     * @param string $varname  TODO
     *
     * @return boolean  See isset().
     */
    public function exists($varname)
    {
        return $this->__isset($varname);
    }

    /**
     * isset() implementation.
     *
     * @param string $varname  TODO
     *
     * @return boolean  See isset().
     */
    public function __isset($varname)
    {
        return (count($this->_expectedVariables) &&
                $this->_exists($this->_expectedVariables, $varname, false)) ||
               $this->_exists($this->_vars, $varname, false);
    }

    /**
     * TODO
     */
    public function get($varname)
    {
        return $this->__get($varname);
    }

    /**
     * TODO
     */
    public function __get($varname)
    {
        $this->_getExists($this->_vars, $varname, $value);
        return $value;
    }

    /**
     * TODO
     */
    public function getExists($varname, &$exists)
    {
        $exists = $this->_getExists($this->_vars, $varname, $value);
        return $value;
    }

    /**
     * TODO
     */
    public function set($varname, $value)
    {
        return $this->__set($varname, $value);
    }

    /**
     * TODO
     */
    public function __set($varname, $value)
    {
        $keys = array();
        if (!Horde_Array::getArrayParts($varname, $base, $keys)) {
            $this->_vars[$varname] = $value;
        } else {
            array_unshift($keys, $base);
            $place = &$this->_vars;
            $i = count($keys);

            while ($i--) {
                $key = array_shift($keys);
                if (!isset($place[$key])) {
                    $place[$key] = array();
                }
                $place = &$place[$key];
            }

            $place = $value;
        }
    }

    /**
     * TODO
     */
    public function remove($varname)
    {
        return $this->__unset($varname);
    }

    /**
     * TODO
     */
    public function __unset($varname)
    {
        Horde_Array::getArrayParts($varname, $base, $keys);
        if (!is_null($base)) {
            $ptr = &$this->_vars[$base];
            $end = count($keys) - 1;
            foreach ($keys as $key => $val) {
                if (!isset($ptr[$val])) {
                    break;
                }
                if ($end == $key) {
                    array_splice($ptr, array_search($val, array_keys($ptr)), 1);
                } else {
                    $ptr = &$ptr[$val];
                }
            }
        } else {
            unset($this->_vars[$varname]);
        }
    }

    /**
     * TODO
     */
    public function merge($vars)
    {
        foreach ($vars as $varname => $value) {
            $this->set($varname, $value);
        }
    }

    /**
     * Set $varname to $value ONLY if it's not already present.
     *
     * @
     */
    public function add($varname, $value)
    {
        if ($this->exists($varname)) {
            return false;
        }
        $this->_vars[$varname] = $value;
    }

    /**
     * Find out whether or not $varname was set in $array.
     *
     * @param array $array     The array to search in (usually either
     *                         $this->_vars or $this->_expectedVariables).
     * @param string $varname  The name of the variable to look for.
     * @param boolean $check   If we don't find $varname, should we check
     *                         $this->_expectedVariables to see if should
     *                         have existed (like a checkbox or select
     *                         multiple).
     *
     * @return boolean  Whether or not the variable was set (or, if we've
     *                  checked $this->_expectedVariables, should have been
     *                  set).
     */
    protected function _exists($array, $varname, $check = true)
    {
        return $this->_getExists($array, $varname, $value, $check);
    }

    /**
     * Fetch the requested variable ($varname) into $value, and return
     * whether or not the variable was set in $array.
     *
     * @param array $array     See _exists().
     * @param string $varname  See _exists().
     * @param mixed &$value    $varname's value gets assigned to this variable.
     * @param boolean $check   See _exists().
     *
     * @return boolean  Whether or not the variable was set (or, if we've
     *                  checked $this->_expectedVariables, should have been
     *                  set).
     */
    protected function _getExists($array, $varname, &$value, $check = true)
    {
        if (Horde_Array::getArrayParts($varname, $base, $keys)) {
            if (!isset($array[$base])) {
                $value = null;
                // If we're supposed to check $this->_expectedVariables, do so,
                // but make sure not to check it again.
                return $check
                    ? $this->_exists($this->_expectedVariables, $varname, false)
                    : false;
            } else {
                $searchspace = &$array[$base];
                $i = count($keys);

                while ($i--) {
                    $key = array_shift($keys);
                    if (!isset($searchspace[$key])) {
                        $value = null;
                        // If we're supposed to check
                        // $this->_expectedVariables, do so, but make
                        // sure not to check it again.
                        return $check
                            ? $this->_exists($this->_expectedVariables, $varname, false)
                            : false;
                    }
                    $searchspace = &$searchspace[$key];
                }
                $value = $searchspace;
                return true;
            }
        } else {
            $value = isset($array[$varname]) ? $array[$varname] : null;
            if (!is_null($value)) {
                return true;
            } elseif ($check) {
                // If we're supposed to check
                // $this->_expectedVariables, do so, but make sure not
                // to check it again.
                return $this->_exists($this->_expectedVariables, $varname, false);
            }

            return false;
        }
    }

}
