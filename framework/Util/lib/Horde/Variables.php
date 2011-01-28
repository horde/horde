<?php
/**
 * Horde_Variables:: class. Provides OO-way to access form variables.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Util
 */
class Horde_Variables implements Countable, Iterator
{
    /**
     * Array of form variables.
     *
     * @var array
     */
    protected $_vars;

    /**
     * The list of expected variables.
     *
     * @var array
     */
    protected $_expectedVariables = array();

    /**
     * Has the input been sanitized?
     *
     * @var boolean
     */
    protected $_sanitized = false;

    /**
     * Returns a Horde_Variables object populated with the form input.
     *
     * @param string $sanitize  Sanitize the input variables?
     *
     * @return Horde_Variables  Variables object.
     */
    static public function getDefaultVariables($sanitize = false)
    {
        return new self(null, $sanitize);
    }

    /**
     * Constructor.
     *
     * @param array $vars       TODO
     * @param string $sanitize  Sanitize the input variables?
     */
    public function __construct($vars = array(), $sanitize = false)
    {
        if (is_null($vars)) {
            $vars = Horde_Util::dispelMagicQuotes($_REQUEST);
        }

        if (isset($vars['_formvars'])) {
            $this->_expectedVariables = @unserialize($vars['_formvars']);
            unset($vars['_formvars']);
        }

        $this->_vars = $vars;

        if ($sanitize) {
            $this->sanitize();
        }
    }

    /**
     * Sanitize the form input.
     */
    public function sanitize()
    {
        if (!$this->_sanitized) {
            foreach (array_keys($this->_vars) as $key) {
                $value = $this->get($key);
                $this->set($key, is_array($value) ? filter_var_array($value, FILTER_SANITIZE_STRING) : filter_var($value, FILTER_SANITIZE_STRING));
            }
            $this->_sanitized = true;
        }
    }

    /**
     * Alias of isset().
     *
     * @param string $varname  The form variable name.
     *
     * @return boolean  Does $varname form variable exist?
     */
    public function exists($varname)
    {
        return $this->__isset($varname);
    }

    /**
     * isset() implementation.
     *
     * @param string $varname  The form variable name.
     *
     * @return boolean  Does $varname form variable exist?
     */
    public function __isset($varname)
    {
        return count($this->_expectedVariables)
            ? $this->_exists($this->_expectedVariables, $varname)
            : $this->_exists($this->_vars, $varname);
    }

    /**
     * Returns the value of a given form variable.
     *
     * @param string $varname  The form variable name.
     * @param string $default  The default form variable value.
     *
     * @return mixed  The form variable, or null if it doesn't exist.
     */
    public function get($varname, $default = null)
    {
        return $this->_getExists($this->_vars, $varname, $value)
            ? $value
            : $default;
    }

    /**
     * Returns the value of a given form variable.
     *
     * @param string $varname  The form variable name.
     *
     * @return mixed  The form variable, or null if it doesn't exist.
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
     * Sets the value of a given form variable.
     *
     * @param string $varname  The form variable name.
     * @param mixed $value     The value to set.
     */
    public function set($varname, $value)
    {
        $this->__set($varname, $value);
    }

    /**
     * Sets the value of a given form variable.
     *
     * @param string $varname  The form variable name.
     * @param mixed $value     The value to set.
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
     * Deletes a given form variable.
     *
     * @param string $varname  The form variable name.
     */
    public function remove($varname)
    {
        return $this->__unset($varname);
    }

    /**
     * Deletes a given form variable.
     *
     * @param string $varname  The form variable name.
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
     * Merges a list of variables into the current form variable list.
     *
     * @param array $vars  Form variables.
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
     * @param string $varname  The form variable name.
     * @param mixed $value     The value to set.
     *
     * @return boolean  True if the value was altered.
     */
    public function add($varname, $value)
    {
        if ($this->exists($varname)) {
            return false;
        }

        $this->_vars[$varname] = $value;
        return true;
    }

    /**
     * Find out whether or not $varname was set in $array.
     *
     * @param array $array     The array to search in (usually either
     *                         $this->_vars or $this->_expectedVariables).
     * @param string $varname  The name of the variable to look for.
     *
     * @return boolean  Whether or not the variable was set (or, if we've
     *                  checked $this->_expectedVariables, should have been
     *                  set).
     */
    protected function _exists($array, $varname)
    {
        return $this->_getExists($array, $varname, $value);
    }

    /**
     * Fetch the requested variable ($varname) into $value, and return
     * whether or not the variable was set in $array.
     *
     * @param array $array     See _exists().
     * @param string $varname  See _exists().
     * @param mixed &$value    $varname's value gets assigned to this variable.
     *
     * @return boolean  Whether or not the variable was set (or, if we've
     *                  checked $this->_expectedVariables, should have been
     *                  set).
     */
    protected function _getExists($array, $varname, &$value)
    {
        if (Horde_Array::getArrayParts($varname, $base, $keys)) {
            if (!isset($array[$base])) {
                $value = null;
                return false;
            }

            $searchspace = &$array[$base];
            $i = count($keys);

            while ($i--) {
                $key = array_shift($keys);
                if (!isset($searchspace[$key])) {
                    $value = null;
                    return false;
                }
                $searchspace = &$searchspace[$key];
            }
            $value = $searchspace;

            return true;
        }

        $value = isset($array[$varname]) ? $array[$varname] : null;

        return !is_null($value);
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count($this->_vars);
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_vars);
    }

    public function key()
    {
        return key($this->_vars);
    }

    public function next()
    {
        next($this->_vars);
    }

    public function rewind()
    {
        reset($this->_vars);
    }

    public function valid()
    {
        return (key($this->_vars) !== null);
    }

}
