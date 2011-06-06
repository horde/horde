<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
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
     * @var Whups_QueryManager
     */
    var $_qManager;

    /**
     * Query id.
     *
     * @var integer
     */
    var $id;

    /**
     * The full name of the query.
     *
     * @var string
     */
    var $name;

    /**
     * The query slug (short name).
     *
     * @var string
     */
    var $slug;

    /**
     * @var array
     */
    var $query = array('type' => Whups_Query::TYPE_AND,
                       'children' => array());

    /**
     * @var array
     */
    var $parameters = array();

    /**
     * Constructor
     */
    function Whups_Query(&$qManager, $qDetails = array())
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
    function pathToString(&$path)
    {
        return implode(',', $path);
    }

    /**
     * @static
     */
    function stringToPath($pathstring)
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
    function textOperators()
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
    function hasPermission($userid, $permission, $creator = null)
    {
        return $this->_qManager->hasPermission($this->id, $userid, $permission, $creator);
    }

    /**
     * Saves any changes to this object to the backend
     * permanently. New objects are added instead.
     *
     * @return boolean|PEAR_Error  PEAR_Error on failure.
     */
    function save()
    {
        return $this->_qManager->save($this);
    }

    /**
     * Delete this object from the backend permanently.
     *
     * @return boolean|PEAR_Error  PEAR_Error on failure.
     */
    function delete()
    {
        return $this->_qManager->delete($this);
    }

    /**
     * Returns a <link> tag for this query's feed.
     *
     * @return string  A full <link> tag.
     */
    function feedLink()
    {
        return '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars($this->name) . '" href="' . Whups::urlFor('query_rss', empty($this->slug) ? array('id' => $this->id) : array('slug' => $this->slug), true, -1) . '" />';
    }

    /**
     * Tab operations for this query.
     */
    function getTabs($vars)
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
            Horde::addScriptFile('popup.js', 'horde', true);

            $permsurl = $GLOBALS['registry']->get('webroot', 'horde') . '/services/shares/edit.php';
            $permsurl = Horde_Util::addParameter($permsurl, array('app' => 'whups',
                                                            'cid' => $this->id));
            $tabs->addTab(_("Edit _Permissions"), $permsurl, array('tabname' => 'perms',
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
     * Operations.
     */

    function pathToForm(&$vars)
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
            return PEAR::raiseError(_("This query element cannot be edited."));
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

        return PEAR::raiseError(_("This query element cannot be edited."));
    }

    function deleteNode($pathstring)
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

    function hoist($pathstring)
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

    function insertBranch($pathstring, $type)
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

    function insertCriterion($pathstring, $criterion, $cvalue, $operator, $value)
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
    function walk(&$obj, $method)
    {
        $path = array();
        $more = array();
        $this->_walk($this->query, $more, $path, $obj, $method);
    }

    /**
     * @access private
     */
    function _walk(&$node, &$more, &$path, &$obj, $method)
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
    function reduce(&$obj, $method, &$vars)
    {
        return $this->_reduce($this->query, $obj, $method, $vars);
    }

    /**
     * @access private
     */
    function _reduce(&$node, &$obj, $method, &$vars)
    {
        $args = array();

        if (isset($node['children'])) {
            $count = count($node['children']);

            for ($i = 0; $i < $count; $i++) {
                $result = $this->_reduce($node['children'][$i], $obj, $method, $vars);
                $args[] = $result;
            }
        }

        if ($node['type'] == Whups_Query::TYPE_CRITERION) {
            $value = $node['value'];

            $pn = $this->_getParameterName($value);
            if ($pn !== null) {
                $value = $vars->get($pn);
            }

            $result = $obj->$method($args, Whups_Query::TYPE_CRITERION, $node['criterion'],
                                    $node['cvalue'], $node['operator'], $value);
        } else {
            $result = $obj->$method($args, $node['type'], null, null, null, null);
        }

        return $result;
    }

    /**
     * @access private
     */
    function _getParameterName($value)
    {
        if (strcmp(substr($value, 0, 2), '${'))
            return null;

        $pn = substr($value, 2, -1);
        if (!is_string($pn))
            $pn = null;

        return $pn;
    }

}

/**
 * @package Whups
 */
class Whups_QueryManager {

    /**
     * Horde_Share instance for managing shares.
     *
     * @var Horde_Share
     */
    var $_shareManager;

