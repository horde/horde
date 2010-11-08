<?php
/**
 * Ingo_Storage_Prefs:: implements the Ingo_Storage:: API to save Ingo data
 * via the Horde preferences system.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Prefs extends Ingo_Storage
{
    /**
     * Constructor.
     *
     * @param array $params  Additional parameters for the subclass.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
        parent::__construct();
    }

    /**
     * Retrieves the specified data from the storage backend.
     *
     * @param integer $field     The field name of the desired data.
     *                           See lib/Storage.php for the available fields.
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_Rule|Ingo_Storage_Filters  The specified data.
     */
    protected function _retrieve($field, $readonly = false)
    {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('ingo', array(
            'cache' => false,
            'user' => Ingo::getUser()
        ));

        switch ($field) {
        case self::ACTION_BLACKLIST:
            $ob = new Ingo_Storage_Blacklist();
            if ($data = @unserialize($prefs->getValue('blacklist'))) {
                $ob->setBlacklist($data['a'], false);
                $ob->setBlacklistFolder($data['f']);
            }
            break;

        case self::ACTION_WHITELIST:
            $ob = new Ingo_Storage_Whitelist();
            if ($data = @unserialize($prefs->getValue('whitelist'))) {
                $ob->setWhitelist($data, false);
            }
            break;

        case self::ACTION_FILTERS:
            $ob = new Ingo_Storage_Filters();
            if ($data = @unserialize($prefs->getValue('rules'))) {
                $ob->setFilterlist($data);
            }
            break;

        case self::ACTION_FORWARD:
            $ob = new Ingo_Storage_Forward();
            if ($data = @unserialize($prefs->getValue('forward'))) {
                $ob->setForwardAddresses($data['a'], false);
                $ob->setForwardKeep($data['k']);
            }
            break;

        case self::ACTION_VACATION:
            $ob = new Ingo_Storage_Vacation();
            if ($data = @unserialize($prefs->getValue('vacation'))) {
                $ob->setVacationAddresses($data['addresses'], false);
                $ob->setVacationDays($data['days']);
                $ob->setVacationExcludes($data['excludes'], false);
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
            if ($data = @unserialize($prefs->getValue('spam'))) {
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
     * @param Ingo_Storage_Rule|Ingo_Storage_Filters $ob  The object to store.
     *
     * @return boolean  True on success.
     */
    protected function _store($ob)
    {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('ingo', array(
            'cache' => false,
            'user' => Ingo::getUser()
        ));

        switch ($ob->obType()) {
        case self::ACTION_BLACKLIST:
            $data = array(
                'a' => $ob->getBlacklist(),
                'f' => $ob->getBlacklistFolder(),
            );
            return $prefs->setValue('blacklist', serialize($data));

        case self::ACTION_FILTERS:
            return $prefs->setValue('rules', serialize($ob->getFilterList()));

        case self::ACTION_FORWARD:
            $data = array(
                'a' => $ob->getForwardAddresses(),
                'k' => $ob->getForwardKeep(),
            );
            return $prefs->setValue('forward', serialize($data));

        case self::ACTION_VACATION:
            $data = array(
                'addresses' => $ob->getVacationAddresses(),
                'days' => $ob->getVacationDays(),
                'excludes' => $ob->getVacationExcludes(),
                'ignorelist' => $ob->getVacationIgnorelist(),
                'reason' => $ob->getVacationReason(),
                'subject' => $ob->getVacationSubject(),
                'start' => $ob->getVacationStart(),
                'end' => $ob->getVacationEnd(),
            );
            return $prefs->setValue('vacation', serialize($data));

        case self::ACTION_WHITELIST:
            return $prefs->setValue('whitelist', serialize($ob->getWhitelist()));

        case self::ACTION_SPAM:
            $data = array(
                'folder' => $ob->getSpamFolder(),
                'level' => $ob->getSpamLevel(),
            );
            return $prefs->setValue('spam', serialize($data));
        }

        return false;
    }

}
