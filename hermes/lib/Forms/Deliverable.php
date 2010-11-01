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
class DeliverableClientSelector extends Horde_Form {

    function DeliverableClientSelector(&$vars)
    {
        parent::Horde_Form($vars, _("Select Client"));

        require 'Horde/Form/Action.php';
        $action = &Horde_Form_Action::factory('submit');
        list($clienttype, $clientparams) = $this->getClientType();

        $cli = &$this->addVariable(_("Client"), 'client_id', $clienttype, true, false, null, $clientparams);
        $cli->setAction($action);
        $this->setButtons(_("Edit Deliverables"));
    }

    function getClientType()
    {
        $clients = Hermes::listClients();
        if (is_a($clients, 'PEAR_Error')) {
            return array('invalid', array(sprintf(_("An error occurred listing clients: %s"),
                                                  $clients->getMessage())));
        } elseif (count($clients)) {
            return array('enum', array($clients));
        } else {
            return array('invalid', array(_("There are no clients which you have access to.")));
        }
    }

}

class DeliverableForm extends Horde_Form {

    function DeliverableForm(&$vars)
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
