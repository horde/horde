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
class Hermes_Form_Admin_EditJobTypeStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, 'editjobtypestep2form');

        $jobtype = $vars->get('jobtype');
        try {
            $info = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->getJobTypeByID($jobtype);
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