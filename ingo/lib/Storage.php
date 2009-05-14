<?php
/**
 * Ingo_Storage:: defines an API to store the various filter rules.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Storage
{
    /**
     * Ingo_Storage:: 'combine' constants
     */
    const COMBINE_ALL = 1;
    const COMBINE_ANY = 2;

    /**
     * Ingo_Storage:: 'action' constants
     */
    const ACTION_FILTERS = 0;
    const ACTION_KEEP = 1;
    const ACTION_MOVE = 2;
    const ACTION_DISCARD = 3;
    const ACTION_REDIRECT = 4;
    const ACTION_REDIRECTKEEP = 5;
    const ACTION_REJECT = 6;
    const ACTION_BLACKLIST = 7;
    const ACTION_VACATION = 8;
    const ACTION_WHITELIST = 9;
    const ACTION_FORWARD = 10;
    const ACTION_MOVEKEEP = 11;
    const ACTION_FLAGONLY = 12;
    const ACTION_NOTIFY = 13;
    const ACTION_SPAM = 14;

    /**
     * Ingo_Storage:: 'flags' constants
     */
    const FLAG_ANSWERED = 1;
    const FLAG_DELETED = 2;
    const FLAG_FLAGGED = 4;
    const FLAG_SEEN = 8;

    /**
     * Ingo_Storage:: 'type' constants.
     */
    const TYPE_HEADER = 1;
    const TYPE_SIZE = 2;
    const TYPE_BODY = 3;

    /**
     * Driver specific parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Cached rule objects.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Attempts to return a concrete Ingo_Storage instance based on $driver.
     *
     * @param string $driver  The type of concrete Ingo_Storage subclass to
     *                        return.  This is based on the storage driver
     *                        ($driver).  The code is dynamically included.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Ingo_Storage instance, or
     *                false on an error.
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Ingo_Storage_' . $driver;
        return class_exists($class)
            ? new $class($params)
            : false;
    }

    /**
     * Destructor.
     */
    protected function __destruct()
    {
        $cache = &Horde_SessionObjects::singleton();

        /* Store the current objects. */
        foreach ($this->_cache as $key => $val) {
            if (!$val['mod'] && isset($_SESSION['ingo']['storage'][$key])) {
                continue;
            }
            if (isset($_SESSION['ingo']['storage'][$key])) {
                $cache->setPruneFlag($_SESSION['ingo']['storage'][$key], true);
            }
            $_SESSION['ingo']['storage'][$key] = $cache->storeOid($val['ob'], false);
        }
    }

    /**
     * Retrieves the specified data.
     *
     * @param integer $field     The field name of the desired data
     *                           (INGO_STORAGE_ACTION_* constants).
     * @param boolean $cache     Use the cached object?
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_rule|Ingo_Storage_filters  The specified object.
     */
    public function retrieve($field, $cache = true, $readonly = false)
    {
        /* Don't cache if using shares. */
        if ($cache && empty($GLOBALS['ingo_shares'])) {
            if (!isset($this->_cache[$field])) {
                $this->_cache[$field] = array('mod' => false);
                if (isset($_SESSION['ingo']['storage'][$field])) {
                    $cacheSess = &Horde_SessionObjects::singleton();
                    $this->_cache[$field]['ob'] = $cacheSess->query($_SESSION['ingo']['storage'][$field]);
                } else {
                    $this->_cache[$field]['ob'] = &$this->_retrieve($field, $readonly);
                }
            }
            $ob = &$this->_cache[$field]['ob'];
        } else {
            $ob = &$this->_retrieve($field, $readonly);
        }

        return $ob;
    }

    /**
     * Retrieves the specified data from the storage backend.
     *
     * @abstract
     *
     * @param integer $field     The field name of the desired data.
     *                           See lib/Storage.php for the available fields.
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_rule|Ingo_Storage_filters  The specified data.
     */
    protected function _retrieve($field, $readonly = false)
    {
        return false;
    }

    /**
     * Stores the specified data.
     *
     * @param Ingo_Storage_rule|Ingo_Storage_filters $ob  The object to store.
     * @param boolean $cache                              Cache the object?
     *
     * @return boolean  True on success.
     */
    public function store(&$ob, $cache = true)
    {
        $type = $ob->obType();
        if (in_array($type, array(self::ACTION_BLACKLIST,
                                  self::ACTION_VACATION,
                                  self::ACTION_WHITELIST,
                                  self::ACTION_FORWARD,
                                  self::ACTION_SPAM))) {
            $filters = $this->retrieve(self::ACTION_FILTERS);
            if ($filters->findRuleId($type) === null) {
                switch ($type) {
                case self::ACTION_BLACKLIST:
                    $name = 'Blacklist';
                    break;

                case self::ACTION_VACATION:
                    $name = 'Vacation';
                    break;

                case self::ACTION_WHITELIST:
                    $name = 'Whitelist';
                    break;

                case self::ACTION_FORWARD:
                    $name = 'Forward';
                    break;

                case self::ACTION_SPAM:
                    $name = 'Spam Filter';
                    break;
                }
                $filters->addRule(array('action' => $type, 'name' => $name));
                $result = $this->store($filters, $cache);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
        }

        $result = $this->_store($ob);
        if ($cache) {
            $this->_cache[$ob->obType()] = array('ob' => $ob, 'mod' => true);
        }

        return $result;
    }

    /**
     * Stores the specified data in the storage backend.
     *
     * @abstract
     *
     * @param Ingo_Storage_rule|Ingo_Storage_filters $ob  The object to store.
     *
     * @return boolean  True on success.
     */
    protected function _store(&$ob)
    {
        return false;
    }

    /**
     * Returns information on a given action constant.
     *
     * @param integer $action  The INGO_STORAGE_ACTION_* value.
     *
     * @return stdClass  Object with the following values:
     * <pre>
     * 'flags' => (boolean) Does this action allow flags to be set?
     * 'label' => (string) The label for this action.
     * 'type'  => (string) Either 'folder', 'text', or empty.
     * </pre>
     */
    public function getActionInfo($action)
    {
        $ob = &new stdClass;
        $ob->flags = false;
        $ob->type = 'text';

        switch ($action) {
        case self::ACTION_KEEP:
            $ob->label = _("Deliver into my Inbox");
            $ob->type = false;
            $ob->flags = true;
            break;

        case self::ACTION_MOVE:
            $ob->label = _("Deliver to folder...");
            $ob->type = 'folder';
            $ob->flags = true;
            break;

        case self::ACTION_DISCARD:
            $ob->label = _("Delete message completely");
            $ob->type = false;
            break;

        case self::ACTION_REDIRECT:
            $ob->label = _("Redirect to...");
            break;

        case self::ACTION_REDIRECTKEEP:
            $ob->label = _("Deliver into my Inbox and redirect to...");
            $ob->flags = true;
            break;

        case self::ACTION_MOVEKEEP:
            $ob->label = _("Deliver into my Inbox and copy to...");
            $ob->type = 'folder';
            $ob->flags = true;
            break;

        case self::ACTION_REJECT:
            $ob->label = _("Reject with reason...");
            break;

        case self::ACTION_FLAGONLY:
            $ob->label = _("Only flag the message");
            $ob->type = false;
            $ob->flags = true;
            break;

        case self::ACTION_NOTIFY:
            $ob->label = _("Notify email address...");
            break;
        }

        return $ob;
    }

    /**
     * Returns information on a given test string.
     *
     * @param string $action  The test string.
     *
     * @return stdClass  Object with the following values:
     * <pre>
     * 'label' => (string) The label for this action.
     * 'type'  => (string) Either 'int', 'none', or 'text'.
     * </pre>
     */
    public function getTestInfo($test)
    {
        /* Mapping of gettext strings -> labels. */
        $labels = array(
            'contains' => _("Contains"),
            'not contain' =>  _("Doesn't contain"),
            'is' => _("Is"),
            'not is' => _("Isn't"),
            'begins with' => _("Begins with"),
            'not begins with' => _("Doesn't begin with"),
            'ends with' => _("Ends with"),
            'not ends with' => _("Doesn't end with"),
            'exists' =>  _("Exists"),
            'not exist' => _("Doesn't exist"),
            'regex' => _("Regular expression"),
            'matches' => _("Matches (with placeholders)"),
            'not matches' => _("Doesn't match (with placeholders)"),
            'less than' => _("Less than"),
            'less than or equal to' => _("Less than or equal to"),
            'greater than' => _("Greater than"),
            'greater than or equal to' => _("Greater than or equal to"),
            'equal' => _("Equal to"),
            'not equal' => _("Not equal to")
        );

        /* The type of tests available. */
        $types = array(
            'int'  => array(
                'less than', 'less than or equal to', 'greater than',
                'greater than or equal to', 'equal', 'not equal'
            ),
            'none' => array(
                'exists', 'not exist'
            ),
            'text' => array(
                'contains', 'not contain', 'is', 'not is', 'begins with',
                'not begins with', 'ends with', 'not ends with', 'regex',
                'matches', 'not matches'
            )
        );

        /* Create the information object. */
        $ob = &new stdClass;
        $ob->label = $labels[$test];
        foreach ($types as $key => $val) {
            if (in_array($test, $val)) {
                $ob->type = $key;
                break;
            }
        }

        return $ob;
    }

}

