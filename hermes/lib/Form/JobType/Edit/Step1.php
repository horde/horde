<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Hermes
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Hermes_Form_JobType_Edit_Step1 extends Horde_Form
{
    public function __construct(&$vars)
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