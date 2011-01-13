<?php
/**
 * Login system task for upgrading contact lists after upgrading to Turba 2.2.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_LoginTasks
 */
class Turba_LoginTasks_SystemTask_UpgradeLists extends Horde_LoginTasks_SystemTask
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
    protected $_shares = array();

    /**
     * Perform all functions for this task.
     *
     * @return boolean  Success.
     */
    public function execute()
    {
        if ($GLOBALS['session']->get('turba', 'has_share')) {
            $criteria = array('__type' => 'Group');
            $sources = array_keys($GLOBALS['cfgSources']);
            foreach ($sources as $sourcekey) {
                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Injector_Factory_Driver')->create($sourcekey);
                    $lists = $driver->search($criteria);
                } catch (Turba_Exception $e) {
                    return false;
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

        return true;
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

}
