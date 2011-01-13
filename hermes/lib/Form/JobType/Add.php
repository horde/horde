<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Hermes_Form_JobType_Add extends Horde_Form
{
    public function __construct(&$vars)
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