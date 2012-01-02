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
class Hermes_Form_Admin_DeleteJobType extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, 'deletejobtypeform');

        $jobtype = $vars->get('jobtype');

        try {
            $info = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->getJobTypeByID($jobtype);
        } catch (Exception $e) {}
        $yesnotype = 'enum';
        $type_params = array(array(0 => _("No"), 1 => _("Yes")));

        $this->addHidden('', 'jobtype', 'int', true, true);

        $sname = &$this->addVariable(_("Job Type"), 'name', 'text', false, true);
        $sname->setDefault($info['name']);

        $this->addVariable(
            _("Really delete this job type? This may cause data problems!"),
            'yesno',
            $yesnotype,
            true,
            false,
            null,
            $type_params);
    }

}