<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

/** Horde_Form_Action */
require_once 'Horde/Form/Action.php';

/** Mode types. */
define('QUERY_TYPE_AND',       1);
define('QUERY_TYPE_OR',        2);
define('QUERY_TYPE_NOT',       3);
define('QUERY_TYPE_CRITERION', 4);

/** Criterion types. */
define('CRITERION_ID',             1);
define('CRITERION_QUEUE',          2);
define('CRITERION_TYPE',           3);
define('CRITERION_STATE',          4);
define('CRITERION_PRIORITY',       5);
define('CRITERION_OWNERS',         7);
define('CRITERION_REQUESTER',      8);
define('CRITERION_GROUPS',         9);
define('CRITERION_ADDED_COMMENT', 11);
define('CRITERION_COMMENT',       12);
define('CRITERION_SUMMARY',       13);
define('CRITERION_ATTRIBUTE',     14);
define('CRITERION_VERSION',       15);
define('CRITERION_TIMESTAMP',     16);
define('CRITERION_UPDATED',       17);
define('CRITERION_RESOLVED',      18);
define('CRITERION_ASSIGNED',      19);
define('CRITERION_DUE',           20);

/** Operators for integer fields. */
define('OPERATOR_GREATER', 1);
define('OPERATOR_LESS',    2);
define('OPERATOR_EQUAL',   3);

/** Operators for text fields. */
define('OPERATOR_CI_SUBSTRING',  4);
define('OPERATOR_CS_SUBSTRING',  5);
define('OPERATOR_WORD',          6);
define('OPERATOR_PATTERN',       7);

/**
 * array(
 *     'type'      => QUERY_TYPE_...
 *     'children'  => array(...) unless type == QUERY_TYPE_CRITERION
 *     'criterion' => CRITERION_... if  type == QUERY_TYPE_CRITERION
 *     'operator'  => OPERATOR_...  if  type == QUERY_TYPE_CRITERION
 *     'value'     => other argument to operator of criterion
 */

/**
 * @package Whups
 */