/**
 * Ingo_Storage_rule:: is the base class for the various action objects
 * used by Ingo_Storage.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_rule
{
    /**
     * The object type.
     *
     * @var integer
     */
    protected $_obtype;

    /**
     * Whether the rule has been saved (if being saved separately).
     *
     * @var boolean
     */
    protected $_saved = false;

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
     * Marks the rule as saved or unsaved.
     *
     * @param boolean $data  Whether the rule has been saved.
     */
    public function setSaved($data)
    {
        $this->_saved = $data;
    }

    /**
     * Returns whether the rule has been saved.
     *
     * @return boolean  True if the rule has been saved.
     */
    public function isSaved()
    {
        return $this->_saved;
    }

    /**
     * Function to manage an internal address list.
     *
     * @param mixed $data    The incoming data (array or string).
     * @param boolean $sort  Sort the list?
     *
     * @return array  The address list.
     */
    protected function _addressList($data, $sort)
    {
        $output = array();

        if (is_array($data)) {
            $output = $data;
        } else {
            $data = trim($data);
            $output = (empty($data)) ? array() : preg_split("/\s+/", $data);
        }

        if ($sort) {
            $output = Horde_Array::prepareAddressList($output);
        }

        return $output;
    }

}

/**
 * Ingo_Storage_blacklist is the object used to hold blacklist rule
 * information.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_blacklist extends Ingo_Storage_rule
{
    protected $_addr = array();
    protected $_folder = '';
    protected $_obtype = Ingo_Storage::ACTION_BLACKLIST;

    /**
     * Sets the list of blacklisted addresses.
     *
     * @param mixed $data    The list of addresses (array or string).
     * @param boolean $sort  Sort the list?
     *
     * @return mixed  PEAR_Error on error, true on success.
     */
    public function setBlacklist($data, $sort = true)
    {
        $addr = &$this->_addressList($data, $sort);
        if (!empty($GLOBALS['conf']['storage']['maxblacklist'])) {
            $addr_count = count($addr);
            if ($addr_count > $GLOBALS['conf']['storage']['maxblacklist']) {
                return PEAR::raiseError(sprintf(_("Maximum number of blacklisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to blacklist."), $addr_count, $GLOBALS['conf']['storage']['maxblacklist']), 'horde.error');
            }
        }

        $this->_addr = $addr;
        return true;
    }

    public function setBlacklistFolder($data)
    {
        $this->_folder = $data;
    }

    public function getBlacklist()
    {
        return array_filter($this->_addr, array('Ingo', '_filterEmptyAddress'));
    }

    public function getBlacklistFolder()
    {
        return $this->_folder;
    }

}

