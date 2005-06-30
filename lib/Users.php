<?php
/**
 * CreateStep1Form Class
 *
 * $Horde: whups/lib/Create.php,v 1.56 2005/01/03 14:35:44 jan Exp $
 *
 * Copyright 2001-2005 Robert E. Coyle <robertecoyle@hotmail.com>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Whups
 */
class SelectContextForm extends Horde_Form {

    var $_useFormToken = false;

    function CreateStep1Form(&$vars)
    {
        global $whups;

        parent::Horde_Form($vars, _("Choose a context"));

        $contexts = $shout->getContexts("customer");
        if (count($contexts)) {
            $contexts = &$this->addVariable(_("Context"), 'context', 'enum',
                true, false, null, array($contexts, _("Choose:")));
            if (!Auth::getAuth()) {
                $this->addVariable(_("Your Email Address"), 'user_email',
                    'email', true);
            } else {
                require_once 'Horde/Form/Action.php';
                $contexts->setAction(Horde_Form_Action::factory('submit'));
            }
        } else {
            $this->addVariable(_("Context"), 'context', 'invalid', true,
                false, null, array(_("There are no contexts which you have
permission to view.")));
        }
    }

}