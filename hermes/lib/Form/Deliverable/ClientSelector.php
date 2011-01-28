<?php
/**
 * DeliverableClientSelector - Form for selecting client on deliverables screen
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Hermes
 */
class Hermes_Form_Deliverable_ClientSelector extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::Horde_Form($vars, _("Select Client"));
        $action = &Horde_Form_Action::factory('submit');
        list($clienttype, $clientparams) = $this->getClientType();

        $cli = &$this->addVariable(_("Client"), 'client_id', $clienttype, true, false, null, $clientparams);
        $cli->setAction($action);
        $this->setButtons(_("Edit Deliverables"));
    }

    public function getClientType()
    {
        try {
            $clients = Hermes::listClients();
        } catch (Hermes_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing clients: %s"),
                                                  $clients->getMessage())));
        }
        if (count($clients)) {
            return array('enum', array($clients));
        } else {
            return array('invalid', array(_("There are no clients which you have access to.")));
        }
    }

}