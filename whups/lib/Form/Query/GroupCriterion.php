<?php
/**
 * @package Whups
 */
class Whups_Form_Query_GroupCriterion extends Horde_Form
{
    public function __construct(&$vars)
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