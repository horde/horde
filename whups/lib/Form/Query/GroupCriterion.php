<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * Form to add or edit group criteria
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Query_GroupCriterion extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct(
            $vars,
            $vars->get('edit') ? _("Edit Group Criterion") : _("Add Group Criterion"),
            'Whups_Form_Query_GroupCriterion');

        $this->addHidden('', 'edit', 'boolean', false);

        try {
            $grouplist = $GLOBALS['injector']
                ->getInstance('Horde_Group')
                ->listAll();
        } catch (Horde_Group_Exception $e) {
            $grouplist = array();
        }

        if (count($grouplist)) {
            $type_params = array(_("Could not find any groups."));
            $this->addVariable(
                _("Groups"), 'groups', 'invalid', false, false, null, $type_params);
        } else {
            $this->addVariable(
                _("Groups"), 'groups', 'enum', true, false, null, array($grouplist));
        }
    }

    public function execute(&$vars)
    {
        $path = $vars->get('path');
        $groups = $vars->get('groups');

        if ($groups) {
            $GLOBALS['whups_query']->insertCriterion(
                $path,
                Whups_Query::CRITERION_GROUPS,
                null,
                Whups_Query::OPERATOR_EQUAL,
                $groups);
        }

        $this->unsetVars($vars);
    }
}