    /**
     * Constructor.
     */
    function Whups_QueryManager()
    {
        $this->_shareManager = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
    }

    /**
     * Returns a specific query identified by its id.
     *
     * @param integer $queryId  A query id.
     *
     * @return Whups_Query  The matching query or null if not found.
     * @throws Whups_Exception
     */
    function getQuery($queryId)
    {
        try {
            $share = $this->_shareManager->getShareById($queryId);
        } catch (Horde_Exception_NotFound $e) {
            throw new Whups_Exception($e);
        }
        return $this->_getQuery($share);
    }

    /**
     * Returns a specific query identified by its slug name.
     *
     * @param string $slug  A query slug.
     *
     * @return Whups_Query  The matching query or null if not found.
     * @throws Whups_Exception
     */
    function getQueryBySlug($slug)
    {
        try {
            $shares = $this->_shareManager->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => Horde_Perms::READ,
                      'attributes' => array('slug' => $slug)));
        } catch (Horde_Share_Exception $e) {
            throw new Whups_Exception($e);
        }
        if (!count($shares)) {
            return;
        }

        return $this->_getQuery(reset($shares));
    }

    /**
     * Builds a query object from a share object.
     *
     * @param Horde_Share_Object $share  A share object representing a query.
     *
     * @return Whups_Query  The query object built from the share.
     */
    function _getQuery($share)
    {
        $queryDetails = $GLOBALS['whups_driver']->getQuery($share->getId());
        if ($queryDetails instanceof PEAR_Error) {
            return $queryDetails;
        }

        $queryDetails['query_id'] = $share->getId();
        $queryDetails['query_name'] = $share->get('name');
        $queryDetails['query_slug'] = $share->get('slug');

        return new Whups_Query($this, $queryDetails);
    }

    /**
     * Checks to see if a user has a given permission to $queryId.
     *
     * @param integer $queryId     The query to check.
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    function hasPermission($queryId, $userid, $permission, $creator = null)
    {
        try {
            $share = $this->_shareManager->getShareById($queryId);
        } catch (Horde_Exception_NotFound $e) {
            // If the share doesn't exist yet, then it has open perms.
            return true;
        }
        return $share->hasPermission($userid, $permission, $creator);
    }

    /**
     * List queries.
     */
    function listQueries($user, $return_slugs = false)
    {
        try {
            $shares = $this->_shareManager->listShares($user);
        } catch (Horde_Share_Exception $e) {
            throw new Whups_Exception($e);
        }

        $queries = array();
        foreach ($shares as $share) {
            $queries[$share->getId()] = $return_slugs
                ? array('name' => $share->get('name'),
                        'slug' => $share->get('slug'))
                : $share->get('name');
        }

        return $queries;
    }

    /**
     */
    function newQuery()
    {
        return new Whups_Query($this);
    }

    /**
     * @param Whups_Query $query The query to save.
     * @throws Whups_Exception
     */
    function save($query)
    {
        if ($query->id) {
            // Query already exists; get its share and update the name
            // if necessary.
            try {
                $share = $this->_shareManager->getShareById($query->id);
            } catch (Horde_Exception_NotFound $e) {
                // Share has an id but doesn't exist; just throw an
                // error.
                throw new Whups_Exception($e);
            }
            if ($share->get('name') != $query->name ||
                $share->get('slug') != $query->slug) {
                $share->set('name', $query->name);
                $share->set('slug', $query->slug);
                $share->save();
            }
        } else {
            // Create a new share for the query.
            $share = $this->_shareManager->newShare($GLOBALS['registry']->getAuth(), (string)new Horde_Support_Uuid(), $query->name);
            $share->set('slug', $query->slug);
            try {
                $this->_shareManager->addShare($share);
            } catch (Horde_Share_Exception $e) {
                throw new Whups_Exception($e);
            }
            $query->id = $share->getId();
        }

        // Update the queries table.
        $GLOBALS['whups_driver']->saveQuery($query);
    }

    /**
     * @param Whups_Query $query The query to delete.
     */
    function delete($query)
    {
        if (!$query->id) {
            // Queries that aren't saved yet shouldn't be able to be deleted.
            return;
        }

        try {
            $share = $this->_shareManager->getShareById($query->id);
            $this->_shareManager->removeShare($share);
        } catch (Exception $e) {
            throw new Whups_Exception($e);
        }
        $result = $GLOBALS['whups_driver']->deleteQuery($query->id);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return true;
    }

}
