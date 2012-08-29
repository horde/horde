<?php
/**
 * Ingo_Storage:: defines an API to store the various filter rules.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
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
     * Cached rule objects.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @params array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Shutdown function.
     */
    public function shutdown()
    {
        /* Store the current objects. */
        foreach ($this->_cache as $key => $val) {
            if ($val['mod'] || !$GLOBALS['session']->exists('ingo', 'storage/' . $key)) {
                $GLOBALS['session']->set('ingo', 'storage/' . $key, $GLOBALS['session']->store($val['ob'], false));
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
                $cached = $GLOBALS['session']->retrieve($GLOBALS['session']->get('ingo', 'storage/' . $field));
                $this->_cache[$field] = array(
                    'mod' => false,
                    'ob' => $cached ? $cached : $this->_retrieve($field, $readonly)
                );
            }
            return $this->_cache[$field]['ob'];
        }

        return $this->_retrieve($field, $readonly);
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
     * @throws Ingo_Exception
     */
    public function store($ob, $cache = true)
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

        $this->_store($ob);
        if ($cache) {
            $this->_cache[$ob->obType()] = array('ob' => $ob, 'mod' => true);
        }
    }

    /**
     * Stores the specified data in the storage backend.
     *
     * @param Ingo_Storage_Rule|Ingo_Storage_Filters $ob  The object to store.
     */
    protected function _store($ob)
    {
    }

    /**
     * Returns information on a given action constant.
     *
     * @param integer $action  The ACTION_* value.
     *
     * @return object  Object with the following values:
     *   - flags: (boolean) Does this action allow flags to be set?
     *   - label: (string) The label for this action.
     *   - type: (string) Either 'folder', 'text', or empty.
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
     * @return object  Object with the following values:
     *   - label: (string) The label for this action.
     *   - type: (string) Either 'int', 'none', or 'text'.
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