class Whups_Query {

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
    var $query = array('type' => QUERY_TYPE_AND,
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
            OPERATOR_EQUAL        => _("Exact Match"),
            OPERATOR_CI_SUBSTRING => _("Case Insensitive Substring"),
            // OPERATOR_CS_SUBSTRING => _("Case Sensitive Substring"),
            OPERATOR_WORD         => _("Match Word"),
            OPERATOR_PATTERN      => _("Match Pattern"));
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
     * Tab operations for this query.
     */
    function getTabs($vars)
    {
        // Create a few variables that are reused.
        $queryurl = Horde::applicationUrl('query/index.php');
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
        $tabs->addTab(_("E_xecute Query"), Horde::applicationUrl('query/run.php'), 'run');
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

        if ($qobj['type'] != QUERY_TYPE_CRITERION) {
            // Search for any criteria that have been combined automatically
            // with an AND or OR.
            switch ($qobj['type']) {
            case QUERY_TYPE_OR:
                // Search for multiple ids.
                $criteria = array();
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        $child['criterion'] != CRITERION_ID) {
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
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        ($child['criterion'] != CRITERION_OWNERS &&
                         $child['criterion'] != CRITERION_REQUESTER &&
                         $child['criterion'] != CRITERION_ADDED_COMMENT) ||
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
                        case CRITERION_OWNERS:
                            $vars->set('owners', true);
                            break;
                        case CRITERION_REQUESTER:
                            $vars->set('requester', true);
                            break;
                        case CRITERION_ADDED_COMMENT:
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
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        ($child['criterion'] != CRITERION_COMMENT &&
                         $child['criterion'] != CRITERION_SUMMARY) ||
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
                        if ($criterion == CRITERION_COMMENT) {
                            $vars->set('comments', true);
                        } elseif ($criterion == CRITERION_SUMMARY) {
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
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        $child['criterion'] != CRITERION_ATTRIBUTE ||
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

            case QUERY_TYPE_AND:
                // Search for date criteria.
                $criteria = false;
                foreach ($qobj['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        ($child['criterion'] != CRITERION_TIMESTAMP &&
                         $child['criterion'] != CRITERION_UPDATED &&
                         $child['criterion'] != CRITERION_RESOLVED &&
                         $child['criterion'] != CRITERION_ASSIGNED &&
                         $child['criterion'] != CRITERION_DUE)) {
                        $criteria = false;
                        break;
                    }
                    $criteria = true;
                }
                if ($criteria) {
                    foreach ($qobj['children'] as $child) {
                        switch ($child['criterion'] . $child['operator']) {
                        case CRITERION_TIMESTAMP . OPERATOR_GREATER:
                            $vars->set('ticket_timestamp[from]', $child['value']);
                            break;
                        case CRITERION_TIMESTAMP . OPERATOR_LESS:
                            $vars->set('ticket_timestamp[to]', $child['value']);
                            break;
                        case CRITERION_UPDATED . OPERATOR_GREATER:
                            $vars->set('date_updated[from]', $child['value']);
                            break;
                        case CRITERION_UPDATED . OPERATOR_LESS:
                            $vars->set('date_updated[to]', $child['value']);
                            break;
                        case CRITERION_RESOLVED . OPERATOR_GREATER:
                            $vars->set('date_resolved[from]', $child['value']);
                            break;
                        case CRITERION_RESOLVED . OPERATOR_LESS:
                            $vars->set('date_resolved[to]', $child['value']);
                            break;
                        case CRITERION_ASSIGNED . OPERATOR_GREATER:
                            $vars->set('date_assigned[from]', $child['value']);
                            break;
                        case CRITERION_ASSIGNED . OPERATOR_LESS:
                            $vars->set('date_assigned[to]', $child['value']);
                            break;
                        case CRITERION_DUE . OPERATOR_GREATER:
                            $vars->set('ticket_due[from]', $child['value']);
                            break;
                        case CRITERION_DUE . OPERATOR_LESS:
                            $vars->set('ticket_due[to]', $child['value']);
                            break;
                        }
                    }
                    return 'date';
                }

                // Search for version criterion.
                if (count($qobj['children']) == 2 &&
                    $qobj['children'][0]['type'] == QUERY_TYPE_CRITERION &&
                    $qobj['children'][0]['criterion'] == CRITERION_QUEUE &&
                    $qobj['children'][1]['type'] == QUERY_TYPE_CRITERION &&
                    $qobj['children'][1]['criterion'] == CRITERION_VERSION) {
                    $vars->set('queue', $qobj['children'][0]['value']);
                    $vars->set('version', $qobj['children'][1]['value']);
                    return 'props';
                }
                break;
            }
            return PEAR::raiseError(_("This query element cannot be edited."));
        }

        switch ($qobj['criterion']) {
        case CRITERION_ID:
            $multiple = false;
            if ($parent && $parent['type'] == QUERY_TYPE_OR) {
                $multiple = array();
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        $child['criterion'] != CRITERION_ID) {
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

        case CRITERION_QUEUE:
            if ($parent && $parent['type'] == QUERY_TYPE_AND &&
                count($parent['children']) == 2 &&
                $parent['children'][1]['type'] == QUERY_TYPE_CRITERION &&
                $parent['children'][1]['criterion'] == CRITERION_VERSION) {
                array_pop($path);
                $vars->set('path', Whups_Query::pathToString($path));
                $vars->set('version', $parent['children'][1]['value']);
            }
            $vars->set('queue', $qobj['value']);
            return 'props';

        case CRITERION_VERSION:
            array_pop($path);
            $vars->set('path', Whups_Query::pathToString($path));
            $vars->set('queue', $parent['children'][0]['value']);
            $vars->set('version', $qobj['value']);
            return 'props';

        case CRITERION_TYPE:
            $vars->set('ttype', $qobj['value']);
            return 'props';

        case CRITERION_STATE:
            $vars->set('state', $qobj['value']);
            return 'props';

        case CRITERION_PRIORITY:
            $vars->set('priority', $qobj['value']);
            return 'props';

        case CRITERION_TIMESTAMP:
        case CRITERION_UPDATED:
        case CRITERION_RESOLVED:
        case CRITERION_ASSIGNED:
        case CRITERION_DUE:
            $criteria = false;
            if ($parent && $parent['type'] == QUERY_TYPE_AND) {
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        ($child['criterion'] != CRITERION_TIMESTAMP &&
                         $child['criterion'] != CRITERION_UPDATED &&
                         $child['criterion'] != CRITERION_RESOLVED &&
                         $child['criterion'] != CRITERION_ASSIGNED &&
                         $child['criterion'] != CRITERION_DUE)) {
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
                    case CRITERION_TIMESTAMP . OPERATOR_GREATER:
                        $vars->set('ticket_timestamp[from]', $child['value']);
                        break;
                    case CRITERION_TIMESTAMP . OPERATOR_LESS:
                        $vars->set('ticket_timestamp[to]', $child['value']);
                        break;
                    case CRITERION_UPDATED . OPERATOR_GREATER:
                        $vars->set('date_updated[from]', $child['value']);
                        break;
                    case CRITERION_UPDATED . OPERATOR_LESS:
                        $vars->set('date_updated[to]', $child['value']);
                        break;
                    case CRITERION_RESOLVED . OPERATOR_GREATER:
                        $vars->set('date_resolved[from]', $child['value']);
                        break;
                    case CRITERION_RESOLVED . OPERATOR_LESS:
                        $vars->set('date_resolved[to]', $child['value']);
                        break;
                    case CRITERION_ASSIGNED . OPERATOR_GREATER:
                        $vars->set('date_assigned[from]', $child['value']);
                        break;
                    case CRITERION_ASSIGNED . OPERATOR_LESS:
                        $vars->set('date_assigned[to]', $child['value']);
                        break;
                    case CRITERION_DUE . OPERATOR_GREATER:
                        $vars->set('ticket_due[from]', $child['value']);
                        break;
                    case CRITERION_DUE . OPERATOR_LESS:
                        $vars->set('ticket_due[to]', $child['value']);
                        break;
                    }
                }
            }
            return 'date';

        case CRITERION_OWNERS:
        case CRITERION_REQUESTER:
        case CRITERION_ADDED_COMMENT:
            $criteria = false;
            if ($parent && $parent['type'] == QUERY_TYPE_OR) {
                $criteria = array();
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        !in_array($child['criterion'], array(CRITERION_OWNERS, CRITERION_REQUESTER, CRITERION_ADDED_COMMENT))) {
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
                case CRITERION_OWNERS:
                    $vars->set('owners', true);
                    break;
                case CRITERION_REQUESTER:
                    $vars->set('requester', true);
                    break;
                case CRITERION_ADDED_COMMENT:
                    $vars->set('comments', true);
                    break;
                }
            }
            return 'user';

