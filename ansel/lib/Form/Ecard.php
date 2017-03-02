<?php
/**
 * Ecard generator.
 *
 * @package Ansel
 */

class Ansel_Form_Ecard extends Horde_Form {

    protected $_useFormToken = true;

    public function __construct(&$vars, $title)
    {
        global $conf, $registry, $injector;

        parent::__construct($vars, $title);

        $this->setButtons(_("Send"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);
        $this->addHidden('', 'image_desc', 'text', false);

        $user = $registry->getAuth();
        if (empty($user)) {
            $this->addVariable(_("Use the following return address:"), 'ecard_retaddr', 'text', true);
        } else {
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
            $from_addr = $identity->getDefaultFromAddress();
            $vars->set('ecard_retaddr', $from_addr);
            $this->addHidden('', 'ecard_retaddr', 'text', true);
        }

        $this->addVariable(_("Send ecard to the following address:"), 'ecard_addr', 'text', true);
        $this->addVariable(_("Comments:"), 'ecard_comments', 'longtext', false, false, null, array('15', '60'));
        if (empty($user) && !empty($conf['ecard']['captcha'])) {
            $this->addVariable(
                _("Spam protection"),
                'captcha',
                'figlet',
                 true,
                 false,
                 null,
                 array(
                    Ansel::getCAPTCHA(!$this->isSubmitted()),
                    $conf['ecard']['figlet_font']
                )
            );
        }
    }

    public function validate($vars = null, $canAutoFill = false)
    {
        global $conf, $registry;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!$registry->getAuth() && !empty($conf['ecard']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->addVariable(
                    _("Spam protection"),
                    'captcha',
                    'figlet',
                    true,
                    false,
                    null,
                    array(
                        Ansel::getCAPTCHA(true),
                        $conf['ecard']['figlet_font']
                    )
                );
            }
            return false;
        }

        return true;
    }

}
