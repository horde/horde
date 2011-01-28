<?php
/**
 * This file contains any general Horde_Form classes for administration
 * purposes that don't have have their own file in the Admin/ subdirectory.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class SendReminderForm extends Horde_Form {

    function SendReminderForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Send Reminders"));

        $queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue', Horde_Perms::EDIT);
        if (count($queues)) {
            $modtype = 'enum';
            $type_params = array($queues);
        } else {
            $modtype = 'invalid';
            $type_params = array(_("There are no queues available."));
        }

        $this->addVariable(_("Send only for this list of ticket ids"), 'id', 'intlist', false);
        $this->addVariable(_("For tickets from these queues"), 'queue', $modtype, false, false, null, $type_params);

        $cats = $whups_driver->getCategories();
        unset($cats['resolved']);
        $categories = &$this->addVariable(_("For tickets which are"), 'category', 'multienum', false, false, null, array($cats, 3));
        $categories->setDefault(array('assigned'));

        $this->addVariable(_("Unassigned tickets"), 'unassigned', 'email', false, false, _("If you select any tickets that do not have an owner, who should we send email to?"));
    }

}
