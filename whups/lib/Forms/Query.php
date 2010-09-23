<?php
/**
 * Forms for editing queries.
 *
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
class InsertBranchForm extends Horde_Form {

    function InsertBranchForm(&$vars)
    {
        parent::Horde_Form($vars, _("Insert Branch"));

        $branchtypes = array(
            QUERY_TYPE_AND => _("And"),
            QUERY_TYPE_OR  => _("Or"),
            QUERY_TYPE_NOT => _("Not"));

        $this->addHidden(null, 'path', 'text', false, true);
        $this->addVariable(_("Branch Type"), 'type', 'enum', true, false, null, array($branchtypes));
    }

}

/**
 * @package Whups
 */
class UserCriterionForm extends Horde_Form {

    function UserCriterionForm(&$vars)
    {
        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit User Criterion") : _("Add User Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("User ID"), 'user', 'text', true);
        $this->addVariable(_("Match Operator"), 'operator', 'enum', true, false, null, array(Whups_Query::textOperators()));
        $this->addVariable(_("Search Owners"), 'owners', 'boolean', false);
        $this->addVariable(_("Search Requester"), 'requester', 'boolean', false);
        $this->addVariable(_("Search Comments"), 'comments', 'boolean', false);
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');
        $user = $vars->get('user');
        $operator = $vars->get('operator');
        $owners = $vars->get('owners');
        $requester = $vars->get('requester');
        $comments = $vars->get('comments');

