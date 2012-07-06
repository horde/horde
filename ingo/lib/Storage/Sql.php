<?php
/**
 * Ingo_Storage_Sql implements the Ingo_Storage API to save Ingo data via
 * Horde's Horde_Db database abstraction layer.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Sql extends Ingo_Storage
{
    /**
     * Retrieves the specified data from the storage backend.
     *
     * @param integer $field     The field name of the desired data.
     *                           See lib/Storage.php for the available fields.
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_Rule|Ingo_Storage_Filters  The specified data.
     * @throws Ingo_Exception
     */
    protected function _retrieve($field, $readonly = false)
    {
        switch ($field) {
        case self::ACTION_BLACKLIST:
        case self::ACTION_WHITELIST:
            if ($field == self::ACTION_BLACKLIST) {
                $ob = new Ingo_Storage_Blacklist();
                $filters = $this->retrieve(self::ACTION_FILTERS);
                $rule = $filters->findRule($field);
                if (isset($rule['action-value'])) {
                    $ob->setBlacklistFolder($rule['action-value']);
                }
            } else {
                $ob = new Ingo_Storage_Whitelist();
            }
            $query = sprintf('SELECT list_address FROM %s WHERE list_owner = ? AND list_blacklist = ?',
                             $this->_params['table_lists']);
            $values = array(Ingo::getUser(),
                            (int)($field == self::ACTION_BLACKLIST));
            try {
                $addresses = $this->_params['db']->selectValues($query, $values);
            } catch (Horde_Db_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw new Ingo_Exception($e);
            }
            if ($field == self::ACTION_BLACKLIST) {
                $ob->setBlacklist($addresses);
            } else {
                $ob->setWhitelist($addresses);
            }
            break;

        case self::ACTION_FILTERS:
            $ob = new Ingo_Storage_Filters_Sql($this->_params['db'], $this->_params);
            $ob->init($readonly);
            break;

        case self::ACTION_FORWARD:
            $query = sprintf('SELECT * FROM %s WHERE forward_owner = ?',
                             $this->_params['table_forwards']);

            try {
                $data = $this->_params['db']->selectOne($query, array(Ingo::getUser()));
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob = new Ingo_Storage_Forward();
            if (!empty($data)) {
                $ob->setForwardAddresses(explode("\n", $data['forward_addresses']));
                $ob->setForwardKeep((bool)$data['forward_keep']);
                $ob->setSaved(true);
            } elseif ($data = @unserialize($GLOBALS['prefs']->getDefault('forward'))) {
                $ob->setForwardAddresses($data['a']);
                $ob->setForwardKeep($data['k']);
            }
            break;

        case self::ACTION_VACATION:
            $query = sprintf('SELECT * FROM %s WHERE vacation_owner = ?',
                             $this->_params['table_vacations']);

            try {
                $data = $this->_params['db']->selectOne($query, array(Ingo::getUser()));
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob = new Ingo_Storage_Vacation();
            if (!empty($data)) {
                $ob->setVacationAddresses(explode("\n", $data['vacation_addresses']));
                $ob->setVacationDays((int)$data['vacation_days']);
                $ob->setVacationStart((int)$data['vacation_start']);
                $ob->setVacationEnd((int)$data['vacation_end']);
                $ob->setVacationExcludes(explode("\n", $data['vacation_excludes']));
                $ob->setVacationIgnorelist((bool)$data['vacation_ignorelists']);
                $ob->setVacationReason(Horde_String::convertCharset($data['vacation_reason'], $this->_params['charset'], 'UTF-8'));
                $ob->setVacationSubject(Horde_String::convertCharset($data['vacation_subject'], $this->_params['charset'], 'UTF-8'));
                $ob->setSaved(true);
            } elseif ($data = @unserialize($GLOBALS['prefs']->getDefault('vacation'))) {
                $ob->setVacationAddresses($data['addresses'], false);
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
            $query = sprintf('SELECT * FROM %s WHERE spam_owner = ?',
                             $this->_params['table_spam']);

            try {
                $data = $this->_params['db']->selectOne($query, array(Ingo::getUser()));
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob = new Ingo_Storage_Spam();
            if (!empty($data)) {
                $ob->setSpamFolder($data['spam_folder']);
                $ob->setSpamLevel((int)$data['spam_level']);
                $ob->setSaved(true);
            } elseif ($data = @unserialize($GLOBALS['prefs']->getDefault('spam'))) {
                $ob->setSpamFolder($data['folder']);
                $ob->setSpamLevel($data['level']);
            }
            break;

        default:
            $ob = false;
        }

        return $ob;
    }

    /**
     * Stores the specified data in the storage backend.
     *
     * @access private
     *
     * @param Ingo_Storage_Rule|Ingo_Storage_Filters $ob  The object to store.
     */
    protected function _store($ob)
    {
        switch ($ob->obType()) {
        case self::ACTION_BLACKLIST:
        case self::ACTION_WHITELIST:
            $is_blacklist = (int)($ob->obType() == self::ACTION_BLACKLIST);
            if ($is_blacklist) {
                $filters = $this->retrieve(self::ACTION_FILTERS);
                $id = $filters->findRuleId(self::ACTION_BLACKLIST);
                if ($id !== null) {
                    $rule = $filters->getRule($id);
                    if (!isset($rule['action-value']) ||
                        $rule['action-value'] != $ob->getBlacklistFolder()) {
                        $rule['action-value'] = $ob->getBlacklistFolder();
                        $filters->updateRule($rule, $id);
                    }
                }
            }
            $query = sprintf('DELETE FROM %s WHERE list_owner = ? AND list_blacklist = ?',
                             $this->_params['table_lists']);
            $values = array(Ingo::getUser(), $is_blacklist);
            try {
                $this->_params['db']->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Ingo_Exception($e);
            }
            $query = sprintf('INSERT INTO %s (list_owner, list_blacklist, list_address) VALUES (?, ?, ?)',
                             $this->_params['table_lists']);

            $addresses = $is_blacklist ? $ob->getBlacklist() : $ob->getWhitelist();
            foreach ($addresses as $address) {
                try {
                    $result = $this->_params['db']->insert(
                        $query,
                        array(Ingo::getUser(),
                              $is_blacklist,
                              $address));
                } catch (Horde_Db_Exception $e) {
                    Horde::logMessage($result, 'ERR');
                    throw new Ingo_Exception($e);
                }
            }
            $ob->setSaved(true);
            break;

        case self::ACTION_FORWARD:
            $values = array(
                implode("\n", $ob->getForwardAddresses()),
                (int)(bool)$ob->getForwardKeep(),
                Ingo::getUser());
            try {
                if ($ob->isSaved()) {
                    $query = sprintf('UPDATE %s SET forward_addresses = ?, forward_keep = ? WHERE forward_owner = ?', $this->_params['table_forwards']);
                    $this->_params['db']->update($query, $values);
                } else {
                    $query = sprintf('INSERT INTO %s (forward_addresses, forward_keep, forward_owner) VALUES (?, ?, ?)', $this->_params['table_forwards']);
                    $this->_params['db']->insert($query, $values);
                }
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob->setSaved(true);
            break;

        case self::ACTION_VACATION:
            $values = array(
                implode("\n", $ob->getVacationAddresses()),
                Horde_String::convertCharset($ob->getVacationSubject(),
                                             'UTF-8',
                                             $this->_params['charset']),
                Horde_String::convertCharset($ob->getVacationReason(),
                                             'UTF-8',
                                             $this->_params['charset']),
                (int)$ob->getVacationDays(),
                (int)$ob->getVacationStart(),
                (int)$ob->getVacationEnd(),
                implode("\n", $ob->getVacationExcludes()),
                (int)(bool)$ob->getVacationIgnorelist(),
                Ingo::getUser()
            );
            try {
                if ($ob->isSaved()) {
                    $query = sprintf('UPDATE %s SET vacation_addresses = ?, vacation_subject = ?, vacation_reason = ?, vacation_days = ?, vacation_start = ?, vacation_end = ?, vacation_excludes = ?, vacation_ignorelists = ? WHERE vacation_owner = ?', $this->_params['table_vacations']);
                    $this->_params['db']->update($query, $values);
                } else {
                    $query = sprintf('INSERT INTO %s (vacation_addresses, vacation_subject, vacation_reason, vacation_days, vacation_start, vacation_end, vacation_excludes, vacation_ignorelists, vacation_owner) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->_params['table_vacations']);
                    $this->_params['db']->insert($query, $values);
                }
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob->setSaved(true);
            break;

        case self::ACTION_SPAM:
            $values = array(
                (int)$ob->getSpamLevel(),
                $ob->getSpamFolder(),
                Ingo::getUser());
            try {
                if ($ob->isSaved()) {
                    $query = sprintf('UPDATE %s SET spam_level = ?, spam_folder = ? WHERE spam_owner = ?', $this->_params['table_spam']);
                    $this->_params['db']->update($query, $values);
                } else {
                    $query = sprintf('INSERT INTO %s (spam_level, spam_folder, spam_owner) VALUES (?, ?, ?)', $this->_params['table_spam']);
                    $this->_params['db']->insert($query, $values);
                }
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            $ob->setSaved(true);
            break;
        }
    }

    /**
     * Removes the data of the specified user from the storage backend.
     *
     * @param string $user  The user name to delete filters for.
     *
     * @throws Ingo_Exception
     */
    public function removeUserData($user)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            $user != $GLOBALS['registry']->getAuth()) {
            throw new Ingo_Exception(_("Permission Denied"));
        }

        $queries = array(sprintf('DELETE FROM %s WHERE rule_owner = ?',
                                 $this->_params['table_rules']),
                         sprintf('DELETE FROM %s WHERE list_owner = ?',
                                 $this->_params['table_lists']),
                         sprintf('DELETE FROM %s WHERE vacation_owner = ?',
                                 $this->_params['table_vacations']),
                         sprintf('DELETE FROM %s WHERE forward_owner = ?',
                                 $this->_params['table_forwards']),
                         sprintf('DELETE FROM %s WHERE spam_owner = ?',
                                 $this->_params['table_spam']));

        $values = array($user);
        foreach ($queries as $query) {
            try {
                $this->_params['db']->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
        }

        return true;
    }

}
