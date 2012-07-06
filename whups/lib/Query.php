<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

/**
 * array(
 *     'type'      => Whups_Query::TYPE_...
 *     'children'  => array(...) unless type == Whups_Query::TYPE_CRITERION
 *     'criterion' => Whups_Query::CRITERION_... if  type == Whups_Query::TYPE_CRITERION
 *     'operator'  => Whups_Query::OPERATOR_...  if  type == Whups_Query::TYPE_CRITERION
 *     'value'     => other argument to operator of criterion
 */

/**
 * @package Whups
 */
class Whups_Query
{

    /** Mode types. */
    const TYPE_AND = 1;
    const TYPE_OR = 2;
    const TYPE_NOT = 3;
    const TYPE_CRITERION = 4;

    /** Criterion types. */
    const CRITERION_ID = 1;
    const CRITERION_QUEUE = 2;
    const CRITERION_TYPE = 3;
    const CRITERION_STATE = 4;
    const CRITERION_PRIORITY = 5;
    const CRITERION_OWNERS = 7;
    const CRITERION_REQUESTER = 8;
    const CRITERION_GROUPS = 9;
    const CRITERION_ADDED_COMMENT = 11;
    const CRITERION_COMMENT = 12;
    const CRITERION_SUMMARY = 13;
    const CRITERION_ATTRIBUTE = 14;
    const CRITERION_VERSION = 15;
    const CRITERION_TIMESTAMP = 16;
    const CRITERION_UPDATED = 17;
    const CRITERION_RESOLVED = 18;
    const CRITERION_ASSIGNED = 19;
    const CRITERION_DUE = 20;

    /** Operators for integer fields. */
    const OPERATOR_GREATER = 1;
    const OPERATOR_LESS = 2;
    const OPERATOR_EQUAL = 3;

    /** Operators for text fields. */
    const OPERATOR_CI_SUBSTRING = 4;
    const OPERATOR_CS_SUBSTRING = 5;
    const OPERATOR_WORD =  6;
    const OPERATOR_PATTERN = 7;

    /**
     * @var Whups_Query_Manager
     */
    protected $_qManager;

    /**
     * Query id.
     *
     * @var integer
     */
    public $id;

    /**
     * The full name of the query.
     *
     * @var string
     */
    public $name;

    /**
     * The query slug (short name).
     *
     * @var string
     */
    public $slug;

    /**
     * @var array
     */
    public $query = array(
        'type' => Whups_Query::TYPE_AND,
        'children' => array());

    /**
     * @var array
     */
    public $parameters = array();

    /**
     * Constructor
     *
     * @param Whups_Query_Manager $qManager
     * @param array $qDetails
     */
    function __construct(Whups_Query_Manager &$qManager, array $qDetails = array())
    {
        $this->_qManager = &$qManager;
        if (isset($qDetails['query_id'])) {
            $this->id = $qDetails['query_id'];
        }
        if (isset($qDetails['query_name'])) {
            $this->name = $qDetails['query_name'];
        }
        if (isset($qDetails['query_slug'])) {
            $this->slug = $qDetails['query_slug'];
        }
        if (isset($qDetails['query_object'])) {
            $this->query = @unserialize($qDetails['query_object']);
        }
        if (isset($qDetails['query_parameters'])) {
            $this->parameters = @unserialize($qDetails['query_parameters']);
        }
    }

    /**
     * @static
     */
    public static function pathToString(&$path)
    {
        return implode(',', $path);
    }

    /**
     * @static
     */
    public static function stringToPath($pathstring)
    {
        if (!strlen($pathstring)) {
            return array();
        }

        return explode(',', $pathstring);
    }

