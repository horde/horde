<?php
/**
 * Whups_Form_Search Class.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Whups
 */
class Whups_Form_Search extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars)
    {
        parent::__construct($vars);

        $this->setButtons(true);
        $this->appendButtons(_("Save as Query"));
        $this->setSection('attributes', _("Attributes"));

        $queues = Whups::permissionsFilter(
            $GLOBALS['whups_driver']->getQueues(), 'queue', Horde_Perms::READ);
        $queue_count = count($queues);
        $types = array();
        if ($queue_count == 1) {
            $types = $GLOBALS['whups_driver']->getTypes(key($queues));
            $this->addHidden('', 'queue', 'int', false, true);
            $vars->set('queue', key($queues));
        } else {
            if ($queue_count > 0) {
                $modtype = 'enum';
                $queue_params = array(array('0' => _("Any")) + $queues);
                foreach ($queues as $queueID => $name) {
                    $types = $types + $GLOBALS['whups_driver']->getTypes($queueID);
                }
            } else {
                $modtype = 'invalid';
                $queue_params = array(_("There are no queues which you can search."));
            }
            $this->addVariable(
                _("Queue"), 'queue', $modtype, false, false, null, $queue_params);
        }

        $this->addVariable(_("Summary like"), 'summary', 'text', false);

        $states = array();
        foreach ($types as $typeID => $typeName) {
            $states = $GLOBALS['whups_driver']->getAllStateInfo($typeID);
            $list = $default = array();
            foreach ($states as $state) {
                $list[$state['state_id']] = $state['state_name'];
                if ($state['state_category'] != 'resolved') {
                    $default[] = $state['state_id'];
                }
            }
            $v = $this->addVariable(
                $typeName, "states[$typeID]", 'multienum', false, false, null,
                array ($list, 4));
            if (!$this->isSubmitted()) {
                $v->setDefault($default);
            }
        }

        $this->setSection('dates', _("Dates"));
        $this->addVariable(
            _("Created from"), 'ticket_timestamp[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("to"), 'ticket_timestamp[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
        $this->addVariable(
            _("Updated from"), 'date_updated[from]', 'monthdayyear', false,
             false, null, array(date('Y') - 10));
        $this->addVariable(
            _("to"), 'date_updated[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
        $this->addVariable(
            _("Resolved from"), 'date_resolved[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("to"), 'date_resolved[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
        $this->addVariable(
            _("Assigned from"), 'date_assigned[from]', 'monthdayyear', false,
            false, null, array(date('Y') - 10));
        $this->addVariable(
            _("to"), 'date_assigned[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
        $this->addVariable(
            _("Due from"), 'ticket_due[from]', 'monthdayyear', false, false,
            null, array(date('Y') - 10));
        $this->addVariable(
            _("to"), 'ticket_due[to]', 'monthdayyear', false, false, null,
            array(date('Y') - 10));
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param Horde_Variables $vars  A Horde_Variables instance, optional since Horde 3.2.
     * @param array           $info  Array to be filled with the submitted field
     *                               values.
     */
    public function getInfo($vars, &$info)
    {
        parent::getInfo($vars, $info);

        if (empty($info['queue'])) {
            $info['queue'] = array_keys(
                Whups::permissionsFilter(
                    $GLOBALS['whups_driver']->getQueues(),
                    'queue',
                    Horde_Perms::READ,
                    $GLOBALS['registry']->getAuth(),
                    $GLOBALS['registry']->getAuth()));
        } else {
            $info['queue'] = array($info['queue']);
        }

        if (empty($info['states'])) {
            unset($info['states']);
        }

        // ... if we were given a set of states
        if (isset($info['states'])) {
            // collect them into a list of state_id
            $info['state_id'] = array();
            foreach ($info['states'] as $states) {
                if (isset($states)) {
                    // because null === array_merge(array, null)
                    $info['state_id'] = array_merge($info['state_id'], $states);
                }
            }
            unset($info['states']);
        }

        // Remove any queues that don't have a state selected.
        $types = array();
        foreach ($info['queue'] as $queue) {
            foreach ($GLOBALS['whups_driver']->getTypeIds($queue) as $type) {
                $types[$type][$queue] = true;
            }
        }
        $queues = array();
        foreach ($info['state_id'] as $stateId) {
            $state = $GLOBALS['whups_driver']->getState($stateId);
            if (isset($types[$state['type']])) {
                $queues = array_merge($queues, array_keys($types[$state['type']]));
            }
        }
        $info['queue'] = array_intersect($info['queue'], $queues);
    }
}