/**
 * Ingo_Storage_whitelist is the object used to hold whitelist rule
 * information.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_whitelist extends Ingo_Storage_rule
{
    protected $_addr = array();
    protected $_obtype = Ingo_Storage::ACTION_WHITELIST;

    /**
     * Sets the list of whitelisted addresses.
     *
     * @param mixed $data    The list of addresses (array or string).
     * @param boolean $sort  Sort the list?
     *
     * @return mixed  PEAR_Error on error, true on success.
     */
    public function setWhitelist($data, $sort = true)
    {
        $addr = &$this->_addressList($data, $sort);
        $addr = array_filter($addr, array('Ingo', '_filterEmptyAddress'));
        if (!empty($GLOBALS['conf']['storage']['maxwhitelist'])) {
            $addr_count = count($addr);
            if ($addr_count > $GLOBALS['conf']['storage']['maxwhitelist']) {
                return PEAR::raiseError(sprintf(_("Maximum number of whitelisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to whitelist."), $addr_count, $GLOBALS['conf']['storage']['maxwhitelist']), 'horde.error');
            }
        }

        $this->_addr = $addr;
        return true;
    }

    public function getWhitelist()
    {
        return array_filter($this->_addr, array('Ingo', '_filterEmptyAddress'));
    }

}

/**
 * Ingo_Storage_forward is the object used to hold mail forwarding rule
 * information.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_forward extends Ingo_Storage_rule
{
    protected $_addr = array();
    protected $_keep = true;
    protected $_obtype = Ingo_Storage::ACTION_FORWARD;

    public function setForwardAddresses($data, $sort = true)
    {
        $this->_addr = &$this->_addressList($data, $sort);
    }

    public function setForwardKeep($data)
    {
        $this->_keep = $data;
    }

    public function getForwardAddresses()
    {
        if (is_array($this->_addr)) {
            foreach ($this->_addr as $key => $val) {
                if (empty($val)) {
                    unset($this->_addr[$key]);
                }
            }
        }
        return $this->_addr;
    }

    public function getForwardKeep()
    {
        return $this->_keep;
    }

}

/**
 * Ingo_Storage_vacation is the object used to hold vacation rule
 * information.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_vacation extends Ingo_Storage_rule
{
    protected $_addr = array();
    protected $_days = 7;
    protected $_excludes = array();
    protected $_ignorelist = true;
    protected $_reason = '';
    protected $_subject = '';
    protected $_start;
    protected $_end;
    protected $_obtype = Ingo_Storage::ACTION_VACATION;

    public function setVacationAddresses($data, $sort = true)
    {
        $this->_addr = &$this->_addressList($data, $sort);
    }

    public function setVacationDays($data)
    {
        $this->_days = $data;
    }

    public function setVacationExcludes($data, $sort = true)
    {
        $this->_excludes = &$this->_addressList($data, $sort);
    }

    public function setVacationIgnorelist($data)
    {
        $this->_ignorelist = $data;
    }

    public function setVacationReason($data)
    {
        $this->_reason = $data;
    }

    public function setVacationSubject($data)
    {
        $this->_subject = $data;
    }

    public function setVacationStart($data)
    {
        $this->_start = $data;
    }

    public function setVacationEnd($data)
    {
        $this->_end = $data;
    }

    public function getVacationAddresses()
    {
        if (empty($GLOBALS['conf']['hooks']['vacation_addresses'])) {
            return $this->_addr;
        }

        $addresses = Horde::callHook('_ingo_hook_vacation_addresses', array(Ingo::getUser()), 'ingo');
        if (is_a($addresses, 'PEAR_Error')) {
            $addresses = array();
        }
        return $addresses;
    }

    public function getVacationDays()
    {
        return $this->_days;
    }

    public function getVacationExcludes()
    {
        return $this->_excludes;
    }

    public function getVacationIgnorelist()
    {
        return $this->_ignorelist;
    }

    public function getVacationReason()
    {
        return $this->_reason;
    }

    public function getVacationSubject()
    {
        return $this->_subject;
    }

    public function getVacationStart()
    {
        return $this->_start;
    }

    public function getVacationStartYear()
    {
        return date('Y', $this->_start);
    }

    public function getVacationStartMonth()
    {
        return date('n', $this->_start);
    }

    public function getVacationStartDay()
    {
        return date('j', $this->_start);
    }

    public function getVacationEnd()
    {
        return $this->_end;
    }

    public function getVacationEndYear()
    {
        return date('Y', $this->_end);
    }

    public function getVacationEndMonth()
    {
        return date('n', $this->_end);
    }

    public function getVacationEndDay()
    {
        return date('j', $this->_end);
    }

}

/**
 * Ingo_Storage_spam is an object used to hold default spam-rule filtering
 * information.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 */
