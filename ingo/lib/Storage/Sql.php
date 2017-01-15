<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Ingo_Storage API implementation to save Ingo data via Horde's Horde_Db
 * database abstraction layer.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Sql extends Ingo_Storage
{
    /**
     * Mapping of 'rule_action' column in ingo_rules table to rule class
     * names.
     *
     * @var array
     */
    protected $_actions = array(
        1 => 'Ingo_Rule_User_Keep',
        2 => 'Ingo_Rule_User_Move',
        3 => 'Ingo_Rule_User_Discard',
        4 => 'Ingo_Rule_User_Redirect',
        5 => 'Ingo_Rule_User_RedirectKeep',
        6 => 'Ingo_Rule_User_Reject',
        7 => 'Ingo_Rule_System_Blacklist',
        8 => 'Ingo_Rule_System_Vacation',
        9 => 'Ingo_Rule_System_Whitelist',
        10 => 'Ingo_Rule_System_Forward',
        11 => 'Ingo_Rule_User_MoveKeep',
        12 => 'Ingo_Rule_User_FlagOnly',
        13 => 'Ingo_Rule_User_Notify',
        14 => 'Ingo_Rule_System_Spam'
    );

    /**
     */
    protected function _loadFromBackend()
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE rule_owner = ? ORDER BY rule_order',
            $this->_params['table_rules']
        );
        $values = array(Ingo::getUser());

        try {
            $result = $this->_params['db']->select($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        $user = Ingo::getUser();

        foreach ($result as $row) {
            if (!($action = $this->_actions[intval($row['rule_action'])])) {
                continue;
            }

            $disable = !(bool)$row['rule_active'];
            $ob = new $action();

            switch ($action) {
            case 'Ingo_Rule_System_Blacklist':
                $ob->mailbox = Horde_String::convertCharset(
                    $row['rule_value'],
                    $this->_params['charset'],
                    'UTF-8'
                );

                $query = sprintf(
                    'SELECT list_address FROM %s WHERE list_owner = ? AND list_blacklist = ?',
                    $this->_params['table_lists']
                );
                $values = array($user, 1);

                try {
                    $ob->addresses = $this->_params['db']->selectValues(
                        $query,
                        $values
                    );
                } catch (Horde_Db_Exception $e) {
                    Horde::log($e, 'ERR');
                    throw new Ingo_Exception($e);
                }
                break;

            case 'Ingo_Rule_System_Forward':
                $query = sprintf(
                    'SELECT * FROM %s WHERE forward_owner = ?',
                    $this->_params['table_forwards']
                );
                $values = array($user);

                try {
                    $data = $this->_params['db']->selectOne($query, $values);
                    $columns = $this->_params['db']->columns(
                        $this->_params['table_forwards']
                    );
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }

                if (empty($data)) {
                    $disable = true;
                } else {
                    $ob->addresses = explode(
                        "\n",
                        $columns['forward_addresses']->binaryToString(
                            $data['forward_addresses']
                        )
                    );
                    $ob->keep = $data['forward_keep'];
                }
                break;

            case 'Ingo_Rule_System_Spam':
                $query = sprintf(
                    'SELECT * FROM %s WHERE spam_owner = ?',
                    $this->_params['table_spam']
                );
                $values = array($user);

                try {
                    $data = $this->_params['db']->selectOne($query, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }

                if (empty($data)) {
                    $disable = true;
                } else {
                    $ob->level = $data['spam_level'];
                    $ob->mailbox = $data['spam_folder'];
                }
                break;

            case 'Ingo_Rule_System_Vacation':
                $query = sprintf(
                    'SELECT * FROM %s WHERE vacation_owner = ?',
                    $this->_params['table_vacations']
                );
                $values = array($user);

                try {
                    $data = $this->_params['db']->selectOne($query, $values);
                    $columns = $this->_params['db']->columns(
                        $this->_params['table_vacations']
                    );
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }

                if (empty($data)) {
                    $disable = true;
                } else {
                    $ob->addresses = explode(
                        "\n",
                        $columns['vacation_addresses']->binaryToString(
                            $data['vacation_addresses']
                        )
                    );
                    $ob->days = $data['vacation_days'];
                    $ob->end = $data['vacation_end'];
                    $ob->exclude = explode(
                        "\n",
                        $columns['vacation_excludes']->binaryToString(
                            $data['vacation_excludes']
                        )
                    );
                    $ob->ignore_list = $data['vacation_ignorelists'];
                    $ob->reason = Horde_String::convertCharset(
                        $columns['vacation_reason']->binaryToString(
                            $data['vacation_reason']
                        ),
                        $this->_params['charset'],
                        'UTF-8'
                    );
                    $ob->start = $data['vacation_start'];
                    $ob->subject = Horde_String::convertCharset(
                        $data['vacation_subject'],
                        $this->_params['charset'],
                        'UTF-8'
                    );
                }
                break;

            case 'Ingo_Rule_System_Whitelist':
                $query = sprintf(
                    'SELECT list_address FROM %s WHERE list_owner = ? AND list_blacklist = ?',
                    $this->_params['table_lists']
                );
                $values = array($user, 0);

                try {
                    $ob->addresses = $this->_params['db']->selectValues(
                        $query,
                        $values
                    );
                } catch (Horde_Db_Exception $e) {
                    Horde::log($e, 'ERR');
                    throw new Ingo_Exception($e);
                }
                break;

            default:
                $columns = $this->_params['db']->columns($this->_params['table_rules']);
                $ob->combine = intval($row['rule_combine']);
                $ob->conditions = empty($row['rule_conditions'])
                    ? null :
                    Horde_String::convertCharset(
                        unserialize(
                            $columns['rule_conditions']->binaryToString(
                                $row['rule_conditions']
                            )
                        ),
                        $this->_params['charset'],
                        'UTF-8'
                    );
                $ob->flags = $ob->flags | intval($row['rule_flags']);
                $ob->name = Horde_String::convertCharset(
                    $row['rule_name'],
                    $this->_params['charset'],
                    'UTF-8'
                );
                $ob->stop = (bool)$row['rule_stop'];
                $ob->value = Horde_String::convertCharset(
                    $row['rule_value'],
                    $this->_params['charset'],
                    'UTF-8'
                );
                break;
            }

            $ob->disable = $disable;
            $ob->uid = $row['rule_id'];

            $this->_rules[] = $ob;
        }
    }

    /**
     */
    protected function _removeUserData($user)
    {
        $tables = array(
            'table_rules' => 'rule_owner',
            'table_lists' => 'list_owner',
            'table_vacations' => 'vacation_owner',
            'table_forwards' => 'forward_owner',
            'table_spam' => 'spam_owner'
        );
        $values = array($user);

        foreach ($tables as $key => $val) {
            $query = sprintf(
                'DELETE FROM %s WHERE %s = ?',
                $this->_params[$key],
                $val
            );

            try {
                $this->_params['db']->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                Horde::log($e, 'ERR');
            }
        }
    }

    /**
     */
    protected function _storeBackend($action, $rule)
    {
        switch ($action) {
        case self::STORE_ADD:
            try {
                $query = sprintf(
                    'SELECT MAX(rule_order) FROM %s WHERE rule_owner = ?',
                    $this->_params['table_rules']
                );
                $values = array(Ingo::getUser());

                $max = $this->_params['db']->selectValue($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }

            $query = sprintf(
                'INSERT INTO %s (rule_owner, rule_name, rule_action, ' .
                'rule_value, rule_flags, rule_conditions, rule_combine, ' .
                'rule_stop, rule_active, rule_order) ' .
                'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
               $this->_params['table_rules']
            );

            $d = $this->_ruleToBackend($rule);
            $values = array(
                Ingo::getUser(),
                $d['name'],
                $d['action'],
                $d['value'],
                $d['flags'],
                new Horde_Db_Value_Text($d['conditions']),
                $d['combine'],
                $d['stop'],
                $d['active'],
                $max + 1
            );

            try {
                $rule->uid = $this->_params['db']->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            break;

        case self::STORE_DELETE:
            $query = sprintf(
                'DELETE FROM %s WHERE rule_id = ? AND rule_owner = ?',
                $this->_params['table_rules']
            );
            $values = array($rule->uid, Ingo::getUser());

            try {
                /* No need to alter rule order; it is no longer contiguous,
                 * but that doesn't affect sort order. */
                $this->_params['db']->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            break;

        case self::STORE_UPDATE:
            $query = sprintf(
                'UPDATE %s SET rule_name = ?, rule_action = ?, ' .
                'rule_value = ?, rule_flags = ?, rule_conditions = ?, ' .
                'rule_combine = ?, rule_stop = ?, rule_active = ? ' .
                'WHERE rule_id = ? AND rule_owner = ?',
                $this->_params['table_rules']
            );

            $d = $this->_ruleToBackend($rule);
            $values = array(
                $d['name'],
                $d['action'],
                $d['value'],
                $d['flags'],
                $d['conditions'],
                $d['combine'],
                $d['stop'],
                $d['active'],
                $rule->uid,
                Ingo::getUser()
            );

            try {
                $this->_params['db']->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ingo_Exception($e);
            }
            break;

        case self::STORE_SORT:
            /* This won't update "invisible" rules based on current script
             * setup, but that is fine; sorting will still work if rules have
             * duplicate order IDs, and these invisible rules have no
             * guarantee of order if they are ever displayed again. */
            $query = sprintf(
                'UPDATE %s SET rule_order = ? WHERE rule_id = ?',
                 $this->_params['table_rules']
            );

            $this->_params['db']->beginDbTransaction();
            try {
                foreach ($this->_rules as $key => $val) {
                    $this->_params['db']->update($query, array($key, $val->uid));
                }
            } catch (Horde_Db_Exception $e) {
                $this->_params['db']->rollbackDbTransaction();
                throw new Ingo_Exception($e);
            }
            $this->_params['db']->commitDbTransaction();
            break;
        }

        switch ($action) {
        case self::STORE_ADD:
        case self::STORE_UPDATE:
            switch ($class = get_class($rule)) {
            case 'Ingo_Rule_System_Blacklist':
            case 'Ingo_Rule_System_Whitelist':
                $query = sprintf(
                    'DELETE FROM %s WHERE list_owner = ? AND ' .
                    'list_blacklist = ?',
                    $this->_params['table_lists']
                );
                $values = array(
                    Ingo::getUser(),
                    intval($class === 'Ingo_Rule_System_Blacklist')
                );

                try {
                    $this->_params['db']->delete($query, $values);
                } catch (Horde_Db_Exception $e) {
                    Horde::log($e, 'ERR');
                    throw new Ingo_Exception($e);
                }

                $query = sprintf(
                    'INSERT INTO %s (list_owner, list_blacklist, ' .
                    'list_address) VALUES (?, ?, ?)',
                        $this->_params['table_lists']
                );

                $this->_params['db']->beginDbTransaction();
                try {
                    foreach ($rule->addresses as $address) {
                        $this->_params['db']->insert(
                            $query,
                            array_merge($values, array($address))
                        );
                    }
                } catch (Horde_Db_Exception $e) {
                    Horde::log($e, 'ERR');
                    $this->_params['db']->rollbackDbTransaction();
                    throw new Ingo_Exception($e);
                }
                $this->_params['db']->commitDbTransaction();
                break;

            case 'Ingo_Rule_System_Forward':
                $values = array(
                    new Horde_Db_Value_Text(
                        implode("\n", $rule->addresses)
                    ),
                    intval($rule->keep),
                    Ingo::getUser()
                );

                try {
                    if ($action === self::STORE_ADD) {
                        $query = sprintf(
                            'INSERT INTO %s (forward_addresses, ' .
                            'forward_keep, forward_owner) VALUES (?, ?, ?)',
                                $this->_params['table_forwards']
                        );
                        $this->_params['db']->insert($query, $values);
                    } else {
                        $query = sprintf(
                            'UPDATE %s SET forward_addresses = ?, ' .
                            'forward_keep = ? WHERE forward_owner = ?',
                            $this->_params['table_forwards']
                        );
                        $this->_params['db']->update($query, $values);
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }
                break;

            case 'Ingo_Rule_System_Spam':
                $values = array(
                    $rule->level,
                    $rule->mailbox,
                    Ingo::getUser()
                );

                try {
                    if ($action === self::STORE_ADD) {
                        $query = sprintf(
                            'UPDATE %s SET spam_level = ?, ' .
                            'spam_folder = ? WHERE spam_owner = ?',
                            $this->_params['table_spam']
                        );
                        $this->_params['db']->update($query, $values);
                    } else {
                        $query = sprintf(
                            'INSERT INTO %s (spam_level, spam_folder, ' .
                            'spam_owner) VALUES (?, ?, ?)',
                                $this->_params['table_spam']
                        );
                        $this->_params['db']->insert($query, $values);
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }
                break;

            case 'Ingo_Rule_System_Vacation':
                $values = array(
                    new Horde_Db_Value_Text(implode("\n", $rule->addresses)),
                    Horde_String::convertCharset(
                        $rule->subject,
                        'UTF-8',
                        $this->_params['charset']
                    ),
                    new Horde_Db_Value_Text(Horde_String::convertCharset(
                        $rule->reason,
                        'UTF-8',
                        $this->_params['charset']
                    )),
                    $rule->days,
                    $rule->start,
                    $rule->end,
                    new Horde_Db_Value_Text(implode("\n", $rule->exclude)),
                    intval($rule->ignore_list),
                    Ingo::getUser()
                );

                try {
                    if ($action === self::STORE_ADD) {
                        $query = sprintf(
                            'INSERT INTO %s (vacation_addresses, ' .
                            'vacation_subject, vacation_reason, ' .
                            'vacation_days, vacation_start, vacation_end, ' .
                            'vacation_excludes, vacation_ignorelists, ' .
                            'vacation_owner) VALUES ' .
                            '(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                            $this->_params['table_vacations']
                        );
                        $this->_params['db']->insert($query, $values);
                    } else {
                        $query = sprintf(
                            'UPDATE %s SET vacation_addresses = ?, ' .
                            'vacation_subject = ?, vacation_reason = ?, ' .
                            'vacation_days = ?, vacation_start = ?, ' .
                            'vacation_end = ?, vacation_excludes = ?, ' .
                            'vacation_ignorelists = ? WHERE ' .
                            'vacation_owner = ?',
                            $this->_params['table_vacations']
                        );
                        $this->_params['db']->update($query, $values);
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Ingo_Exception($e);
                }
                break;
            }
        }
    }

    /**
     * Converts a user rule to the database format.
     *
     * @param Ingo_Rule $rule  Rule object.
     *
     * @return array  Rule hash in DB's format.
     */
    protected function _ruleToBackend(Ingo_Rule $rule)
    {
        $user_rule = ($rule instanceof Ingo_Rule_User);

        if ($user_rule) {
            $value = $rule->value;
        } elseif ($rule instanceof Ingo_Rule_System_Blacklist) {
            $value = $rule->mailbox;
        } else {
            $value = null;
        }

        return array(
            'action' => array_search(get_class($rule), $this->_actions),
            'active' => intval(!$rule->disable),
            'combine' => $user_rule ? $rule->combine : null,
            'conditions' => $user_rule
                ? serialize(Horde_String::convertCharset(
                    $rule->conditions,
                    'UTF-8',
                    $this->_params['charset']
                  ))
                : null,
            'flags' => $user_rule ? $rule->flags : null,
            'name' => Horde_String::convertCharset(
                $rule->name,
                'UTF-8',
                $this->_params['charset']
            ),
            'stop' => $user_rule ? intval($rule->stop) : null,
            'value' => is_null($value)
                ? null
                : Horde_String::convertCharset(
                    $value,
                    'UTF-8',
                    $this->_params['charset']
                )
        );
    }

}
