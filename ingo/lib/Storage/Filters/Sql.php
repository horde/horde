<?php
/**
 * Ingo_Storage_Filters_Sql is the object used to hold user-defined filtering
 * rule information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Filters_Sql extends Ingo_Storage_Filters {

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing.
     *
     * Defaults to the same handle as $_db if a separate write database is not
     * required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Driver specific parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param DB $db         Handle for the database connection.
     * @param DB $write_db   Handle for the database connection, used for
     *                       writing.
     * @param array $params  Driver specific parameters.
     */
    public function __construct($db, $write_db, $params)
    {
        $this->_db = $db;
        $this->_write_db = $write_db;
        $this->_params = $params;
    }

    /**
     * Loads all rules from the DB backend.
     *
     * @param boolean $readonly  Whether to disable any write operations.
     */
    public function init($readonly = false)
    {
        $query = sprintf('SELECT * FROM %s WHERE rule_owner = ? ORDER BY rule_order',
                         $this->_params['table_rules']);
        $values = array(Ingo::getUser());
        Horde::logMessage('Ingo_Storage_Filters_Sql(): ' . $query, 'DEBUG');
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $data = array();
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $data[$row['rule_order']] = array(
                'id' => (int)$row['rule_id'],
                'name' => Horde_String::convertCharset($row['rule_name'], $this->_params['charset']),
                'action' => (int)$row['rule_action'],
                'action-value' => Horde_String::convertCharset($row['rule_value'], $this->_params['charset']),
                'flags' => (int)$row['rule_flags'],
                'conditions' => empty($row['rule_conditions']) ? null : Horde_String::convertCharset(unserialize($row['rule_conditions']), $this->_params['charset']),
                'combine' => (int)$row['rule_combine'],
                'stop' => (bool)$row['rule_stop'],
                'disable' => !(bool)$row['rule_active']);
        }
        $this->setFilterlist($data);

        if (empty($data) && !$readonly) {
            $data = @unserialize($GLOBALS['prefs']->getDefault('rules'));
            if ($data) {
                foreach ($data as $val) {
                    $this->addRule($val, false);
                }
            } else {
                $this->addRule(
                    array('name' => 'Whitelist',
                          'action' => Ingo_Storage::ACTION_WHITELIST),
                    false);
                $this->addRule(
                    array('name' => 'Vacation',
                          'action' => Ingo_Storage::ACTION_VACATION,
                          'disable' => true),
                    false);
                $this->addRule(
                    array('name' => 'Blacklist',
                          'action' => Ingo_Storage::ACTION_BLACKLIST),
                    false);
                $this->addRule(
                    array('name' => 'Spam Filter',
                          'action' => Ingo_Storage::ACTION_SPAM,
                          'disable' => true),
                    false);
                $this->addRule(
                    array('name' => 'Forward',
                          'action' => Ingo_Storage::ACTION_FORWARD),
                    false);
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
    protected function _ruleToBackend($rule)
    {
        return array(Horde_String::convertCharset($rule['name'], 'UTF-8', $this->_params['charset']),
                     (int)$rule['action'],
                     isset($rule['action-value']) ? Horde_String::convertCharset($rule['action-value'], 'UTF-8', $this->_params['charset']) : null,
                     isset($rule['flags']) ? (int)$rule['flags'] : null,
                     isset($rule['conditions']) ? serialize(Horde_String::convertCharset($rule['conditions'], 'UTF-8', $this->_params['charset'])) : null,
                     isset($rule['combine']) ? (int)$rule['combine'] : null,
                     isset($rule['stop']) ? (int)$rule['stop'] : null,
                     isset($rule['disable']) ? (int)(!$rule['disable']) : 1);
    }

    /**
     * Adds a rule hash to the filters list.
     *
     * @param array $rule       A rule hash.
     * @param boolean $default  If true merge the rule hash with default rule
     *                          values.
     */
    public function addRule($rule, $default = true)
    {
        if ($default) {
            $rule = array_merge($this->getDefaultRule(), $rule);
        }

        $query = sprintf('INSERT INTO %s (rule_id, rule_owner, rule_name, rule_action, rule_value, rule_flags, rule_conditions, rule_combine, rule_stop, rule_active, rule_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                         $this->_params['table_rules']);
        $id = $this->_write_db->nextId($this->_params['table_rules']);
        if (is_a($id, 'PEAR_Error')) {
            return $id;
        }
        $order = key(array_reverse($this->_filters, true)) + 1;
        $values = array_merge(array($id, Ingo::getUser()),
                              $this->_ruleToBackend($rule),
                              array($order));
        Horde::logMessage('Ingo_Storage_Filters_Sql::addRule(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $rule['id'] = $id;
        $this->_filters[$order] = $rule;
    }

    /**
     * Updates an existing rule with a rule hash.
     *
     * @param array $rule  A rule hash
     * @param integer $id  A rule number
     */
    public function updateRule($rule, $id)
    {
        $query = sprintf('UPDATE %s SET rule_name = ?, rule_action = ?, rule_value = ?, rule_flags = ?, rule_conditions = ?, rule_combine = ?, rule_stop = ?, rule_active = ?, rule_order = ? WHERE rule_id = ? AND rule_owner = ?',
                         $this->_params['table_rules']);
        $values = array_merge($this->_ruleToBackend($rule),
                              array($id, $rule['id'], Ingo::getUser()));
        Horde::logMessage('Ingo_Storage_Filters_Sql::updateRule(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $this->_filters[$id] = $rule;
    }

    /**
     * Deletes a rule from the filters list.
     *
     * @param integer $id  Number of the rule to delete.
     *
     * @return boolean  True if the rule has been found and deleted.
     */
    public function deleteRule($id)
    {
        if (!isset($this->_filters[$id])) {
            return false;
        }

        $query = sprintf('DELETE FROM %s WHERE rule_id = ? AND rule_owner = ?',
                         $this->_params['table_rules']);
        $values = array($this->_filters[$id]['id'], Ingo::getUser());
        Horde::logMessage('Ingo_Storage_Filters_Sql::deleteRule(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }
        unset($this->_filters[$id]);

        $query = sprintf('UPDATE %s SET rule_order = rule_order - 1 WHERE rule_owner = ? AND rule_order > ?',
                         $this->_params['table_rules']);
        $values = array(Ingo::getUser(), $id);
        Horde::logMessage('Ingo_Storage_Filters_Sql::deleteRule(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Creates a copy of an existing rule.
     *
     * The created copy is added to the filters list right after the original
     * rule.
     *
     * @param integer $id  Number of the rule to copy.
     *
     * @return boolean  True if the rule has been found and copied.
     */
    public function copyRule($id)
    {
        if (isset($this->_filters[$id])) {
            $newrule = $this->_filters[$id];
            $newrule['name'] = sprintf(_("Copy of %s"), $this->_filters[$id]['name']);
            $this->addRule($newrule, false);
            $this->ruleUp(count($this->_filters) - 1, count($this->_filters) - $id - 2);
            return true;
        }

        return false;
    }

    /**
     * Moves a rule up in the filters list.
     *
     * @param integer $id     Number of the rule to move.
     * @param integer $steps  Number of positions to move the rule up.
     */
    public function ruleUp($id, $steps = 1)
    {
        return $this->_ruleMove($id, -$steps);
    }

    /**
     * Moves a rule down in the filters list.
     *
     * @param integer $id     Number of the rule to move.
     * @param integer $steps  Number of positions to move the rule down.
     */
    public function ruleDown($id, $steps = 1)
    {
        return $this->_ruleMove($id, $steps);
    }

    /**
     * Moves a rule in the filters list.
     *
     * @param integer $id     Number of the rule to move.
     * @param integer $steps  Number of positions and direction to move the
     *                        rule.
     */
    protected function _ruleMove($id, $steps)
    {
        $query = sprintf('UPDATE %s SET rule_order = rule_order %s 1 WHERE rule_owner = ? AND rule_order %s ? AND rule_order %s ?',
                         $this->_params['table_rules'],
                         $steps > 0 ? '-' : '+',
                         $steps > 0 ? '>' : '>=',
                         $steps > 0 ? '<=' : '<');
        $values = array(Ingo::getUser());
        if ($steps < 0) {
            $values[] = (int)($id + $steps);
            $values[] = (int)$id;
        } else {
            $values[] = (int)$id;
            $values[] = (int)($id + $steps);
        }
        Horde::logMessage('Ingo_Storage_Filters_Sql::ruleUp(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }
        $query = sprintf('UPDATE %s SET rule_order = ? WHERE rule_owner = ? AND rule_id = ?',
                         $this->_params['table_rules']);
        $values = array((int)($id + $steps),
                        Ingo::getUser(),
                        $this->_filters[$id]['id']);
        Horde::logMessage('Ingo_Storage_Filters_Sql::ruleUp(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        $this->init();
    }

    /**
     * Disables a rule.
     *
     * @param integer $id  Number of the rule to disable.
     */
    public function ruleDisable($id)
    {
        $rule = $this->_filters[$id];
        $rule['disable'] = true;
        $this->updateRule($rule, $id);
    }

    /**
     * Enables a rule.
     *
     * @param integer $id  Number of the rule to enable.
     */
    public function ruleEnable($id)
    {
        $rule = $this->_filters[$id];
        $rule['disable'] = false;
        $this->updateRule($rule, $id);
    }

}
