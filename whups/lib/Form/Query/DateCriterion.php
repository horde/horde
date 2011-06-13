<?php
/**
 * @package Whups
 */
class Whups_Form_Query_DateCriterion extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct(
            $vars,
            $vars->get('edit') ? _("Edit Date Criterion") : _("Add Date Criterion"),
            'Whups_Form_Query_DateCriterion');

        $this->addHidden('', 'edit', 'boolean', false);

        $this->addVariable(
            _("Created from"), 'ticket_timestamp[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("Created to"), 'ticket_timestamp[to]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));

        $this->addVariable(
            _("Updated from"), 'date_updated[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("Updated to"), 'date_updated[to]', 'monthdayyear', false, false,
            null, array(date('Y') - 10));

        $this->addVariable(
            _("Resolved from"), 'date_resolved[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("Resolved to"), 'date_resolved[to]', 'monthdayyear', false, false,
            null, array(date('Y') - 10));

        $this->addVariable(
            _("Assigned from"), 'date_assigned[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("Assigned to"), 'date_assigned[to]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));

        $this->addVariable(
            _("Due from"), 'ticket_due[from]', 'monthdayyear', false, false,
            null, array(date('Y') - 10));
        $this->addVariable(
            _("Due to"), 'ticket_due[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
    }

    public function execute(&$vars)
    {
        $path = $vars->get('path');
        $parent = false;

        $keys = array(
            Whups_Query::CRITERION_TIMESTAMP => 'ticket_timestamp',
            Whups_Query::CRITERION_UPDATED => 'date_updated',
            Whups_Query::CRITERION_RESOLVED => 'date_resolved',
            Whups_Query::CRITERION_ASSIGNED => 'date_assigned',
            Whups_Query::CRITERION_DUE => 'ticket_due');

        foreach ($keys as $key_id => $key_name) {
            $date = $vars->get($key_name . '[from]');
            if (!empty($date['month'])) {
                if (!$parent) {
                    $path = $GLOBALS['whups_query']->insertBranch(
                        $path, Whups_Query::TYPE_AND);
                    $parent = true;
                }
                $date = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                $GLOBALS['whups_query']->insertCriterion(
                    $path, $key_id, null, Whups_Query::OPERATOR_GREATER, $date);
            }
            $date = $vars->get($key_name . '[to]');
            if (!empty($date['month'])) {
                if (!$parent) {
                    $path = $GLOBALS['whups_query']->insertBranch(
                        $path, Whups_Query::TYPE_AND);
                    $parent = true;
                }
                $date = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                $GLOBALS['whups_query']->insertCriterion(
                    $path, $key_id, null, Whups_Query::OPERATOR_LESS, $date);
            }
        }

        $this->unsetVars($vars);
    }

}