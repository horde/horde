<?php
/**
 * This file contains all Horde_Form classes for queue administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditQueueStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver, $registry;

        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            parent::Horde_Form($vars, _("Edit or Delete Queues"));
            $this->setButtons(array(_("Edit Queue"), _("Delete Queue")));
        } else {
            parent::Horde_Form($vars, _("Edit Queues"));
            $this->setButtons(array(_("Edit Queue")));
        }

        $queues = Whups::permissionsFilter(
            $whups_driver->getQueues(), 'queue', Horde_Perms::EDIT);
        if ($queues) {
            $modtype = 'enum';
            $type_params = array($queues);
        } else {
            $modtype = 'invalid';
            $type_params = array(_("There are no queues to edit"));
        }

        $this->addVariable(
            _("Queue Name"), 'queue', $modtype, true, false, null, $type_params);
    }

}