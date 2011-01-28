<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/**
 * @package Hermes
 */
class AddJobTypeForm extends Horde_Form {

    function AddJobTypeForm(&$vars)
    {
        parent::Horde_Form($vars, 'addjobtypeform');

        $this->addVariable(_("Job Type"), 'name', 'text', true);
        $var = &$this->addVariable(_("Enabled?"), 'enabled', 'boolean', false);
        $var->setDefault(true);
        $var = &$this->addVariable(_("Billable?"), 'billable', 'boolean', false);
        $var->setDefault(true);
        $this->addVariable(_("Hourly Rate"), 'rate', 'number', false);
    }

}

/**
 * @package Hermes
 */
class EditJobTypeStep1Form extends Horde_Form {

    function EditJobTypeStep1Form(&$vars)
    {
        parent::Horde_Form($vars, 'editjobtypestep1form');

        $values = array();
        try {
            $jobtypes = $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes();
            foreach ($jobtypes as $id => $jobtype) {
                $values[$id] = $jobtype['name'];
                if (empty($jobtype['enabled'])) {
                    $values[$id] .= _(" (DISABLED)");
                }
            }
        } catch (Hermes_Exception $e) {}

        if ($values) {
            $subtype = 'enum';
            $type_params = array($values);
        } else {
            $subtype = 'invalid';
            $type_params = array(_("There are no job types to edit"));
        }

        $this->addVariable(_("JobType Name"), 'jobtype', $subtype, true, false, null, $type_params);
    }

}

/**
 * @package Hermes
 */
class EditJobTypeStep2Form extends Horde_Form {

    function EditJobTypeStep2Form(&$vars)
    {
        parent::Horde_Form($vars, 'editjobtypestep2form');

        $jobtype = $vars->get('jobtype');
        try {
            $info = $GLOBALS['injector']->getInstance('Hermes_Driver')->getJobTypeByID($jobtype);
        } catch (Exception $e) {}

        if (!$info) {
            $stype = 'invalid';
            $type_params = array(_("This is not a valid job type."));
        } else {
            $stype = 'text';
            $type_params = array();
        }

        $this->addHidden('', 'jobtype', 'int', true, true);

        $sname = &$this->addVariable(_("Job Type"), 'name', $stype, true, false, null, $type_params);
        if (!empty($info['name'])) {
            $sname->setDefault($info['name']);
        }

        $enab = &$this->addVariable(_("Enabled?"), 'enabled', 'boolean', false);
        $enab->setDefault($info['enabled']);
        $enab = &$this->addVariable(_("Billable?"), 'billable', 'boolean', false);
        $enab->setDefault($info['billable']);
        $enab = &$this->addVariable(_("Hourly Rate"), 'rate', 'number', false);
        $enab->setDefault($info['rate']);
    }

}

/**
 * @package Hermes
 */
class DeleteJobTypeForm extends Horde_Form {

    function DeleteJobTypeForm(&$vars)
    {
        parent::Horde_Form($vars, 'deletejobtypeform');

        $jobtype = $vars->get('jobtype');

        try {
            $info = $GLOBALS['injector']->getInstance('Hermes_Driver')->getJobTypeByID($jobtype);
        } catch (Exception $e) {}
        $yesnotype = 'enum';
        $type_params = array(array(0 => _("No"), 1 => _("Yes")));

        $this->addHidden('', 'jobtype', 'int', true, true);

        $sname = &$this->addVariable(_("Job Type"), 'name', 'text', false, true);
        $sname->setDefault($info['name']);

        $this->addVariable(_("Really delete this job type? This may cause data problems!!"), 'yesno', $yesnotype, true, false, null, $type_params);
    }

}

/**
 * @package Hermes
 */
class EditClientStep1Form extends Horde_Form {

    function EditClientStep1Form(&$vars)
    {
        parent::Horde_Form($vars, 'editclientstep1form');

        try {
            $clients = Hermes::listClients();
            if (count($clients)) {
                $subtype = 'enum';
                $type_params = array($clients);
            } else {
                $subtype = 'invalid';
                $type_params = array(_("There are no clients to edit"));
            }
        } catch (Hermes_Exception $e) {
            $subtype = 'invalid';
            $type_params = array($clients->getMessage());
        }

        $this->addVariable(_("Client Name"), 'client', $subtype, true, false, null, $type_params);
    }

}

/**
 * @package Hermes
 */
class EditClientStep2Form extends Horde_Form {

    function EditClientStep2Form(&$vars)
    {
        parent::Horde_Form($vars, 'editclientstep2form');

        $client = $vars->get('client');
        try {
            $info = $GLOBALS['injector']->getInstance('Hermes_Driver')->getClientSettings($client);
        } catch (Hermes_Exception $e) {}
        if (!$info) {
            $stype = 'invalid';
            $type_params = array(_("This is not a valid client."));
        } else {
            $stype = 'text';
            $type_params = array();
        }

        $this->addHidden('', 'client', 'text', true, true);
        $name = &$this->addVariable(_("Client"), 'name', $stype, false, true, null, $type_params);
        $name->setDefault($info['name']);

        $enterdescription = &$this->addVariable(sprintf(_("Should users enter descriptions of their timeslices for this client? If not, the description will automatically be \"%s\"."), _("See Attached Timesheet")), 'enterdescription', 'boolean', true);
        if (!empty($info['enterdescription'])) {
            $enterdescription->setDefault($info['enterdescription']);
        }

        $exportid = &$this->addVariable(_("ID for this client when exporting data, if different from the name displayed above."), 'exportid', 'text', false);
        if (!empty($info['exportid'])) {
            $exportid->setDefault($info['exportid']);
        }
    }

}
