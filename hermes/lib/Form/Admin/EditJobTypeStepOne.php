<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/**
 * @package Hermes
 */
class Hermes_Form_Admin_EditJobTypeStepOne extends Horde_Form
{

    public function __construct(&$vars)
    {
        parent::__construct($vars, 'editjobtypestep1form');

        $values = array();
        try {
            $jobtypes = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->listJobTypes();
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