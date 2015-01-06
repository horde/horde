<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Forward page.
 *
 * @author    Todd Merritt <tmerritt@email.arizona.edu>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
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
        $this->_assertCategory(Ingo_Storage::ACTION_FORWARD, _("Forward"));

        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get the forward object and rule. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $forward = $ingo_storage->retrieve(Ingo_Storage::ACTION_FORWARD);
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $fwd_id = $filters->findRuleId(Ingo_Storage::ACTION_FORWARD);
        $fwd_rule = $filters->getRule($fwd_id);

        /* Build form. */
        $form = new Ingo_Form_Forward($this->vars);

        /* Perform requested actions. Ingo_Form_Forward does token checking
         * for us. */
        if ($form->validate($this->vars)) {
            $forward->setForwardAddresses($this->vars->addresses);
            $forward->setForwardKeep($this->vars->keep_copy == 'on');
            try {
                $ingo_storage->store($forward);
                $notification->push(_("Changes saved."), 'horde.success');
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $filters->ruleEnable($fwd_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Enabled"), 'horde.success');
                    $fwd_rule['disable'] = false;
                } elseif ($this->vars->submitbutton == _("Save and Disable")) {
                    $filters->ruleDisable($fwd_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Disabled"), 'horde.success');
                    $fwd_rule['disable'] = true;
                }
                Ingo_Script_Util::update();
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
        }

        /* Add buttons depending on the above actions. */
        $form->setCustomButtons($fwd_rule['disable']);

        /* Set default values. */
        if (!$form->isSubmitted()) {
            $this->vars->keep_copy = $forward->getForwardKeep();
            $this->vars->addresses = implode("\n", $forward->getForwardAddresses());
        }

        /* Set form title. */
        $form_title = _("Forward");
        if (!empty($fwd_rule['disable'])) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'forward');
        $form->setTitle($form_title);

        $this->header = _("Forwards Edit");

        Horde::startBuffer();
        $form->renderActive(
            new Horde_Form_Renderer(array(
                'encode_title' => false,
                'varrenderer_driver' => array('ingo', 'ingo')
            )),
            $this->vars,
            self::url(),
            'post'
        );
        $this->output = Horde::endBuffer();
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'forward');
    }

}
