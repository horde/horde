<?php
/**
 * Ecard generator.
 *
 * @package Ansel
 */

class EcardForm extends Horde_Form {

    var $_useFormToken = false;

    function EcardForm(&$vars, $title)
    {
        parent::Horde_Form($vars, $title);

        $this->setButtons(_("Send"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);
        $this->addHidden('', 'image_desc', 'text', false);

        $user = Horde_Auth::getAuth();
        if (empty($user)) {
            $this->addVariable(_("Use the following return address:"), 'ecard_retaddr', 'text', true);
        } else {
            require_once 'Horde/Identity.php';
            $identity = Identity::singleton();
            $from_addr = $identity->getDefaultFromAddress();
            $vars->set('ecard_retaddr', $from_addr);
            $this->addHidden('', 'ecard_retaddr', 'text', true);
        }

        $this->addVariable(_("Send ecard to the following address:"), 'ecard_addr', 'text', true);
        $this->addVariable(_("Comments:"), 'ecard_comments', 'longtext', false, false, null, array('15', '60'));
    }

}
