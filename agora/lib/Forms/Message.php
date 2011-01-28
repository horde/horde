<?php
/**
 * Message form class.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Agora
 */
class MessageForm extends Horde_Form {

    function validate(&$vars, $canAutoFill = false)
    {
        global $conf;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!$GLOBALS['registry']->getAuth() && !empty($conf['forums']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->insertVariableBefore('newcomment', _("Spam protection"), 'captcha', 'figlet', true, null, null, array(Agora::getCAPTCHA(true), $conf['forums']['figlet_font']));
            }
            return false;
        }

        return true;
    }

    function &getRenderer($params = array())
    {
        $renderer = new Horde_Form_Renderer_MessageForm($params);
        return $renderer;
    }

}

/**
 * Message renderer class.
 *
 * @package Agora
 */
class Horde_Form_Renderer_MessageForm extends Horde_Form_Renderer {

    function _renderVarInputEnd(&$form, &$var, &$vars)
    {
        if ($var->hasDescription()) {
            // The description is actually the quote button
            echo ' ' . $var->getDescription();
        }
    }

    function close($focus = false)
    {
        echo '</form>' . "\n";

        if (Horde_Util::getGet('reply_focus')) {
            echo '<script type="text/javascript">document.getElementById("message_body").focus()</script>';
        }
    }

}
