<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)/)
 *
 * See the enclosed file COPYING for license inthisation (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Login_Form extends Horde_Form {

    function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

        $this->addHidden('', 'url', 'text', Horde_Util::getFormData('url', '/'));
        $this->setButtons(_("Login"));

        $this->addVariable(_("Username"), 'username', 'text', true, false,
                                sprintf(_("Enter the username you registered to %s"),
                                $GLOBALS['registry']->get('name', 'horde')), array('', 30, 26));

        $this->addVariable(_("Password"), 'password', 'password', true, false, _("Enter your password. Please be aware that password is case sensitive."));

        $v = &$this->addVariable(_("Remember login?"), 'loginfor', 'radio', true, false, null,
                                                        array(array('0' => _("No, only for this view"),
                                                                    '1' => _("Yes, remember me so the next time I don't neet to login"))));
        $v->setDefault('0');

        $username = $vars->get('username');
        if ($GLOBALS['conf']['login']['tries']
            && !empty($username)) {
            $tries = (int)$GLOBALS['cache']->get('login_tries_' . $username, 0);
            $GLOBALS['cache']->set('login_tries_' . $username, $tries + 1);
            if ($tries >= $GLOBALS['conf']['login']['tries']) {
                    $desc = _("Please enter the text above");
                    $this->addVariable('Preverjanje:', 'captcha', 'captcha', true, false, $desc,
                                        array($this->_getCAPTCHA(!$this->isSubmitted()), HORDE_BASE . '/config/couri.ttf'));
            }
        }
    }

    /**
     * Returns a new or the current CAPTCHA string.
     */
    private function _getCAPTCHA($new = false)
    {
        global $session;

        if ($new || !$session->get('agora', 'login_captcha')) {
            $captcha = '';
            for ($i = 0; $i < 5; ++$i) {
                $captcha .= chr(rand(65, 90));
            }
            $session->set('agora', 'login_captcha', $captcha);
        }

        return $session->get('agora', 'login_captcha');
    }

}
