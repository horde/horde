<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * SQL storage of the user-defined filter list.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Filters_Sql extends Ingo_Storage_Filters
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Driver specific parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param Horde_Db_Adapter $db   Handle for the database connection.
     * @param array $params          Driver specific parameters.
     * @param boolean $readonly      Disable any write operations?
     */
    public function __construct(
        Horde_Db_Adapter $db, $params, $readonly = false
    )
    {
        $this->_db = $db;
        $this->_params = $params;

        $this->_init($readonly);
    }

    /**
     * Loads all rules from the DB backend.
     *
     * @throws Ingo_Exception
     */
    protected function _init($readonly = false)
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE rule_owner = ? ORDER BY rule_order',
            $this->_params['table_rules']
        );
        $values = array(Ingo::getUser());

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        foreach ($result as $row) {
            $this->_filters[$row['rule_order']] = array(
                'id' => (int)$row['rule_id'],
                'name' => Horde_String::convertCharset($row['rule_name'], $this->_params['charset'], 'UTF-8'),
                'action' => (int)$row['rule_action'],
                'action-value' => Horde_String::convertCharset($row['rule_value'], $this->_params['charset'], 'UTF-8'),
                'flags' => (int)$row['rule_flags'],
                'conditions' => empty($row['rule_conditions']) ? null : Horde_String::convertCharset(unserialize($row['rule_conditions']), $this->_params['charset'], 'UTF-8'),
                'combine' => (int)$row['rule_combine'],
                'stop' => (bool)$row['rule_stop'],
                'disable' => !(bool)$row['rule_active']
            );
        }

        if (empty($data) && !$readonly) {
            $data = @unserialize($GLOBALS['prefs']->getDefault('rules'));
            if (!$data) {
            } else {
                $data = array(
                    array(
                        'name' => 'Whitelist',
                        'action' => Ingo_Storage::ACTION_WHITELIST
                    ),
                    array(
                        'name' => 'Vacation',
                        'action' => Ingo_Storage::ACTION_VACATION,
                        'disable' => true
                    ),
                    array(
                        'name' => 'Blacklist',
                        'action' => Ingo_Storage::ACTION_BLACKLIST
                    ),
                    array(
                        'name' => 'Spam Filter',
                        'action' => Ingo_Storage::ACTION_SPAM,
                        'disable' => true
                    ),
                    array(
                        'name' => 'Forward',
                        'action' => Ingo_Storage::ACTION_FORWARD
                    )
                );
            }

            foreach ($data as $val) {
                $this->addRule($val, false);
            }
        }
    }

    /**
     * Converts a rule hash from Ingo's internal format to the database
     * format.
     *
     * @param array $rule  Rule hash in Ingo's format.
     *
     * @return array  Rule hash in DB's format.
     */
    protected function _ruleToBackend(array $rule)
    {
        return array(
            Horde_String::convertCharset(
                $rule['name'],
                'UTF-8',
                $this->_params['charset']
            ),
            (int)$rule['action'],
            isset($rule['action-value'])
                ? Horde_String::convertCharset($rule['action-value'], 'UTF-8', $this->_params['charset'])
                : null,
            isset($rule['flags'])
                ? (int)$rule['flags']
                : null,
            isset($rule['conditions'])
                ? serialize(Horde_String::convertCharset($rule['conditions'], 'UTF-8', $this->_params['charset']))
                : null,
            isset($rule['combine'])
                ? (int)$rule['combine']
                : null,
            isset($rule['stop'])
                ? (int)$rule['stop']
                : null,
            isset($rule['disable'])
                ? (int)(!$rule['disable'])
                : 1
        );
    }

    /**
     */
    public function addRule(array $rule, $default = true)
    {
        if ($default) {
            $rule = array_merge($this->getDefaultRule(), $rule);
        }

        $query = sprintf(
            'INSERT INTO %s (rule_owner, rule_name, rule_action, rule_value, rule_flags, rule_conditions, rule_combine, rule_stop, rule_active, rule_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
           $this->_params['table_rules']
        );

        $order = key(array_reverse($this->_filters, true)) + 1;
        $values = array_merge(
            array(Ingo::getUser()),
            $this->_ruleToBackend($rule),
            array($order)
        );

        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        $rule['id'] = $result;
        $this->_filters[$order] = $rule;
    }

    /**
     */
    public function updateRule($rule, $id)
    {
        $query = sprintf(
            'UPDATE %s SET rule_name = ?, rule_action = ?, rule_value = ?, rule_flags = ?, rule_conditions = ?, rule_combine = ?, rule_stop = ?, rule_active = ?, rule_order = ? WHERE rule_id = ? AND rule_owner = ?',
            $this->_params['table_rules']
        );
        $values = array_merge(
            $this->_ruleToBackend($rule),
            array($id, $rule['id'], Ingo::getUser())
        );

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        $this->_filters[$id] = $rule;
    }

    /**
     */
    public function deleteRule($id)
    {
        if (!isset($this->_filters[$id])) {
            return false;
        }

        $query = sprintf(
            'DELETE FROM %s WHERE rule_id = ? AND rule_owner = ?',
            $this->_params['table_rules']
        );
        $values = array($this->_filters[$id]['id'], Ingo::getUser());

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        /* Remove the rule from the filter list. */
        unset($this->_filters[$id]);
        $this->_filters = array_combine(
            range(1, count($this->_filters)),
            array_values($this->_filters)
        );

        $query = sprintf(
            'UPDATE %s SET rule_order = rule_order - 1 WHERE rule_owner = ? AND rule_order > ?',
            $this->_params['table_rules']
        );
        $values = array(Ingo::getUser(), $id);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }

        return true;
    }

    /**
     */
    public function copyRule($id)
    {
        if (!isset($this->_filters[$id])) {
            return false;
        }

        $newrule = $this->_filters[$id];
        $newrule['name'] = sprintf(
            _("Copy of %s"),
            $this->_filters[$id]['name']
        );
        $this->addRule($newrule, false);

        $rules = array();
        foreach (array_slice(array_keys($this->_filters), 0, -1) as $key) {
            $rules[] = $key;
            if ($key == $id) {
                $end = end($this->_filters);
                $rules[] = key($this->_filters);
            }
        }

        $this->sort($rules);

        return true;
    }

    /**
     */
    public function sort($rules)
    {
        $old = $this->_filters;

        parent::sort($rules);

        $query = sprintf(
            'UPDATE %s SET rule_order = ? WHERE rule_id = ?',
             $this->_params['table_rules']
        );

        $this->_db->beginDbTransaction();
        try {
            foreach ($this->_filters as $key => $val) {
                $this->_db->update($query, array($key, $val['id']));
            }
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            $this->_filters = $old;
            throw new Ingo_Exception($e);
        }
        $this->_db->commitDbTransaction();
    }

    /**
     */
    public function ruleDisable($id)
    {
        $rule = $this->_filters[$id];
        $rule['disable'] = true;
        $this->updateRule($rule, $id);
    }

    /**
     */
    public function ruleEnable($id)
    {
        $rule = $this->_filters[$id];
        $rule['disable'] = false;
        $this->updateRule($rule, $id);
    }

}