    /**
     * Returns human readable descriptions of all operator types.
     *
     * @static
     *
     * @return array  Hash with operator types and descriptions.
     */
    public static function textOperators()
    {
        return array(
            Whups_Query::OPERATOR_EQUAL        => _("Exact Match"),
            Whups_Query::OPERATOR_CI_SUBSTRING => _("Case Insensitive Substring"),
            Whups_Query::OPERATOR_CS_SUBSTRING => _("Case Sensitive Substring"),
            Whups_Query::OPERATOR_WORD         => _("Match Word"),
            Whups_Query::OPERATOR_PATTERN      => _("Match Pattern"));
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    public function hasPermission($userid, $permission, $creator = null)
    {
        return $this->_qManager->hasPermission($this->id, $userid, $permission, $creator);
    }

    /**
     * Saves any changes to this object to the backend
     * permanently. New objects are added instead.
     *
     */
    public function save()
    {
        $this->_qManager->save($this);
    }

    /**
     * Delete this object from the backend permanently.
     *
     */
    public function delete()
    {
        $this->_qManager->delete($this);
    }

    /**
     * Returns <link> data for this query's feed.
     *
     * @return array  Link data.
     */
    public function feedLink()
    {
        return array(
            'href' => Whups::urlFor('query_rss', empty($this->slug) ? array('id' => $this->id) : array('slug' => $this->slug), true, -1),
            'title' => $this->name
        );
    }

    /**
     * Tab operations for this query.
     *
     * @params Horde_Variables
     */
    public function getTabs(Horde_Variables $vars)
    {
        // Create a few variables that are reused.
        $queryurl = Horde::url('query/index.php');
        $edit = $this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        $delete = $this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE);

        $tabs = new Horde_Core_Ui_Tabs('action', $vars);
        $tabs->addTab(_("Ne_w Query"), $queryurl, 'new');
        if (!$this->id || $edit) {
            $tabs->addTab(_("_Edit Query"), $queryurl, 'edit');
        }
        if ($this->id && $edit && empty($GLOBALS['conf']['share']['no_sharing'])) {
            $GLOBALS['page_output']->addScriptFile('popup.js', 'horde');

            $permsurl = $GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php';
            $permsurl = Horde_Util::addParameter(
                $permsurl,
                array(
                    'app' => 'whups',
                    'cid' => $this->id));
            $tabs->addTab(
                _("Edit _Permissions"),
                $permsurl,
                array(
                    'tabname' => 'perms',
                    'onclick' => 'popup(\'' . $permsurl . '\'); return false;',
                    'target' => '_blank'));
        }
        $tabs->addTab(_("E_xecute Query"), Horde::url('query/run.php'), 'run');
        $tabs->addTab(_("_Load Query"), $queryurl, 'load');
        if ((!$this->id && $GLOBALS['registry']->getAuth()) ||
            ($this->id && $edit)) {
            $tabs->addTab(_("Sa_ve Query"), $queryurl, 'save');
        }
        if ($this->id && $delete) {
            $tabs->addTab(_("_Delete Query"), $queryurl, 'delete');
        }

        return $tabs;
    }

