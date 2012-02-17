<?php
/**
 * This file contains all Horde_Form classes to create a new ticket.
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
class Whups_Form_Ticket_CreateStepTwo extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Create Ticket - Step 2"));

        $types = $whups_driver->getTypes($vars->get('queue'));
        $info  = $whups_driver->getQueue($vars->get('queue'));
        $type = $whups_driver->getDefaultType($vars->get('queue'));
        if (count($types) == 0) {
            $typetype = 'invalid';
            $type_params = array(
                _("There are no ticket types associated with this queue; until there are, you cannot create any tickets in this queue."));
        } else {
            $typetype = 'enum';
            $type_params = array($types);
            if (empty($type) || !isset($types[$type])) {
                $type_params[] = _("Choose:");
            }
        }
        $types = &$this->addVariable(
            _("Ticket Type"), 'type', $typetype, true, false, null, $type_params);
        $types->setDefault($type);

        if (!empty($info['versioned'])) {
            $versions = $whups_driver->getVersions($vars->get('queue'));
            if (count($versions) == 0) {
                $vtype = 'invalid';
                $v_params = array(_("This queue requires that you specify a version, but there are no versions associated with it. Until versions are created for this queue, you will not be able to create tickets."));
            } else {
                $vtype = 'enum';
                $v_params = array($versions);
            }
            $this->addVariable(
                _("Queue Version"), 'version', $vtype, true, false, null, $v_params);
        } else {
            $types->setAction(Horde_Form_Action::factory('submit'));
        }
    }

}
