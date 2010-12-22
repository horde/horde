<?php

/**
 * DeliverableClientSelector - Form for selecting client on deliverables screen
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Hermes
 */
class Hermes_Form_Deliverable extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::Horde_Form($vars, _("Deliverable Detail"));

        $this->addHidden('', 'deliverable_id', 'text', false);
        $this->addHidden('', 'client_id', 'text', false);
        $this->addHidden('', 'parent', 'text', false);

        $this->addVariable(_("Display Name"), 'name', 'text', true);
        $this->addVariable(_("Active?"), 'active', 'boolean', true);
        $this->addVariable(_("Estimated Hours"), 'estimate', 'number', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false);
    }

}
