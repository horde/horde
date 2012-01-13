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
class Hermes_Form_Admin_EditClientStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, 'editclientstep1form');

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