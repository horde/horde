<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Storage_Filters is the object used to hold user-defined filtering rule
 * information.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Storage_Filters
{
    /**
     * The filter list.
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * The object type.
     *
     * @var integer
     */
    protected $_obtype = Ingo_Storage::ACTION_FILTERS;

    /**
     * Returns the object rule type.
     *
     * @return integer  The object rule type.
     */
    public function obType()
    {
        return $this->_obtype;
    }

    /**
     * Propagates the filter list with data.
     *
     * @param array $data  A list of rule hashes.
     */
    public function setFilterlist($data)
    {
        $this->_filters = $data;
    }

    /**
     * Returns the filter list.
     *
     * @param arary  The rules to skip, a list of Ingo::RULE_* constants.
     *
     * @return array  The list of rule hashes.
     */
    public function getFilterList($skip = array())
    {
        $filters = array();
        $skip = array_flip($skip);
        $skip_list = array(
            Ingo_Storage::ACTION_BLACKLIST => Ingo::RULE_BLACKLIST,
            Ingo_Storage::ACTION_WHITELIST => Ingo::RULE_WHITELIST,
            Ingo_Storage::ACTION_FORWARD => Ingo::RULE_FORWARD,
            Ingo_Storage::ACTION_VACATION => Ingo::RULE_VACATION,
            Ingo_Storage::ACTION_SPAM => Ingo::RULE_SPAM
        );

        foreach ($this->_filters as $id => $filter) {
            if (isset($skip_list[$filter['action']])) {
                if (!isset($skip[$skip_list[$filter['action']]])) {
                    $filters[$id] = $filter;
                }
            } elseif (!isset($skip[Ingo::RULE_FILTER])) {
                $filters[$id] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Return the filter entry for a given ID.
     *
     * @return mixed  The rule hash entry, or false if not defined.
     */
    public function getFilter($id)
    {
        return isset($this->_filters[$id])
            ? $this->_filters[$id]
            : false;
    }

    /**
     * Returns a single rule hash.
     *
     * @param integer $id  A rule number.
     *
     * @return array  The requested rule hash.
     */
    public function getRule($id)
    {
        return $this->_filters[$id];
    }

    /**
     * Returns a rule hash with default value used when creating new rules.
     *
     * @return array  A rule hash.
     */
    public function getDefaultRule()
    {
        return array(
            'name' => _("New Rule"),
            'combine' => Ingo_Storage::COMBINE_ALL,
            'conditions' => array(),
            'action' => Ingo_Storage::ACTION_KEEP,
            'action-value' => '',
            'stop' => true,
            'flags' => 0,
            'disable' => false
        );
    }

    /**
     * Searches for the first rule of a certain action type and returns its
     * number.
     *
     * @param integer $action  The field type of the searched rule
     *                         (ACTION_* constants).
     *
     * @return integer  The number of the first matching rule or null.
     */
    public function findRuleId($action)
    {
        foreach ($this->_filters as $id => $rule) {
            if ($rule['action'] == $action) {
                return $id;
            }
        }
    }

    /**
     * Searches for and returns the first rule of a certain action type.
     *
     * @param integer $action  The field type of the searched rule
     *                         (ACTION_* constants).
     *
     * @return array  The first matching rule hash or null.
     */
    public function findRule($action)
    {
        $id = $this->findRuleId($action);
        if ($id !== null) {
            return $this->getRule($id);
        }
    }

    /**
     * Adds a rule hash to the filters list.
     *
     * @param array $rule       A rule hash.
     * @param boolean $default  If true merge the rule hash with default rule
     *                          values.
     */
    public function addRule(array $rule, $default = true)
    {
        if ($default) {
            $this->_filters[] = array_merge($this->getDefaultRule(), $rule);
        } else {
            $this->_filters[] = $rule;
        }
    }

    /**
     * Updates an existing rule with a rule hash.
     *
     * @param array $rule  A rule hash
     * @param integer $id  A rule number
     */
    public function updateRule($rule, $id)
    {
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
        if (isset($this->_filters[$id])) {
            unset($this->_filters[$id]);
            $this->_filters = array_values($this->_filters);
            return true;
        }

        return false;
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
            $this->_filters = array_merge(array_slice($this->_filters, 0, $id + 1), array($newrule), array_slice($this->_filters, $id + 1));
            return true;
        }

        return false;
    }

    /**
     * Sorts the list of rules in the given order.
     *
     * @param array $rules  List of rule numbers.
     *
     * @throws Ingo_Exception
     */
    public function sort($rules)
    {
        $new = array();
        foreach ($rules as $val) {
            $new[] = $this->_filters[$val];
        }
        $this->_filters = $new;
    }

    /**
     * Disables a rule.
     *
     * @param integer $id  Number of the rule to disable.
     */
    public function ruleDisable($id)
    {
        $this->_filters[$id]['disable'] = true;
    }

    /**
     * Enables a rule.
     *
     * @param integer $id  Number of the rule to enable.
     */
    public function ruleEnable($id)
    {
        $this->_filters[$id]['disable'] = false;
    }

}