        // If we're adding more than one criterion, put them all under an OR
        // node (which should be what is wanted in the general case).
        if ((bool)$owners + (bool)$requester + (bool)$comments > 1) {
            $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_OR);
        }

        if ($owners) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_OWNERS, null, $operator, $user);
        }

        if ($requester) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_REQUESTER, null, $operator, $user);
        }

        if ($comments) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_ADDED_COMMENT, null, $operator, $user);
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class GroupCriterionForm extends Horde_Form {

    function GroupCriterionForm(&$vars)
    {
        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit Group Criterion") : _("Add Group Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $grouplist = $groups->listGroups();
        } catch (Horde_Group_Exception $e) {
            $grouplist = array();
        }

        if (count($grouplist)) {
            $type_params = array(_("Could not find any groups."));
            $this->addVariable(_("Groups"), 'groups', 'invalid', false, false, null, $type_params);
        } else {
            $this->addVariable(_("Groups"), 'groups', 'enum', true, false, null, array($grouplist));
        }
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');
        $groups = $vars->get('groups');

        if ($groups) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_GROUPS, null, OPERATOR_EQUAL, $groups);
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class TextCriterionForm extends Horde_Form {

    function TextCriterionForm(&$vars)
    {
        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit Text Criterion") : _("Add Text Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("Text"), 'text', 'text', true);
        $this->addVariable(_("Match Operator"), 'operator', 'enum', true, false, null, array(Whups_Query::textOperators()));
        $this->addVariable(_("Search Summary"), 'summary', 'boolean', false);
        $this->addVariable(_("Search Comments"), 'comments', 'boolean', false);
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');
        $text = $vars->get('text');
        $operator = $vars->get('operator');
        $summary = $vars->get('summary');
        $comments = $vars->get('comments');

        if ($summary && $comments) {
            $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_OR);
        }

        if ($summary) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_SUMMARY, null, $operator, $text);
        }

        if ($comments) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_COMMENT, null, $operator, $text);
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class PropertyCriterionForm extends Horde_Form {

    function PropertyCriterionForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit Property Criterion") : _("Add Property Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("Id"), 'id', 'intlist', false);

        /* Types. */
        $this->addVariable(_("Type"), 'ttype', 'enum', false, false, null,
                           array($whups_driver->getAllTypes(), _("Any")));


        /* Queues. */
        $queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::READ);
        if (count($queues)) {
            $v = &$this->addVariable(_("Queue"), 'queue', 'enum', false, false,
                                     null, array($queues, _("Any")));
            $v->setAction(Horde_Form_Action::factory('reload'));
            if ($vars->get('queue')) {
                $this->addVariable(_("Version"), 'version', 'enum', false,
                                   false, null,
                                   array($whups_driver->getVersions($vars->get('queue')), _("Any")));
            }
        }

        /* States. */
        $states = $whups_driver->getStates();
        $this->addVariable(_("State"), 'state', 'enum', false, false, null,
                           array($states, _("Any")));

        /* Priorities. */
        $priorities = $whups_driver->getPriorities();
        $this->addVariable(_("Priority"), 'priority', 'enum', false, false,
                           null, array($priorities, _("Any")));
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');

        $id = $vars->get('id');
        if (strlen(trim($id))) {
            $newpath = $path;
            $ids = split("[\\t\\n ,]+", $id);

            if (count($ids) > 1) {
                $newpath = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_OR);
            }

            foreach ($ids as $id) {
                $GLOBALS['whups_query']->insertCriterion($newpath, CRITERION_ID, null, OPERATOR_EQUAL, $id);
            }
        }

        $queue = $vars->get('queue');
        if ($queue) {
            $version = $vars->get('version');
            if ($version) {
                $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_AND);
            }
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_QUEUE, null, OPERATOR_EQUAL, $queue);
            if ($version) {
                $GLOBALS['whups_query']->insertCriterion($path, CRITERION_VERSION, null, OPERATOR_EQUAL, $version);
            }
        }

        $type = $vars->get('ttype');
        if ($type) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_TYPE, null, OPERATOR_EQUAL, $type);
        }

        $state = $vars->get('state');
        if ($state) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_STATE, null, OPERATOR_EQUAL, $state);
        }

        $priority = $vars->get('priority');
        if ($priority) {
            $GLOBALS['whups_query']->insertCriterion($path, CRITERION_PRIORITY, null, OPERATOR_EQUAL, $priority);
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class AttributeCriterionForm extends Horde_Form {

    /**
     * List of all available attributes.
     */
    var $attribs = array();

    function AttributeCriterionForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit Attribute Criterion") : _("Add Attribute Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);

        $this->attribs = $whups_driver->getAttributesForType();
        if (is_a($this->attribs, 'PEAR_Error')) {
            $this->addVariable(_("Search Attribute"), 'attribute', 'invalid', true, false, null, array($this->attribs->getMessage()));
        } elseif ($this->attribs) {
            $this->addVariable(_("Match"), 'text', 'text', true);
            $this->addVariable(_("Match Operator"), 'operator', 'enum', true, false, null, array(Whups_Query::textOperators()));

            foreach ($this->attribs as $id => $attribute) {
                $this->addVariable(sprintf(_("Search %s Attribute"), $attribute['human_name']), "a$id", 'boolean', false);
            }
        } else {
            $this->addVariable(_("Search Attribute"), 'attribute', 'invalid', true, false, null, array(_("There are no attributes defined.")));
        }
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');
        $text = $vars->get('text');
        $operator = $vars->get('operator');

        $count = 0;

        $keys = array_keys($this->attribs);
        foreach ($keys as $id) {
            $count += $vars->exists("a$id") ? 1 : 0;
        }

        if ($count > 1) {
            $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_OR);
        }

        foreach ($keys as $id) {
            if ($vars->get("a$id")) {
                $GLOBALS['whups_query']->insertCriterion($path, CRITERION_ATTRIBUTE, $id, $operator, $text);
            }
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class DateCriterionForm extends Horde_Form {

    function DateCriterionForm(&$vars)
    {
        parent::Horde_Form($vars, $vars->get('edit') ? _("Edit Date Criterion") : _("Add Date Criterion"));

        $this->addHidden('', 'edit', 'boolean', false);

        $this->addVariable(_("Created from"), 'ticket_timestamp[from]', 'monthdayyear', false, false, null, array(date('Y') - 10));
        $this->addVariable(_("Created to"), 'ticket_timestamp[to]', 'monthdayyear', false, false, null, array(date('Y') - 10));

        $this->addVariable(_("Updated from"), 'date_updated[from]', 'monthdayyear', false, false, null, array(date('Y') - 10));
        $this->addVariable(_("Updated to"), 'date_updated[to]', 'monthdayyear', false, false, null, array(date('Y') - 10));

        $this->addVariable(_("Resolved from"), 'date_resolved[from]', 'monthdayyear', false, false, null, array(date('Y') - 10));
        $this->addVariable(_("Resolved to"), 'date_resolved[to]', 'monthdayyear', false, false, null, array(date('Y') - 10));

        $this->addVariable(_("Assigned from"), 'date_assigned[from]', 'monthdayyear', false, false, null, array(date('Y') - 10));
        $this->addVariable(_("Assigned to"), 'date_assigned[to]', 'monthdayyear', false, false, null, array(date('Y') - 10));

        $this->addVariable(_("Due from"), 'ticket_due[from]', 'monthdayyear', false, false, null, array(date('Y') - 10));
        $this->addVariable(_("Due to"), 'ticket_due[to]', 'monthdayyear', false, false, null, array(date('Y') - 10));
    }

    function execute(&$vars)
    {
        $path = $vars->get('path');
        $parent = false;

        $keys = array(CRITERION_TIMESTAMP => 'ticket_timestamp',
                      CRITERION_UPDATED => 'date_updated',
                      CRITERION_RESOLVED => 'date_resolved',
                      CRITERION_ASSIGNED => 'date_assigned',
                      CRITERION_DUE => 'ticket_due');

        foreach ($keys as $key_id => $key_name) {
            $date = $vars->get($key_name . '[from]');
            if (!empty($date['month'])) {
                if (!$parent) {
                    $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_AND);
                    $parent = true;
                }
                $date = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                $GLOBALS['whups_query']->insertCriterion($path, $key_id, null, OPERATOR_GREATER, $date);
            }
            $date = $vars->get($key_name . '[to]');
            if (!empty($date['month'])) {
                if (!$parent) {
                    $path = $GLOBALS['whups_query']->insertBranch($path, QUERY_TYPE_AND);
                    $parent = true;
                }
                $date = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                $GLOBALS['whups_query']->insertCriterion($path, $key_id, null, OPERATOR_LESS, $date);
            }
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class ChooseQueryNameForSaveForm extends Horde_Form {

    function ChooseQueryNameForSaveForm(&$vars)
    {
        parent::Horde_Form($vars, _("Save Query"));
        $this->setButtons(_("Save"));

        $v = &$this->addVariable(_("Query Name"), 'name', 'text', true);
        $v->setDefault($GLOBALS['whups_query']->name);
        $v = &$this->addVariable(_("Query Slug"), 'slug', 'text', false);
        $v->setDefault($GLOBALS['whups_query']->slug);
    }

    function execute(&$vars)
    {
        $GLOBALS['whups_query']->name = $vars->get('name');
        $GLOBALS['whups_query']->slug = $vars->get('slug');
        $result = $GLOBALS['whups_query']->save();

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class ChooseQueryNameForLoadForm extends Horde_Form {

    function ChooseQueryNameForLoadForm(&$vars)
    {
        parent::Horde_Form($vars, _("Load Query"));

        $qManager = new Whups_QueryManager();
        $qParams = $qManager->listQueries($GLOBALS['registry']->getAuth());
        if (count($qParams)) {
            $qType = 'enum';
        } else {
            $qType = 'invalid';
            $qParams = _("You have no saved queries.");
        }

        $this->addVariable(_("Name"), 'name', $qType, true, false, null, array($qParams));
    }

    function execute(&$vars)
    {
        $qManager = new Whups_QueryManager();
        $query = $qManager->getQuery($vars->get('name'));
        if (is_a($query, 'PEAR_Error')) {
            $GLOBALS['notification']->push(sprintf(_("The query couldn't be loaded:"), $query->getMessage()), 'horde.error');
        } else {
            $GLOBALS['whups_query'] = $query;
        }

        $this->unsetVars($vars);
    }

}

/**
 * @package Whups
 */
class DeleteQueryForm extends Horde_Form {

    function DeleteQueryForm(&$vars)
    {
        parent::Horde_Form($vars, _("Delete Query?"));

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(_("Really delete this query? This operation is not undoable."), 'yesno', 'enum', true, false, null, $yesno);
    }

    function execute(&$vars)
    {
        global $notification;

        if ($vars->get('yesno')) {
            if (!$GLOBALS['whups_query']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $notifications->push(sprintf(_("Permission denied.")), 'horde.error');
            } else {
                $result = $GLOBALS['whups_query']->delete();
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(sprintf(_("The query \"%s\" couldn't be deleted: %s"), $GLOBALS['whups_query']->name, $result->getMessage()), 'horde.error');
                } else {
                    $notification->push(sprintf(_("The query \"%s\" has been deleted."), $GLOBALS['whups_query']->name), 'horde.success');
                    $qManager = new Whups_QueryManager();
                    unset($GLOBALS['whups_query']);
                    $GLOBALS['whups_query'] = $qManager->newQuery();
                }
            }
        }

        $this->unsetVars($vars);
    }

}
