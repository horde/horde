<?php
/**
 * Login system task for upgrading user prefs after upgrading to Turba 2.2.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_LoginTasks
 */
class Turba_LoginTasks_SystemTask_UpgradePrefs extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::ONCE;

    /**
     * Cache array used in _updateShareName().
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Holds an array of Horde_Share_Object objects.
     *
     * @var array
     */
    protected $_shares;

    /**
     * Perform all functions for this task.
     *
     * @return boolean  Success.
     */
    public function execute()
    {
        global $registry;

        if ($GLOBALS['session']->get('turba', 'has_share')) {
            $this->_doAddressbooks();
            $this->_doColumns();
            $this->_doAddSource();

            // Now take care of non-Turba prefs.
            $apps = $registry->listApps(null, true);
            if (!empty($apps['imp'])) {
                $registry->loadPrefs('imp');
                $this->_doImp();
            }

            if (!empty($apps['kronolith'])) {
                $registry->loadPrefs('kronolith');
                $this->_doKronolith();
            }

            $registry->loadPrefs('turba');
        }

        return true;
    }

    /**
     * Update Turba's addressbooks pref.
     */
    protected function _doAddressbooks()
    {
        global $prefs;

        $abooks = $prefs->getValue('addressbooks');
        if (is_array(json_decode($abooks))) {
            return;
        }

        $abooks = explode("\n", $abooks);
        if (is_array($abooks) && !empty($abooks[0])) {
            $new_prefs = array();
            foreach ($abooks as $abook) {
                $new_prefs[] = $this->_updateShareName($abook);
            }

            return $prefs->setValue('addressbooks', json_encode($new_prefs));
        }

        return true;
    }

    /**
     * Update Turba's columns pref
     */
    protected function _doColumns()
    {
        global $prefs;

        // Turba's columns pref
        $abooks = explode("\n", $prefs->getValue('columns'));
        if (is_array($abooks) && !empty($abooks[0])) {
            $new_prefs = array();
            $cnt = count($abooks);
            for ($i = 0; $i < $cnt; ++$i) {
                $colpref = explode("\t", $abooks[$i]);
                $colpref[0] = $this->_updateShareName($colpref[0]);
                $abooks[$i] = implode("\t", $colpref);
            }
            return $prefs->setValue('columns', implode("\n", $abooks));
        }

        return true;
    }

    /**
     * TODO
     */
    protected function _doAddsource()
    {
        global $prefs;

        $newName = $this->_updateShareName($prefs->getValue('add_source'));
        if (!empty($newName)) {
            return $prefs->setValue('add_source', $newName);
        }
    }

    /**
     * Helper function to update a 'legacy' share name
     * to the new flattened share style.
     */
    protected function _updateShareName($book)
    {
        // No sense going through all the logic if we know we're empty.
        if (empty($book)) {
            return $book;
        }

        if (empty($this->_shares)) {
            $this->_shares = Turba::listShares();
        }

        // Have we seen this one yet?
        if (!empty($this->_cache[$book])) {
            return $this->_cache[$book];
        }

        // Is it an unmodified share key already?
        if (strpos($book, ':') !== false) {
            list($source, $key) = explode(':', $book, 2);
            $source = trim($source);
            $key = trim($key);
            if (isset($this->_shares[$key])) {
                $params = @unserialize($this->_shares[$key]->get('params'));
                // I'm not sure if this would ever be not true, but...
                if ($params['source'] == $source) {
                    $this->_cache[$book] = $key;
                    return $key;
                }
            } else {
                // Maybe a key the upgrade script modified?
                foreach ($this->_shares as $skey => $share) {
                    $params = @unserialize($share->get('params'));
                    if (!empty($params['name']) && $params['name'] == $key &&
                        $params['source'] == $source) {

                       $this->_cache[$book] = $skey;
                       return $skey;
                    }
                }
            }
        } else {
            // Need to check if this is a default address book for
            // one of our sources that is share enabled.
            foreach ($this->_shares as $skey => $share) {
                $params = @unserialize($share->get('params'));
                if ($params['source'] == $book &&
                    !empty($params['default'])) {
                    $this->_cache[$book] = $skey;
                    return $skey;
                }
            }
        }

        // Must be a normal, non-shared source, just pass it back.
        $this->_cache[$book] = $book;

        return $book;
    }

    /**
     * Update IMP's search_sources pref
     */
    protected function _doImp()
    {
        global $prefs;

        $imp_pref = $prefs->getValue('search_sources');
        if (!empty($imp_pref)) {
            $books = explode("\t", $imp_pref);
            $new_books = array();
            foreach ($books as $book) {
                $new_books[] = $this->_updateShareName($book);
            }
            $books = implode("\t", $new_books);
            return $prefs->setValue('search_sources', $books);
        }

        return true;
    }

    /**
     * Update Kronolith's search_abook pref
     */
    protected function _doKronolith()
    {
        global $prefs;

        $books = @unserialize($prefs->getValue('search_abook'));
        if (!empty($books)) {
            $new_books = array();
            foreach ($books as $book) {
                $new_books[] = $this->_updateShareName($book);
            }
            return $prefs->setValue('search_abook', serialize($new_books));
        }

        return true;
    }

}
