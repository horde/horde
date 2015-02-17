<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Storage defines an API to store the various filter rules.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
abstract class Ingo_Storage
implements Countable, IteratorAggregate
{
    /* Internal storage actions. */
    const STORE_ADD = 1;
    const STORE_DELETE = 2;
    const STORE_UPDATE = 3;
    const STORE_SORT = 4;

    /* Max rules errors. */
    const MAX_OK = 0;
    const MAX_NONE = 1;
    const MAX_OVER = 2;

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Rules list.
     *
     * @var array
     */
    protected $_rules = array();

    /**
     * Constructor.
     *
     * @params array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Load the rules.
     */
    protected function _load()
    {
        if (!empty($this->_rules) || !empty($this->_params['_loading'])) {
            return;
        }

        $this->_params['_loading'] = true;
        $this->_loadFromBackend();

        if (empty($this->_rules)) {
            $this->addRule(new Ingo_Rule_System_Whitelist());
            $this->addRule(new Ingo_Rule_System_Blacklist());

            $spam = new Ingo_Rule_System_Spam();
            $spam->disable = true;
            $this->addRule($spam);

            $forward = new Ingo_Rule_System_Forward();
            $forward->disable = true;
            $this->addRule($forward);

            $vacation = new Ingo_Rule_System_Vacation();
            $vacation->disable = true;
            $this->addRule($vacation);
        }

        unset($this->_params['_loading']);
    }

    /**
     * Load the rules from the storage backend.
     */
    protected abstract function _loadFromBackend();

    /**
     * Retrieves the specified system rule.
     *
     * @param string $rule  The rule name.
     *
     * @return Ingo_Rule  A rule object.
     * @throws Ingo_Exception
     */
    public function getSystemRule($rule)
    {
        $this->_load();

        foreach ($this->_rules as $val) {
            if (($val instanceof $rule) &&
                ($val instanceof Ingo_Rule_System)) {
                return $val;
            }
        }

        throw new Ingo_Exception('Invalid system rule');
    }

    /**
     * Removes the user data from the storage backend.
     *
     * @param string $user  The user name to delete filters for.
     *
     * @throws Ingo_Exception
     */
    public function removeUserData($user)
    {
        global $registry;

        if (!$registry->isAdmin() && ($user != $registry->getAuth())) {
            throw new Ingo_Exception(_("Permission Denied"));
        }

        $this->_removeUserData($user);

        $this->_rules = array();
    }

    /**
     * Removes the user data from the storage backend.
     *
     * @param string $user  The user name to delete filters for.
     *
     * @throws Ingo_Exception
     */
    protected abstract function _removeUserData($user);

    /**
     * Has the maximum number of rules been reached?
     *
     * @return integer  A MAX_* constant. Non-zero indicates rule cannot be
     *                  added.
     */
    public function maxRules()
    {
        global $injector;

        $this->_load();

        $max = $injector->getInstance('Horde_Core_Perms')
            ->hasAppPermission(Ingo_Perms::getPerm('max_rules'));

        if ($max === 0) {
            return self::MAX_NONE;
        } elseif (($max !== true) && ($max <= count($this))) {
            return self::MAX_OVER;
        }

        return self::MAX_OK;
    }

    /**
     * Adds a rule to the filters list.
     *
     * @param Ingo_Rule $rule  A rule object.
     */
    public function addRule(Ingo_Rule $rule)
    {
        $this->_load();

        $this->_rules[] = $rule;
        $this->_store(self::STORE_ADD, $rule);
    }

    /**
     * Updates an existing rule.
     *
     * @param Ingo_Rule $rule  A rule object.
     */
    public function updateRule(Ingo_Rule $rule)
    {
        if (($key = $this->_getRule($rule)) === null) {
            $this->addRule($rule);
        } else {
            $this->_rules[$key] = $rule;
            $this->_store(self::STORE_UPDATE, $rule);
        }
    }

    /**
     * Deletes an existing rule.
     *
     * @param Ingo_Rule $rule  A rule object.
     *
     * @return boolean  True if the rule has been found and deleted.
     */
    public function deleteRule(Ingo_Rule $rule)
    {
        if (!($rule instanceof Ingo_Rule_System) &&
            (($key = $this->_getRule($rule)) !== null)) {
            unset($this->_rules[$key]);
            $this->_store(self::STORE_DELETE, $rule);
            return true;
        }

        return false;
    }

    /**
     * Creates a copy of an existing rule.
     *
     * The created copy is added to the filters list after the original rule.
     *
     * @param Ingo_Rule $rule  The rule object to copy.
     *
     * @return boolean  True if the rule has been found and copied.
     */
    public function copyRule(Ingo_Rule $rule)
    {
        if (($rule instanceof Ingo_Rule_System) ||
            (($key = $this->_getRule($rule)) === null)) {
            return false;
        }

        $newrule = clone $this->_rules[$key];
        $newrule->name = sprintf(_("Copy of %s"), $newrule->name);
        $newrule->uid = '';

        $this->_rules = array_values(array_merge(
            array_slice($this->_rules, 0, $key + 1),
            array($newrule),
            array_slice($this->_rules, $key + 1)
        ));

        $this->_store(self::STORE_ADD, $newrule);

        return true;
    }

    /**
     * Sorts the list of rules in the given order.
     *
     * @param array $rules  Sorted list of rule UIDs.
     */
    public function sort($rules)
    {
        $this->_load();

        $rules = array_flip($rules);

        usort($this->_rules, function ($a, $b) use ($rules) {
            $pos_a = isset($rules[$a->uid]) ? $rules[$a->uid] : null;
            $pos_b = isset($rules[$b->uid]) ? $rules[$b->uid] : null;

            if (is_null($pos_a)) {
                return is_null($pos_b) ? 0 : 1;
            }

            return is_null($pos_b)
                ? -1
                : (($pos_a < $pos_b) ? -1 : 1);
        });

        $this->_store(self::STORE_SORT);
    }

    /**
     * Returns a rule given a UID.
     *
     * @param string $uid  Rule UID.
     *
     * @return Ingo_Rule  The rule object (null if not found).
     */
    public function getRuleByUid($uid)
    {
        $this->_load();

        foreach ($this->_rules as $key => $val) {
            if ($val->uid == $uid) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Store a rule.
     *
     * @param integer $action  Storage action.
     * @param Ingo_Rule $rule  Rule the action affects.
     */
    protected function _store($action, $rule = null)
    {
        global $session;

        $this->_storeBackend($action, $rule);

        switch ($action) {
        case self::STORE_UPDATE:
            if ($rule instanceof Ingo_Rule_System_Blacklist) {
                $tmp = $this->getSystemRule('Ingo_Rule_System_Whitelist');
            } elseif ($rule instanceof Ingo_Rule_System_Whitelist) {
                $tmp = $this->getSystemRule('Ingo_Rule_System_Blacklist');
            } else {
                $tmp = null;
            }

            if (!is_null($tmp)) {
                /* Filter out the rule's addresses in the opposite filter. */
                $ob = new Horde_Mail_Rfc822_List($tmp->addresses);
                $ob->setIteratorFilter(0, $rule->addresses);
                $tmp->addresses = $ob->bare_addresses;

                $this->_storeBackend($action, $tmp);
            }
            break;
        }

        $session->set('ingo', 'change', time());
    }

    /**
     * Store a rule in the backend.
     *
     * @see _store()
     */
    protected abstract function _storeBackend($action, $rule);

    /**
     * Retrieves a rule.
     *
     * @param Ingo_Rule $rule  The rule object.
     *
     * @return integer  The key of the rule in the rules list.
     */
    protected function _getRule(Ingo_Rule $rule)
    {
        $this->_load();

        foreach ($this->_rules as $key => $val) {
            if (strlen($rule->uid)) {
                if ($val->uid == $rule->uid) {
                    return $key;
                }
            } elseif (($rule instanceof Ingo_Rule_System) &&
                      ($rule instanceof $val)) {
                $rule->uid = $val->uid;
                return $key;
            }
        }

        return null;
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        $this->_load();

        return count($this->_rules);
    }

    /* IteratorAggregate methods. */

    /**
     */
    public function getIterator()
    {
        $this->_load();

        return new ArrayIterator($this->_rules);
    }

}
