<?php
/**
 * @package Hermes
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

/**
 * TimeForm abstract class.
 *
 * Hermes forms can extend this to gain access to shared functionality.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class Hermes_Form_Time extends Horde_Form
{
    public function __construct(&$vars, $name = null)
    {
        parent::Horde_Form($vars, $name);
    }

    public function getJobTypeType()
    {
        try {
            $types = $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes(array('enabled' => true));
        } catch (Horde_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing job types: %s"), $e->getMessage())));
        }
        if (count($types)) {
            $values = array();
            foreach ($types as $id => $type) {
                $values[$id] = $type['name'];
            }
            return array('enum', array($values));
        }

        return array('invalid', array(_("There are no job types configured.")));
    }

    public function getClientType()
    {
        try {
            $clients = Hermes::listClients();
        } catch (Horde_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing clients: %s"), $e->getMessage())));
        }
        if ($clients) {
            if (count($clients) > 1) {
                $clients = array('' => _("--- Select A Client ---")) + $clients;
            }
            return array('enum', array($clients));
        } else {
            return array('invalid', array(_("There are no clients which you have access to.")));
        }
    }

}