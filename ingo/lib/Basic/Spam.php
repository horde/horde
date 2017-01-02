<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Spam page.
 *
 * @author    Jason Felice <jason.m.felice@gmail.com>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Spam extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification;

        $this->_assertCategory('Ingo_Rule_System_Spam', _("Spam filtering"));

        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get the spam object and rule. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $spam = $ingo_storage->getSystemRule('Ingo_Rule_System_Spam');

        /* Build form. */
        $form = new Ingo_Form_Spam($this->vars);
        $renderer = new Horde_Form_Renderer(array(
            'encode_title' => false,
            'varrenderer_driver' => array('ingo', 'ingo')
        ));

        /* Perform requested actions. Ingo_Form_Spam does token checking for
         * us .*/
        if ($form->validate($this->vars)) {
            $success = false;

            try {
                $spam->mailbox = $this->validateMbox('folder');
                $success = true;
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }

            $spam->level = $this->vars->level;

            try {
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $spam->disable = false;
                    $notify = _("Rule Enabled");
                } elseif ($this->vars->submitbutton == _("Save and Disable")) {
                    $spam->disable = true;
                    $notify = _("Rule Disabled");
                } else {
                    $notify = _("Changes saved.");
                }
                $ingo_storage->updateRule($spam);
                $notification->push($notify, 'horde.success');

                $injector->getInstance('Ingo_Factory_Script')->activateAll();
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
        }

        /* Add buttons depending on the above actions. */
        $form->setCustomButtons($spam->disable);

        /* Set default values. */
        $form->folder_var->type->setFolder($spam->mailbox);
        if (!$form->isSubmitted()) {
            $this->vars->level = $spam->level;
            $this->vars->folder = $spam->mailbox;
            $this->vars->actionID = '';
        }

        /* Set form title. */
        $form_title = _("Spam Filtering");
        if ($spam->disable) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'spam');
        $form->setTitle($form_title);

        $this->header = _("Spam Filtering");

        Horde::startBuffer();
        Horde_Util::pformInput();
        $form->renderActive($renderer, $this->vars, self::url(array('append_session' => -1)), 'post');
        $this->output = Horde::endBuffer();
    }

    /**
     */
    public static function url(array $opts = array())
    {
        if (empty($opts['append_session'])) {
            $opts['append_session'] = 0;
        }
        return Horde::url('basic.php', true, array('append_session' => $opts['append_session']))->add('page', 'spam');
    }

}