        case CRITERION_GROUPS:
            $vars->set('groups', $qobj['value']);
            return 'group';

        case CRITERION_COMMENT:
        case CRITERION_SUMMARY:
            $criteria = false;
            if ($parent && $parent['type'] == QUERY_TYPE_OR) {
                $criteria = array();
                $operator = $value = null;
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        ($child['criterion'] != CRITERION_COMMENT &&
                         $child['criterion'] != CRITERION_SUMMARY) ||
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
                if ($criterion == CRITERION_COMMENT) {
                    $vars->set('comments', true);
                } elseif ($criterion == CRITERION_SUMMARY) {
                    $vars->set('summary', true);
                }
            }
            return 'text';

        case CRITERION_ATTRIBUTE:
            $attribs = array_keys($GLOBALS['whups_driver']->getAttributesForType());
            $criteria = false;
            if ($parent && $parent['type'] == QUERY_TYPE_OR) {
                $criteria = array();
                $operator = $value = null;
                foreach ($parent['children'] as $child) {
                    if ($child['type'] != QUERY_TYPE_CRITERION ||
                        $child['criterion'] != CRITERION_ATTRIBUTE ||
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
            'type'      => QUERY_TYPE_CRITERION,
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
        if ($node['type'] == QUERY_TYPE_CRITERION) {
            $obj->$method($more, $path, QUERY_TYPE_CRITERION, $node['criterion'],
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

        if ($node['type'] == QUERY_TYPE_CRITERION) {
            $value = $node['value'];

            $pn = $this->_getParameterName($value);
            if ($pn !== null) {
                $value = $vars->get($pn);
            }

            $result = $obj->$method($args, QUERY_TYPE_CRITERION, $node['criterion'],
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
        $this->_shareManager = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();
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
        } catch (Horde_Share_Exception $e) {
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
            $shares = $this->_shareManager->listShares($GLOBALS['registry']->getAuth(), Horde_Perms::READ,
                                                       array('slug' => $slug));
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
        } catch (Horde_Share_Exception $e) {
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
            } catch (Horde_Share_Exception $e) {
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
            $share = $this->_shareManager->newShare(strval(new Horde_Support_Uuid());
            $share->set('name', $query->name);
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
        } catch (Horde_Share_Exception $e) {
            throw new Whups_Exception($e);
        }
        $result = $GLOBALS['whups_driver']->deleteQuery($query->id);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return true;
    }

}
