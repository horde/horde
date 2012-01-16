<?php
/**
 * This class manages the sortby preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Sort implements ArrayAccess, Iterator
{
    /* Preference name in backend. */
    const SORTPREF = 'sortpref';

    /**
     * The sortpref value.
     *
     * @var array
     */
    protected $_sortpref = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $prefs;

        $sortpref = @unserialize($prefs->getValue(self::SORTPREF));
        if (is_array($sortpref)) {
            $this->_sortpref = $sortpref;
        }
    }

    /**
     * Garbage collection.
     */
    public function gc()
    {
        foreach (IMP_Mailbox::get(array_keys($this->_sortpref)) as $val) {
            /* Purge if mailbox doesn't exist or this is a search query (not
             * a virtual folder). */
            if (!$val->exists || $val->query) {
                unset($this[$key]);
            }
        }
    }

    /**
     * Upgrade the preference from IMP 4 value.
     */
    public function upgradePrefs()
    {
        global $prefs;

        if (!$prefs->isDefault(self::SORTPREF)) {
            foreach ($this->_sortpref as $key => $val) {
                if (($sb = $this->newSortbyValue($val['b'])) !== null) {
                    $this->_sortpref[$key]['b'] = $sb;
                }
            }

            $this->_save();
        }
    }

    /**
     * Get the new sortby pref value for IMP 5.
     *
     * @param integer $sortby  The old (IMP 4) value.
     *
     * @return integer  Null if no change or else the converted sort value.
     */
    public function newSortbyValue($sortby)
    {
        switch ($sortby) {
        case 1: // SORTARRIVAL
            /* Sortarrival was the same thing as sequence sort in IMP 4. */
            return Horde_Imap_Client::SORT_SEQUENCE;

        case 2: // SORTDATE
            return IMP::IMAP_SORT_DATE;

        case 161: // SORTTHREAD
            return Horde_Imap_Client::SORT_THREAD;
        }

        return null;
    }

    /**
     * Save the preference to the backend.
     */
    protected function _save()
    {
        $GLOBALS['prefs']->setValue(serialize($this->_sortpref));
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_sortpref[$offset]);
    }

    public function offsetGet($offset)
    {
        $ob = new IMP_Prefs_Sort_Sortpref(
            $offset,
            isset($this->_sortpref[$offset]['b']) ? $this->_sortpref[$offset]['b'] : null,
            isset($this->_sortpref[$offset]['d']) ? $this->_sortpref[$offset]['d'] : null
        );

        try {
            Horde::callHook('mbox_sort', array($ob), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        return $ob;
    }

    /**
     * Alter a sortpref entry.
     *
     * @param string $offset                  The mailbox name.
     * @param IMP_Prefs_Sort_Sortpref $value  The sortpref object.
     *
     * @throws InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof IMP_Prefs_Sort_Sortpref)) {
            throw new InvalidArgumentException('$value must be a sortpref object.');
        }

        $this->_sortpref[$offset] = $value->toArray();
        $this->_save();
    }

    public function offsetUnset($offset)
    {
        if (isset($this->_sortpref[$offset])) {
            unset($this->_sortpref[$offset]);
            $this->_save();
        }
    }

    /* Iterator methods. */

    public function current()
    {
        return ($key = key($this->_sortpref))
            ? $this[$key]
            : false;
    }

    public function key()
    {
        return key($this->_sortpref);
    }

    public function next()
    {
        next($this->_sortpref);
    }

    public function rewind()
    {
        reset($this->_sortpref);
    }

    public function valid()
    {
        return (key($this->_sortpref) !== null);
    }

}
