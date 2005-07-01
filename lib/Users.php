<?php
/**
 * SelectContextForm Class
 *
 * $Horde: shout/lib/Users.php,v 1.56 2005/01/03 14:35:44 jan Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Whups
 */
class SelectContextForm extends Horde_Form {

    var $_useFormToken = false;

    function SelectContextForm(&$vars)
    {
        global $shout;

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