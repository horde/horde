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
class Hermes_Form_Admin_EditClientStepTwo extends Horde_Form
{

    public function __construct(&$vars)
    {
        parent::__construct($vars, 'editclientstep2form');

        $client = $vars->get('client');
        try {
            $info = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->getClientSettings($client);
        } catch (Hermes_Exception $e) {}
        if (!$info) {
            $stype = 'invalid';
            $type_params = array(_("This is not a valid client."));
        } else {
            $stype = 'text';
            $type_params = array();
        }

        $this->addHidden('', 'client', 'text', true, true);
        $name = &$this->addVariable(_("Client"), 'name', $stype, false, true, null, $type_params);
        $name->setDefault($info['name']);

        $enterdescription = &$this->addVariable(sprintf(_("Should users enter descriptions of their timeslices for this client? If not, the description will automatically be \"%s\"."), _("See Attached Timesheet")), 'enterdescription', 'boolean', true);
        if (!empty($info['enterdescription'])) {
            $enterdescription->setDefault($info['enterdescription']);
        }

        $exportid = &$this->addVariable(_("ID for this client when exporting data, if different from the name displayed above."), 'exportid', 'text', false);
        if (!empty($info['exportid'])) {
            $exportid->setDefault($info['exportid']);
        }
    }

}
