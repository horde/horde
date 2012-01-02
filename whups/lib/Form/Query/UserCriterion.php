<?php
/**
 * Forms for editing queries.
 *
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
 * @package Whups
 */
class Whups_Form_Query_UserCriterion extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::Horde_Form(
            $vars,
            $vars->get('edit') ? _("Edit User Criterion") : _("Add User Criterion"),
            'Whups_Form_Query_UserCriterion');

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("User ID"), 'user', 'text', true);
        $this->addVariable
            (_("Match Operator"), 'operator', 'enum', true, false, null,
             array(Whups_Query::textOperators()));
        $this->addVariable(_("Search Owners"), 'owners', 'boolean', false);
        $this->addVariable(_("Search Requester"), 'requester', 'boolean', false);
        $this->addVariable(_("Search Comments"), 'comments', 'boolean', false);
    }

    public function execute(&$vars)
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
            $path = $GLOBALS['whups_query']->insertBranch($path, Whups_Query::TYPE_OR);
        }

        if ($owners) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_OWNERS, null, $operator, $user);
        }

        if ($requester) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_REQUESTER, null, $operator, $user);
        }

        if ($comments) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_ADDED_COMMENT, null, $operator, $user);
        }

        $this->unsetVars($vars);
    }

}