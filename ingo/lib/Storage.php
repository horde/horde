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

        $class = 'Ingo_Storage_' . ucfirst($driver);
        return class_exists($class)
            ? new $class($params)
            : false;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Shutdown function.
     */
    public function shutdown()
    {
        /* Store the current objects. */
        foreach ($this->_cache as $key => $val) {
            if ($val['mod'] || !isset($_SESSION['ingo']['storage'][$key])) {
                $_SESSION['ingo']['storage'][$key] = $GLOBALS['session']->store($val['ob'], false);
            }
        }
    }

    /**
     * Retrieves the specified data.
     *
     * @param integer $field     The field name of the desired data
     *                           (ACTION_* constants).
     * @param boolean $cache     Use the cached object?
     * @param boolean $readonly  Whether to disable any write operations.
     *
     * @return Ingo_Storage_Rule|Ingo_Storage_Filters  The specified object.
     * @throws Ingo_Exception
     */
    public function retrieve($field, $cache = true, $readonly = false)
    {
        /* Don't cache if using shares. */
        if ($cache && empty($GLOBALS['ingo_shares'])) {
            if (!isset($this->_cache[$field])) {
                $this->_cache[$field] = array(
                    'mod' => false,
                    'ob' => isset($_SESSION['ingo']['storage'][$field])
                        ? $GLOBALS['session'][$_SESSION['ingo']['storage'][$field]]
                        : $this->_retrieve($field, $readonly)
                );
            }
            $ob = $this->_cache[$field]['ob'];
        } else {
            $ob = $this->_retrieve($field, $readonly);
        }

        return $ob;
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
        return false;
    }

    /**
     * Stores the specified data.
     *
     * @param Ingo_Storage_Rule|Ingo_Storage_Filters $ob  The object to store.
     * @param boolean $cache                              Cache the object?
     *
     * @return boolean  True on success.
     * @throws Ingo_Exception
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
                $this->store($filters, $cache);
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
     * @param Ingo_Storage_Rule|Ingo_Storage_Filters $ob  The object to store.
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
     * @param integer $action  The ACTION_* value.
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
        $ob = new stdClass;
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
        $ob = new stdClass;
        $ob->label = $labels[$test];
        foreach ($types as $key => $val) {
            if (in_array($test, $val)) {
                $ob->type = $key;
                break;
            }
        }

        return $ob;
    }

    /**
     * Removes the user data from the storage backend.
     * Stub for child class to override if it can implement.
     *
     * @param string $user  The user name to delete filters for.
     *
     * @throws Ingo_Exception
     */
    public function removeUserData($user)
    {
	    throw new Ingo_Exception(_("Removing user data is not supported with the current filter storage backend."));
    }

}
