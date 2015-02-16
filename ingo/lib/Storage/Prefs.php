<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Storage_Prefs implements the Ingo_Storage API to save Ingo data via the
 * Horde preferences system.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Storage_Prefs extends Ingo_Storage
{
    /**
     * Retrieves the specified data from the storage backend.
     *
     * @param integer $field     The field name of the desired data.
     *                           See lib/Storage.php for the available fields.
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_Rule  The specified data.
     */
    protected function _retrieve($field, $readonly = false)
    {
        switch ($field) {
        case self::ACTION_BLACKLIST:
            $ob = new Ingo_Storage_Blacklist();
            if ($data = @unserialize($this->_prefs()->getValue('blacklist'))) {
                $ob->setBlacklist($data['a']);
                $ob->setBlacklistFolder($data['f']);
            }
            break;

        case self::ACTION_WHITELIST:
            $ob = new Ingo_Storage_Whitelist();
            if ($data = @unserialize($this->_prefs()->getValue('whitelist'))) {
                $ob->setWhitelist($data);
            }
            break;

        case self::ACTION_FILTERS:
            $ob = new Ingo_Storage_Filters_Prefs($this->_prefs());
            break;

        case self::ACTION_FORWARD:
            $ob = new Ingo_Storage_Forward();
            if ($data = @unserialize($this->_prefs()->getValue('forward'))) {
                $ob->setForwardAddresses($data['a']);
                $ob->setForwardKeep($data['k']);
            }
            break;

        case self::ACTION_VACATION:
            $ob = new Ingo_Storage_Vacation();
            if ($data = @unserialize($this->_prefs()->getValue('vacation'))) {
                $ob->setVacationAddresses($data['addresses']);
                $ob->setVacationDays($data['days']);
                $ob->setVacationExcludes($data['excludes']);
                $ob->setVacationIgnorelist($data['ignorelist']);
                $ob->setVacationReason($data['reason']);
                $ob->setVacationSubject($data['subject']);
                if (isset($data['start'])) {
                    $ob->setVacationStart($data['start']);
                }
                if (isset($data['end'])) {
                    $ob->setVacationEnd($data['end']);
                }
            }
            break;

        case self::ACTION_SPAM:
            $ob = new Ingo_Storage_Spam();
            if ($data = @unserialize($this->_prefs()->getValue('spam'))) {
                $ob->setSpamFolder($data['folder']);
                $ob->setSpamLevel($data['level']);
            }
            break;

        default:
            $ob = false;
            break;
        }

        return $ob;
    }

    /**
     * Stores the specified data in the storage backend.
     *
     * @param Ingo_Storage_Rule $ob  The object to store.
     */
    protected function _store($ob)
    {
        switch ($ob->obType()) {
        case self::ACTION_BLACKLIST:
            $this->_prefs()->setValue('blacklist', serialize(array(
                'a' => $ob->getBlacklist(),
                'f' => $ob->getBlacklistFolder()
            )));
            break;

        case self::ACTION_FILTERS:
            $this->_prefs()->setValue(
                'rules',
                serialize($ob->getFilterList())
            );
            break;

        case self::ACTION_FORWARD:
            $this->_prefs()->setValue('forward', serialize(array(
                'a' => $ob->getForwardAddresses(),
                'k' => $ob->getForwardKeep()
            )));
            break;

        case self::ACTION_VACATION:
            $this->_prefs()->setValue('vacation', serialize(array(
                'addresses' => $ob->getVacationAddresses(),
                'days' => $ob->getVacationDays(),
                'excludes' => $ob->getVacationExcludes(),
                'ignorelist' => $ob->getVacationIgnorelist(),
                'reason' => $ob->getVacationReason(),
                'subject' => $ob->getVacationSubject(),
                'start' => $ob->getVacationStart(),
                'end' => $ob->getVacationEnd()
            )));
            break;

        case self::ACTION_WHITELIST:
            $this->_prefs()->setValue(
                'whitelist',
                serialize($ob->getWhitelist())
            );
            break;

        case self::ACTION_SPAM:
            $this->_prefs()->setValue('spam', serialize(array(
                'folder' => $ob->getSpamFolder(),
                'level' => $ob->getSpamLevel()
            )));
            break;
        }
    }

    /**
     */
    protected function _removeUserData($user)
    {
        $p = $this->_prefs($user);

        $p->remove('blacklist');
        $p->remove('filters');
        $p->remove('forward');
        $p->remove('spam');
        $p->remove('vacation');
    }

    /**
     * Get prefs object to use for storage.
     *
     * @param string $user  Username to use (if not default).
     *
     * @return Horde_Prefs  Prefs object.
     */
    protected function _prefs($user = null)
    {
        global $injector;

        return $injector->getInstance('Horde_Core_Factory_Prefs')->create('ingo', array(
            'cache' => false,
            'user' => is_null($user) ? Ingo::getUser() : $user
        ));
    }

}
