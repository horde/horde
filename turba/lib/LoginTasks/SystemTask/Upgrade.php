<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'turba';

    /**
     * Cache array used in _updateShareName().
     *
     * @var array
     */
    protected $_cache = array();

    /**
     */
    protected $_versions = array(
        '2.2',
        '3.0'
    );

    /**
     * Holds an array of Horde_Share_Object objects.
     *
     * @var array
     */
    protected $_shares = array();

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '2.2':
            if ($GLOBALS['session']->get('turba', 'has_share')) {
                $this->_upgradeContactLists();
                $this->_upgradePrefsTurba2();
            }
            break;

        case '3.0':
            $this->_upgradeAbookPrefs();
            break;
        }
    }

    /**
     * Upgrade to the new addressbook preferences.
     */
    protected function _upgradeAbookPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('addressbooks')) {
            $abooks = $prefs->getValue('addressbooks');
            if (!is_array(json_decode($abooks))) {
                $abooks = @explode("\n", $abooks);
                if (empty($abooks)) {
                    $abooks = array();
                }

                return $prefs->setValue('addressbooks', json_encode($abooks));
            }
        }
    }

    /**
     * Upgrade to new contact lists format.
     */
    protected function _upgradeContactLists()
    {
        $criteria = array('__type' => 'Group');
        $sources = array_keys($GLOBALS['cfgSources']);

        foreach ($sources as $sourcekey) {
            try {
                $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($sourcekey);
                $lists = $driver->search($criteria);
            } catch (Turba_Exception $e) {
                return;
            }

            for ($j = 0, $cnt = count($lists); $j < $cnt; ++$j) {
                $list = $lists->next();
                $attributes = $list->getAttributes();
                $members = @unserialize($attributes['__members']);
                if (is_array($members) && !empty($members[0])) {
                    $c = count($members);
                    for ($i = 0; $i < $c; ++$i) {
                        if (substr_count($members[$i], ':') == 2) {
                            preg_match('/^([a-zA-Z0-9]+:[a-zA-Z0-9]+)(:[a-zA-Z0-9]+)$/', $members[$i], $matches);
                            $source = $matches[1];
                            $contact_key = substr($matches[2], 1);
                        } elseif (substr_count($members[$i], ':') == 1) {
                            list($source, $contact_key) = explode(':', $members[$i]);
                        } else {
                            break;
                        }
                        $source = $this->_updateShareName($source);
                        $members[$i] = $source . ':' . $contact_key;
                    }
                    $list->setValue('__members', serialize($members));
                    $list->store();
                }
            }
        }
    }

    /**
     * Helper function to update a 'legacy' share name to the new flattened
     * share style.
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
                    if ($params['name'] == $key &&
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

        // Special case for contacts from an IMSP source. The cfgSource
        // keys changed from 2.1 to 2.2 due to needs of the share code.
        if (strpos($book, 'IMSP_')) {
            // @TODO: Perform magical matching of IMSP-# to username.bookname.
        }

        // Must be a normal, non-shared source, just pass it back.
        $this->_cache[$book] = $book;

        return $book;
    }

    /**
     * Upgrade Turba prefs for version 2.2.
     */
    protected function _upgradePrefsTurba2()
    {
        global $registry;

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

            $prefs->setValue('addressbooks', json_encode($new_prefs));
        }
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
            $prefs->setValue('columns', implode("\n", $abooks));
        }
    }

    /**
     * TODO
     */
    protected function _doAddsource()
    {
        global $prefs;

        $newName = $this->_updateShareName($prefs->getValue('add_source'));
        if (!empty($newName)) {
            $prefs->setValue('add_source', $newName);
        }
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
            $prefs->setValue('search_sources', $books);
        }
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
            $prefs->setValue('search_abook', serialize($new_books));
        }
    }

}
