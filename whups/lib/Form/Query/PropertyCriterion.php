<?php
/**
 * @package Whups
 */
class Whups_Form_Query_PropertyCriterion extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct(
            $vars,
            $vars->get('edit') ? _("Edit Property Criterion") : _("Add Property Criterion"),
            'Whups_Form_Query_PropertyCriterion');

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("Id"), 'id', 'intlist', false);

        /* Types. */
        $this->addVariable(
            _("Type"), 'ttype', 'enum', false, false, null,
            array($whups_driver->getAllTypes(), _("Any")));


        /* Queues. */
        $queues = Whups::permissionsFilter(
            $whups_driver->getQueues(), 'queue', Horde_Perms::READ);
        if (count($queues)) {
            $v = &$this->addVariable(
                _("Queue"), 'queue', 'enum', false, false, null,
                array($queues, _("Any")));
            $v->setAction(Horde_Form_Action::factory('reload'));
            if ($vars->get('queue')) {
                $this->addVariable(
                    _("Version"), 'version', 'enum', false, false, null,
                    array($whups_driver->getVersions($vars->get('queue')), _("Any")));
            }
        }

        /* States. */
        $states = $whups_driver->getStates();
        $this->addVariable(
            _("State"), 'state', 'enum', false, false, null,
            array($states, _("Any")));

        /* Priorities. */
        $priorities = $whups_driver->getPriorities();
        $this->addVariable(
            _("Priority"), 'priority', 'enum', false, false, null,
            array($priorities, _("Any")));
    }

    public function execute(&$vars)
    {
        $path = $vars->get('path');

        $id = $vars->get('id');
        if (strlen(trim($id))) {
            $newpath = $path;
            $ids = split("[\\t\\n ,]+", $id);

            if (count($ids) > 1) {
                $newpath = $GLOBALS['whups_query']->insertBranch(
                    $path, Whups_Query::TYPE_OR);
            }

            foreach ($ids as $id) {
                $GLOBALS['whups_query']->insertCriterion(
                    $newpath, Whups_Query::CRITERION_ID, null,
                    Whups_Query::OPERATOR_EQUAL, $id);
            }
        }

        $queue = $vars->get('queue');
        if ($queue) {
            $version = $vars->get('version');
            if ($version) {
                $path = $GLOBALS['whups_query']->insertBranch(
                    $path, Whups_Query::TYPE_AND);
            }
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_QUEUE, null,
                Whups_Query::OPERATOR_EQUAL, $queue);
            if ($version) {
                $GLOBALS['whups_query']->insertCriterion(
                    $path, Whups_Query::CRITERION_VERSION, null,
                    Whups_Query::OPERATOR_EQUAL, $version);
            }
        }

        $type = $vars->get('ttype');
        if ($type) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_TYPE, null,
                Whups_Query::OPERATOR_EQUAL, $type);
        }

        $state = $vars->get('state');
        if ($state) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_STATE, null,
                Whups_Query::OPERATOR_EQUAL, $state);
        }

        $priority = $vars->get('priority');
        if ($priority) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_PRIORITY, null,
                 Whups_Query::OPERATOR_EQUAL, $priority);
        }

        $this->unsetVars($vars);
    }

}