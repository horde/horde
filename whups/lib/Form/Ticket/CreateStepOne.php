<?php
/**
 * Step One in creating new tickets.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Whups
 */

/**
 * @package Whups
 */
class Whups_Form_Ticket_CreateStepOne extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Create Ticket - Step 1"));

        $queues = Whups::permissionsFilter(
            $whups_driver->getQueues(), 'queue', Horde_Perms::EDIT);
        if (!$queues) {
            $this->addVariable(
                _("Queue Name"), 'queue', 'invalid', true, false, null,
                array(_("There are no queues which you can create tickets in.")));
        } else {
            foreach (array_keys($queues) as $queue_id) {
                $info = $whups_driver->getQueue($queue_id);
                if (!empty($info['description'])) {
                    $queues[$queue_id] .= ' [' . $info['description'] . ']';
                }
            }

            // Auto-select the only queue if only one option is available
            if (count($queues) == 1) {
                $vars->set('queue', array_pop(array_keys($queues)));
            }

            $queues = &$this->addVariable(
                _("Queue Name"), 'queue', 'enum', true, false, null,
                array($queues, _("Choose:")));
            $queues->setAction(Horde_Form_Action::factory('submit'));
        }
    }

}