<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Vacation page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Vacation extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $registry;

        $this->_assertCategory('Ingo_Rule_System_Vacation', _("Vacation"));
        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get vacation object and rules. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $vacation = $ingo_storage->getSystemRule('Ingo_Rule_System_Vacation');

        /* Build form. */
        $form = new Ingo_Form_Vacation(
            $this->vars,
            '',
            null,
            $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_VACATION)->availableCategoryFeatures('Ingo_Rule_System_Vacation')
        );

        /* Perform requested actions. Ingo_Form_Vacation does token checking
         * for us. */
        if ($form->validate($this->vars)) {
            $form->getInfo($this->vars, $info);
            $vacation->addresses = isset($info['addresses']) ? $info['addresses'] : '';
            $vacation->days = $info['days'];
            $vacation->exclude = $info['excludes'];
            $vacation->ignore_list = ($info['ignorelist'] == 'on');
            $vacation->reason = $info['reason'];
            $vacation->subject = $info['subject'];
            $vacation->start = $info['start'];
            $vacation->end = $info['end'];

            try {
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $vacation->disable = false;
                    $notify = _("Rule Enabled");
                } elseif ($this->vars->get('submitbutton') == _("Save and Disable")) {
                    $vacation->disable = true;
                    $notify = _("Rule Disabled");
                } else {
                    $notification->push(_("Changes saved."), 'horde.success');
                }

                $ingo_storage->updateRule($vacation);
                $notification->push($notify, 'horde.success');

                Ingo_Script_Util::update();
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
        }

        /* Add buttons depending on the above actions. */
        $form->setCustomButtons($vacation->disable);

        /* Make sure we have at least one address. */
        if (!count($vacation)) {
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
            $addresses = implode("\n", $identity->getAll('from_addr'));
            /* Remove empty lines. */
            $addresses = trim(preg_replace('/\n+/', "\n", $addresses));
            if (empty($addresses)) {
                $addresses = $registry->getAuth();
            }
            $vacation->addresses = $addresses;
        }

        /* Set default values. */
        if (!$form->isSubmitted()) {
            $this->vars->set('addresses', implode("\n", $vacation->addresses));
            $this->vars->set('excludes', implode("\n", $vacation->exclude));
            $this->vars->set('ignorelist', $vacation->ignore_list);
            $this->vars->set('days', $vacation->days);
            $this->vars->set('subject', $vacation->subject);
            $this->vars->set('reason', $vacation->reason);
            $this->vars->set('start', $vacation->start);
            $this->vars->set('end', $vacation->end);
            $this->vars->set('start_year', $vacation->start_year);
            $this->vars->set('start_month', $vacation->start_month - 1);
            $this->vars->set('start_day', $vacation->start_day - 1);
            $this->vars->set('end_year', $vacation->end_year);
            $this->vars->set('end_month', $vacation->end_month - 1);
            $this->vars->set('end_day', $vacation->end_day - 1);
        }

        /* Set form title. */
        $form_title = _("Vacation");
        if ($vacation->disable) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'vacation');
        $form->setTitle($form_title);

        $this->header = _("Vacation Edit");

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
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'vacation');
    }

}