class Ingo_Storage_spam extends Ingo_Storage_rule
{

    /**
     * The object type.
     *
     * @var integer
     */
    protected $_obtype = Ingo_Storage::ACTION_SPAM;

    protected $_folder = null;
    protected $_level = 5;

    public function setSpamFolder($folder)
    {
        $this->_folder = $folder;
    }

    public function setSpamLevel($level)
    {
        $this->_level = $level;
    }

    public function getSpamFolder()
    {
        return $this->_folder;
    }

    public function getSpamLevel()
    {
        return $this->_level;
    }

}

/**
 * Ingo_Storage_filters is the object used to hold user-defined filtering rule
 * information.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_filters
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
     * @return array  The list of rule hashes.
     */
    public function getFilterlist()
    {
        return $this->_filters;
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
            'combine' => INGO_STORAGE_COMBINE_ALL,
            'conditions' => array(),
            'action' => INGO_STORAGE_ACTION_KEEP,
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
     *                         (INGO_STORAGE_ACTION_* constants).
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
     *                         (INGO_STORAGE_ACTION_* constants).
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
    public function addRule($rule, $default = true)
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
     * Moves a rule up in the filters list.
     *
     * @param integer $id     Number of the rule to move.
     * @param integer $steps  Number of positions to move the rule up.
     */
    public function ruleUp($id, $steps = 1)
    {
        for ($i = 0; $i < $steps && $id > 0;) {
            $temp = $this->_filters[$id - 1];
            $this->_filters[$id - 1] = $this->_filters[$id];
            $this->_filters[$id] = $temp;
            /* Continue to move up until we swap with a viewable category. */
            if (in_array($temp['action'], $_SESSION['ingo']['script_categories'])) {
                $i++;
            }
            $id--;
        }
    }

    /**
     * Moves a rule down in the filters list.
     *
     * @param integer $id     Number of the rule to move.
     * @param integer $steps  Number of positions to move the rule down.
     */
    public function ruleDown($id, $steps = 1)
    {
        $rulecount = count($this->_filters) - 1;
        for ($i = 0; $i < $steps && $id < $rulecount;) {
            $temp = $this->_filters[$id + 1];
            $this->_filters[$id + 1] = $this->_filters[$id];
            $this->_filters[$id] = $temp;
            /* Continue to move down until we swap with a viewable
               category. */
            if (in_array($temp['action'], $_SESSION['ingo']['script_categories'])) {
                $i++;
            }
            $id++;
        }
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
