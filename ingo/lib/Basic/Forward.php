<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2003-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Forward page.
 *
 * @author    Todd Merritt <tmerritt@email.arizona.edu>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Forward extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification;

        /* Redirect if forward is not available. */
        $this->_assertCategory('Ingo_Rule_System_Forward', _("Forward"));
        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get the forward object and rule. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $forward = $ingo_storage->getSystemRule('Ingo_Rule_System_Forward');

        /* Build form. */
        $form = new Ingo_Form_Forward($this->vars);

        /* Perform requested actions. Ingo_Form_Forward does token checking
         * for us. */
        if ($form->validate($this->vars)) {
            $forward->addresses = $this->vars->addresses;
            $forward->keep = ($this->vars->keep_copy == 'on');

            try {
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $forward->disable = true;
                    $notify = _("Rule Enabled");
                } elseif ($this->vars->submitbutton == _("Save and Disable")) {
                    $forward->disable = false;
                    $notify = _("Rule Disabled");
                } else {
                    $notify = _("Changes saved.");
                }

                $ingo_storage->updateRule($forward);
                $notification->push($notify, 'horde.success');

                $injector->getInstance('Ingo_Factory_Script')->activateAll();
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
        }

        /* Add buttons depending on the above actions. */
        $form->setCustomButtons($forward->disable);

        /* Set default values. */
        if (!$form->isSubmitted()) {
            $this->vars->keep_copy = $forward->keep;
            $this->vars->addresses = implode("\n", $forward->addresses);
        }

        /* Set form title. */
        $form_title = _("Forward");
        if ($forward->disable) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'forward');
        $form->setTitle($form_title);

        $this->header = _("Forwards Edit");

        Horde::startBuffer();
        Horde_Util::pformInput();
        $form->renderActive(
            new Horde_Form_Renderer(array(
                'encode_title' => false,
                'varrenderer_driver' => array('ingo', 'ingo')
            )),
            $this->vars,
            self::url(array('append_session' => -1)),
            'post'
        );
        $this->output = Horde::endBuffer();
    }

    /**
     */
    public static function url(array $opts = array())
    {
        if (empty($opts['append_session'])) {
            $opts['append_session'] = 0;
        }
        return Horde::url('basic.php', true, array('append_session' => $opts['append_session']))->add('page', 'forward');
    }

}