    /**
     * Path to form
     *
     * @param Horde_Variables $vars
     *
     * @return string
     * @throws Whups_Exception
     */
    public function pathToForm(&$vars)
    {
        $path = Whups_Query::stringToPath($vars->get('path'));
        $parent = null;
        $qobj = $this->query;

        for ($i = 0, $c = count($path); $i < $c; $i++) {
            $parent = $qobj;
            $qobj = $qobj['children'][$path[$i]];
        }

        if ($qobj['type'] != Whups_Query::TYPE_CRITERION) {
            // Search for any criteria that have been combined automatically
            // with an AND or OR.
            switch ($qobj['type']) {
            case Whups_Query::TYPE_OR:
                // Search for multiple ids.
                $criteria = array();
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        $child['criterion'] != Whups_Query::CRITERION_ID) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['value'];
                }
                if ($criteria) {
                    $vars->set('id', implode(',', $criteria));
                    return 'props';
                }

                // Search for user criteria.
                $criteria = array();
                $operator = $value = null;
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        ($child['criterion'] != Whups_Query::CRITERION_OWNERS &&
                         $child['criterion'] != Whups_Query::CRITERION_REQUESTER &&
                         $child['criterion'] != Whups_Query::CRITERION_ADDED_COMMENT) ||
                        (isset($operator) && $operator != $child['operator']) ||
                        (isset($value) && $value != $child['value'])) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['criterion'];
                    $operator = $child['operator'];
                    $value = $child['value'];
                }
                if ($criteria) {
                    $vars->set('user', $value);
                    $vars->set('operator', $operator);
                    foreach ($criteria as $criterion) {
                        switch ($criterion) {
                        case Whups_Query::CRITERION_OWNERS:
                            $vars->set('owners', true);
                            break;
                        case Whups_Query::CRITERION_REQUESTER:
                            $vars->set('requester', true);
                            break;
                        case Whups_Query::CRITERION_ADDED_COMMENT:
                            $vars->set('comments', true);
                            break;
                        }
                    }
                    return 'user';
                }

                // Search for text criteria.
                $criteria = array();
                $operator = $value = null;
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        ($child['criterion'] != Whups_Query::CRITERION_COMMENT &&
                         $child['criterion'] != Whups_Query::CRITERION_SUMMARY) ||
                        (isset($operator) && $operator != $child['operator']) ||
                        (isset($value) && $value != $child['value'])) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['criterion'];
                    $operator = $child['operator'];
                    $value = $child['value'];
                }
                if ($criteria) {
                    $vars->set('text', $value);
                    $vars->set('operator', $operator);
                    foreach ($criteria as $criterion) {
                        if ($criterion == Whups_Query::CRITERION_COMMENT) {
                            $vars->set('comments', true);
                        } elseif ($criterion == Whups_Query::CRITERION_SUMMARY) {
                            $vars->set('summary', true);
                        }
                    }
                    return 'text';
                }

                // Search for attributes.
                $attribs = array_keys($GLOBALS['whups_driver']->getAttributesForType());
                $criteria = array();
                $operator = $value = null;
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        $child['criterion'] != Whups_Query::CRITERION_ATTRIBUTE ||
                        (isset($operator) && $operator != $child['operator']) ||
                        (isset($value) && $value != $child['value']) ||
                        !in_array($child['cvalue'], $attribs)) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['cvalue'];
                    $operator = $child['operator'];
                    $value = $child['value'];
                }
                if ($criteria) {
                    $vars->set('text', $value);
                    $vars->set('operator', $operator);
                    foreach ($criteria as $criterion) {
                        $vars->set('a' . $criterion, true);
                    }
                    return 'attribs';
                }
                break;

            case Whups_Query::TYPE_AND:
                // Search for date criteria.
                $criteria = false;
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        ($child['criterion'] != Whups_Query::CRITERION_TIMESTAMP &&
                         $child['criterion'] != Whups_Query::CRITERION_UPDATED &&
                         $child['criterion'] != Whups_Query::CRITERION_RESOLVED &&
                         $child['criterion'] != Whups_Query::CRITERION_ASSIGNED &&
                         $child['criterion'] != Whups_Query::CRITERION_DUE)) {
                        $criteria = false;
                        break;
                    }
                    $criteria = true;
                }
                if ($criteria) {
                    foreach ($qobj['children'] as $child) {
                        switch ($child['criterion'] . $child['operator']) {
                        case Whups_Query::CRITERION_TIMESTAMP . Whups_Query::OPERATOR_GREATER:
                            $vars->set('ticket_timestamp[from]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_TIMESTAMP . Whups_Query::OPERATOR_LESS:
                            $vars->set('ticket_timestamp[to]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_UPDATED . Whups_Query::OPERATOR_GREATER:
                            $vars->set('date_updated[from]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_UPDATED . Whups_Query::OPERATOR_LESS:
                            $vars->set('date_updated[to]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_RESOLVED . Whups_Query::OPERATOR_GREATER:
                            $vars->set('date_resolved[from]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_RESOLVED . Whups_Query::OPERATOR_LESS:
                            $vars->set('date_resolved[to]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_ASSIGNED . Whups_Query::OPERATOR_GREATER:
                            $vars->set('date_assigned[from]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_ASSIGNED . Whups_Query::OPERATOR_LESS:
                            $vars->set('date_assigned[to]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_DUE . Whups_Query::OPERATOR_GREATER:
                            $vars->set('ticket_due[from]', $child['value']);
                            break;
                        case Whups_Query::CRITERION_DUE . Whups_Query::OPERATOR_LESS:
                            $vars->set('ticket_due[to]', $child['value']);
                            break;
                        }
                    }
                    return 'date';
                }

                // Search for version criterion.
                if (count($qobj['children']) == 2 &&
                    $qobj['children'][0]['type'] == Whups_Query::TYPE_CRITERION &&
                    $qobj['children'][0]['criterion'] == Whups_Query::CRITERION_QUEUE &&
                    $qobj['children'][1]['type'] == Whups_Query::TYPE_CRITERION &&
                    $qobj['children'][1]['criterion'] == Whups_Query::CRITERION_VERSION) {
                    $vars->set('queue', $qobj['children'][0]['value']);
                    $vars->set('version', $qobj['children'][1]['value']);
                    return 'props';
                }
                break;
            }
            throw new Whups_Exception(_("This query element cannot be edited."));
        }

        switch ($qobj['criterion']) {
        case Whups_Query::CRITERION_ID:
            $multiple = false;
            if ($parent && $parent['type'] == Whups_Query::TYPE_OR) {
                $multiple = array();
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        $child['criterion'] != Whups_Query::CRITERION_ID) {
                        $multiple = false;
                        break;
                    }
                    $multiple[] = $child['value'];
                }
            }
            if ($multiple) {
                array_pop($path);
                $vars->set('path', Whups_Query::pathToString($path));
                $vars->set('id', implode(',', $multiple));
            } else {
                $vars->set('id', $qobj['value']);
            }
            return 'props';

        case Whups_Query::CRITERION_QUEUE:
            if ($parent && $parent['type'] == Whups_Query::TYPE_AND &&
                count($parent['children']) == 2 &&
                $parent['children'][1]['type'] == Whups_Query::TYPE_CRITERION &&
                $parent['children'][1]['criterion'] == Whups_Query::CRITERION_VERSION) {
                array_pop($path);
                $vars->set('path', Whups_Query::pathToString($path));
                $vars->set('version', $parent['children'][1]['value']);
            }
            $vars->set('queue', $qobj['value']);
            return 'props';

        case Whups_Query::CRITERION_VERSION:
            array_pop($path);
            $vars->set('path', Whups_Query::pathToString($path));
            $vars->set('queue', $parent['children'][0]['value']);
            $vars->set('version', $qobj['value']);
            return 'props';

        case Whups_Query::CRITERION_TYPE:
            $vars->set('ttype', $qobj['value']);
            return 'props';

        case Whups_Query::CRITERION_STATE:
            $vars->set('state', $qobj['value']);
            return 'props';

        case Whups_Query::CRITERION_PRIORITY:
            $vars->set('priority', $qobj['value']);
            return 'props';

        case Whups_Query::CRITERION_TIMESTAMP:
        case Whups_Query::CRITERION_UPDATED:
        case Whups_Query::CRITERION_RESOLVED:
        case Whups_Query::CRITERION_ASSIGNED:
        case Whups_Query::CRITERION_DUE:
            $criteria = false;
            if ($parent && $parent['type'] == Whups_Query::TYPE_AND) {
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        ($child['criterion'] != Whups_Query::CRITERION_TIMESTAMP &&
                         $child['criterion'] != Whups_Query::CRITERION_UPDATED &&
                         $child['criterion'] != Whups_Query::CRITERION_RESOLVED &&
                         $child['criterion'] != Whups_Query::CRITERION_ASSIGNED &&
                         $child['criterion'] != Whups_Query::CRITERION_DUE)) {
                        $criteria = false;
                        break;
                    }
                    $criteria = true;
                }
            }
            if ($criteria) {
                array_pop($path);
                $vars->set('path', Whups_Query::pathToString($path));
                foreach ($parent['children'] as $child) {
                    switch ($child['criterion'] . $child['operator']) {
                    case Whups_Query::CRITERION_TIMESTAMP . Whups_Query::OPERATOR_GREATER:
                        $vars->set('ticket_timestamp[from]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_TIMESTAMP . Whups_Query::OPERATOR_LESS:
                        $vars->set('ticket_timestamp[to]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_UPDATED . Whups_Query::OPERATOR_GREATER:
                        $vars->set('date_updated[from]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_UPDATED . Whups_Query::OPERATOR_LESS:
                        $vars->set('date_updated[to]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_RESOLVED . Whups_Query::OPERATOR_GREATER:
                        $vars->set('date_resolved[from]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_RESOLVED . Whups_Query::OPERATOR_LESS:
                        $vars->set('date_resolved[to]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_ASSIGNED . Whups_Query::OPERATOR_GREATER:
                        $vars->set('date_assigned[from]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_ASSIGNED . Whups_Query::OPERATOR_LESS:
                        $vars->set('date_assigned[to]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_DUE . Whups_Query::OPERATOR_GREATER:
                        $vars->set('ticket_due[from]', $child['value']);
                        break;
                    case Whups_Query::CRITERION_DUE . Whups_Query::OPERATOR_LESS:
                        $vars->set('ticket_due[to]', $child['value']);
                        break;
                    }
                }
            }
            return 'date';

        case Whups_Query::CRITERION_OWNERS:
        case Whups_Query::CRITERION_REQUESTER:
        case Whups_Query::CRITERION_ADDED_COMMENT:
            $criteria = false;
            if ($parent && $parent['type'] == Whups_Query::TYPE_OR) {
                $criteria = array();
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        !in_array($child['criterion'], array(Whups_Query::CRITERION_OWNERS, Whups_Query::CRITERION_REQUESTER, Whups_Query::CRITERION_ADDED_COMMENT))) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['criterion'];
                }
                if ($criteria) {
                    array_pop($path);
                    $vars->set('path', Whups_Query::pathToString($path));
                }
            }
            if (!$criteria) {
                $criteria = array($qobj['criterion']);
            }
            $vars->set('user', $qobj['value']);
            $vars->set('operator', $qobj['operator']);
            foreach ($criteria as $criterion) {
                switch ($criterion) {
                case Whups_Query::CRITERION_OWNERS:
                    $vars->set('owners', true);
                    break;
                case Whups_Query::CRITERION_REQUESTER:
                    $vars->set('requester', true);
                    break;
                case Whups_Query::CRITERION_ADDED_COMMENT:
                    $vars->set('comments', true);
                    break;
                }
            }
            return 'user';

        case Whups_Query::CRITERION_GROUPS:
            $vars->set('groups', $qobj['value']);
            return 'group';

        case Whups_Query::CRITERION_COMMENT:
        case Whups_Query::CRITERION_SUMMARY:
            $criteria = false;
            if ($parent && $parent['type'] == Whups_Query::TYPE_OR) {
                $criteria = array();
                $operator = $value = null;
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        ($child['criterion'] != Whups_Query::CRITERION_COMMENT &&
                         $child['criterion'] != Whups_Query::CRITERION_SUMMARY) ||
                        (isset($operator) && $operator != $child['operator']) ||
                        (isset($value) && $value != $child['value'])) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['criterion'];
                    $operator = $child['operator'];
                    $value = $child['value'];
                }
                if ($criteria) {
                    array_pop($path);
                    $vars->set('path', Whups_Query::pathToString($path));
                }
            }
            if (!$criteria) {
                $criteria = array($qobj['criterion']);
            }
            $vars->set('text', $value);
            $vars->set('operator', $operator);
            foreach ($criteria as $criterion) {
                if ($criterion == Whups_Query::CRITERION_COMMENT) {
                    $vars->set('comments', true);
                } elseif ($criterion == Whups_Query::CRITERION_SUMMARY) {
                    $vars->set('summary', true);
                }
            }
            return 'text';

        case Whups_Query::CRITERION_ATTRIBUTE:
            $attribs = array_keys($GLOBALS['whups_driver']->getAttributesForType());
            $criteria = false;
            if ($parent && $parent['type'] == Whups_Query::TYPE_OR) {
                $criteria = array();
                $operator = $value = null;
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != Whups_Query::TYPE_CRITERION ||
                        $child['criterion'] != Whups_Query::CRITERION_ATTRIBUTE ||
                        (isset($operator) && $operator != $child['operator']) ||
                        (isset($value) && $value != $child['value']) ||
                        !in_array($child['cvalue'], $attribs)) {
                        $criteria = false;
                        break;
                    }
                    $criteria[] = $child['cvalue'];
                    $operator = $child['operator'];
                    $value = $child['value'];
                }
                if ($criteria) {
                    array_pop($path);
                    $vars->set('path', Whups_Query::pathToString($path));
                }
            }
            if (!$criteria) {
                $criteria = array($qobj['cvalue']);
            }
            $vars->set('text', $value);
            $vars->set('operator', $operator);
            foreach ($criteria as $criterion) {
                $vars->set('a' . $criterion, true);
            }
            return 'attribs';
        }

        throw new Whups_Exception(_("This query element cannot be edited."));
    }

    public function deleteNode($pathstring)
    {
        $path = Whups_Query::stringToPath($pathstring);
        $qobj = &$this->query;

        if (!strlen($pathstring)) {
            // Deleting the root node isn't supported.
            $GLOBALS['notification']->push(_("Choose New Query instead of deleting the root node."), 'horde.warning');
            return false;
        } else {
            $count = count($path) - 1;
            for ($i = 0; $i < $count; $i++) {
                $qobj = &$qobj['children'][$path[$i]];
            }

            if (!empty($qobj['children'][$path[$count]]['value']) &&
                $this->_getParameterName($qobj['children'][$path[$count]]['value']) !== null) {
                unset($this->parameters[array_search($pn, $this->parameters)]);
            }

            array_splice($qobj['children'], $path[$count], 1);
        }
    }

    public function hoist($pathstring)
    {
        $path = Whups_Query::stringToPath($pathstring);
        $qobj = &$this->query;

        if (!strlen($pathstring)) {
            // Can't hoist the root node.
        } else {
            $count = count($path) - 1;

            for ($i = 0; $i < $count; $i++) {
                $qobj = &$qobj['children'][$path[$i]];
            }

            $cobj = &$qobj['children'][$path[$count]];

            // TODO: make sure we're hoisting a branch.
            array_splice($qobj['children'], $path[$count], 0, $cobj['children']);
            array_splice($cobj['children'], 0, count($cobj['children']));
        }
    }

    public function insertBranch($pathstring, $type)
    {
        $path = Whups_Query::stringToPath($pathstring);
        $qobj = &$this->query;

        $newbranch = array(
            'type'     => $type,
            'children' => array());

        $count = count($path);

        for ($i = 0; $i < $count; $i++) {
            $qobj = &$qobj['children'][$path[$i]];
        }

        if (!isset($qobj['children']) ||
            !is_array($qobj['children'])) {
            $qobj['children'] = array();
        }

        $qobj['children'][] = $newbranch;
        $path[] = count($qobj['children']) - 1;

        return Whups_Query::pathToString($path);
    }

    public function insertCriterion($pathstring, $criterion, $cvalue, $operator, $value)
    {
        $path = Whups_Query::stringToPath($pathstring);
        $qobj = &$this->query;

        $value = trim($value);
        if ($value[0] == '"') {
            // FIXME: The last character should be '"' as well.
            $value = substr($value, 1, -1);
        } else {
            $pn = $this->_getParameterName($value);
            if ($pn !== null) {
                $this->parameters[] = $pn;
            }
        }

        $newbranch = array(
            'type'      => Whups_Query::TYPE_CRITERION,
            'criterion' => $criterion,
            'cvalue'    => $cvalue,
            'operator'  => $operator,
            'value'     => $value);

        $count = count($path);
        for ($i = 0; $i < $count; $i++) {
            $qobj = &$qobj['children'][$path[$i]];
        }

        $qobj['children'][] = $newbranch;
    }

    /**
     * Top down traversal.
     */
    public function walk(&$obj, $method)
    {
        $path = array();
        $more = array();
        $this->_walk($this->query, $more, $path, $obj, $method);
    }

    /**
     * @access private
     */
    protected function _walk(&$node, &$more, &$path, &$obj, $method)
    {
        if ($node['type'] == Whups_Query::TYPE_CRITERION) {
            $obj->$method($more, $path, Whups_Query::TYPE_CRITERION, $node['criterion'],
                          $node['cvalue'], $node['operator'], $node['value']);
        } else {
            $obj->$method($more, $path, $node['type'], null, null, null, null);
        }

        if (isset($node['children'])) {
            $count = count($node['children']);

            for ($i = 0; $i < $count; $i++) {
                $path[] = $i;
                $more[] = ($i < $count - 1);
                $this->_walk($node['children'][$i], $more, $path, $obj, $method);
                array_pop($more);
                array_pop($path);
            }
        }
    }

    /**
     * Bottom up traversal.
     */
    public function reduce($method, &$vars)
    {
        return $this->_reduce($this->query, $method, $vars);
    }

    /**
     * @access private
     */
    protected function _reduce(&$node, $method, &$vars)
    {
        $args = array();

        if (isset($node['children'])) {
            $count = count($node['children']);

            for ($i = 0; $i < $count; $i++) {
                $result = $this->_reduce($node['children'][$i], $method, $vars);
                $args[] = $result;
            }
        }

        if ($node['type'] == Whups_Query::TYPE_CRITERION) {
            $value = $node['value'];

            $pn = $this->_getParameterName($value);
            if ($pn !== null) {
                $value = $vars->get($pn);
            }

            return call_user_func(
                $method, $args, Whups_Query::TYPE_CRITERION, $node['criterion'],
                $node['cvalue'], $node['operator'], $value);
        }

        return call_user_func($method, $args, $node['type'], null, null, null, null);
    }

    /**
     * @access private
     */
    protected function _getParameterName($value)
    {
        if (strcmp(substr($value, 0, 2), '${'))
            return null;

        $pn = substr($value, 2, -1);
        if (!is_string($pn))
            $pn = null;

        return $pn;
    }

}
