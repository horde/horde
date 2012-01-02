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
class Hermes_Form_Admin_AddJobType extends Horde_Form
{

    public function __construct(&$vars)
    {
        parent::__construct($vars, 'addjobtypeform');

        $this->addVariable(_("Job Type"), 'name', 'text', true);
        $var = &$this->addVariable(_("Enabled?"), 'enabled', 'boolean', false);
        $var->setDefault(true);
        $var = &$this->addVariable(_("Billable?"), 'billable', 'boolean', false);
        $var->setDefault(true);
        $this->addVariable(_("Hourly Rate"), 'rate', 'number', false);
    }

}