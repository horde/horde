<?php
/**
 * This class provides the storage for a preference scope.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Scope implements Iterator, Serializable
{
    /**
     * Is the object being initialized?
     *
     * @var boolean
     */
    public $init = false;

    /**
     * The scope name.
     *
     * @var string
     */
    public $scope;

    /**
     * List of dirty prefs.
     *
     * @var array
     */
    protected $_dirty = array();

    /**
     * Preferences list.  Each preference has the following format:
     * <pre>
     * [pref_name] => array(
     *     [d] => (string) Default value
     *            If not present, 'v' is the default value.
     *     [l] => (boolean) Locked
     *            If not present, pref is not locked.
     *     [v] => (string) Current pref value
     * )
     * </pre>
     *
     * @var array
     */
    protected $_prefs = array();

    /**
     * Constructor.
     *
     * @param string $scope  The scope for this set of preferences.
     */
    public function __construct($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Removes a preference entry.
     *
     * @param string $pref  The name of the preference to remove.
     *
     * @return boolean  True if preference was removed.
     */
    public function remove($pref)
    {
        if (!isset($this->_prefs[$pref])) {
            return false;
        }

        unset($this->_prefs[$pref]);
        if (!$this->init) {
            $this->setDirty($pref, true);
        }

        return true;
    }

    /**
     * Sets the value for a preference.
     *
     * @param string $pref  The preference name.
     * @param string $val   The preference value.
     */
    public function set($pref, $val)
    {
        if (isset($this->_prefs[$pref])) {
            $ptr = &$this->_prefs[$pref];

            if ($val != $ptr['v']) {
                if (isset($ptr['d']) && ($val == $ptr['d'])) {
                    unset($ptr['d']);
                } else {
                    $ptr['d'] = $ptr['v'];
                }
                $ptr['v'] = $val;

                if (!$this->init) {
                    $this->setDirty($pref, true);
                }
            }
        } else {
            $this->_prefs[$pref] = array(
                'v' => $val
            );

            if (!$this->init) {
                $this->setDirty($pref, true);
            }
        }
    }

    /**
     * Does a preference exist in this scope?
     *
     * @return boolean  True if the preference exists.
     */
    public function exists($pref)
    {
        return isset($this->_prefs[$pref]);
    }

    /**
     * Returns the value of a preference.
     *
     * @param string $pref  The preference name to retrieve.
     *
     * @return string  The value of the preference, null if it doesn't exist.
     */
    public function get($pref)
    {
        return isset($this->_prefs[$pref]['v'])
            ? $this->_prefs[$pref]['v']
            : null;
    }

    /**
     * Mark a preference as locked.
     *
     * @param string $pref     The preference name.
     * @param boolean $locked  Is the preference locked?
     */
    public function setLocked($pref, $locked)
    {
        if (isset($this->_prefs[$pref])) {
            $ptr = &$this->_prefs[$pref];

            if ($locked) {
                if (!isset($ptr['l'])) {
                    $ptr['l'] = true;
                    if (!$this->init) {
                        $this->setDirty($pref, true);
                    }
                }
            } elseif (isset($ptr['l'])) {
                unset($ptr['l']);
                if (!$this->init) {
                    $this->setDirty($pref, true);
                }
            }
        }
    }

    /**
     * Is a preference locked?
     *
     * @param string $pref  The preference name.
     *
     * @return boolean  Whether the preference is locked.
     */
    public function isLocked($pref)
    {
        return !empty($this->_prefs[$pref]['l']);
    }

    /**
     * Is a preference's value the default?
     *
     * @param string $pref  The preference name.
     *
     * @return boolean  True if the preference contains the default value.
     */
    public function isDefault($pref)
    {
        return !isset($this->_prefs[$pref]['d']);
    }

    /**
     * Returns the default value of a preference.
     *
     * @param string $pref  The preference name.
     *
     * @return string  The preference's default value.
     */
    public function getDefault($pref)
    {
        if (!$this->isDefault($pref)) {
            return $this->_prefs[$pref]['d'];
        }

        return isset($this->_prefs[$pref])
            ? $this->_prefs[$pref]['v']
            : null;
    }

    /**
     * Get the list of dirty preferences.
     *
     * @return array  The list of dirty preferences.
     */
    public function getDirty()
    {
        return array_keys($this->_dirty);
    }

    /**
     * Is a preference marked dirty?
     *
     * @param mixed $pref  The preference name.  If null, will return true if
     *                     scope contains at least one dirty pref.
     *
     * @return boolean  True if the preference is marked dirty.
     */
    public function isDirty($pref = null)
    {
        return is_null($pref)
            ? !empty($this->_dirty)
            : isset($this->_dirty[$pref]);
    }

    /**
     * Set the dirty flag for a preference
     *
     * @param string $pref    The preference name.
     * @param boolean $dirty  True to mark the pref as dirty.
     */
    public function setDirty($pref, $dirty)
    {
        if ($dirty) {
            $this->_dirty[$pref] = true;
        } else {
            unset($this->_dirty[$pref]);
        }
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_prefs);
    }

    public function key()
    {
        return key($this->_prefs);
    }

    public function next()
    {
        next($this->_prefs);
    }

    public function rewind()
    {
        reset($this->_prefs);
    }

    public function valid()
    {
        return (key($this->_prefs) !== null);
    }


    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->scope,
            $this->_prefs
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->scope, $this->_prefs) = json_decode($data, true);
    }

}
