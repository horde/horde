<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Vacation page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Vacation extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $prefs, $session;

        /* Redirect if vacation is not available. */
        if (!in_array(Ingo_Storage::ACTION_VACATION, $session->get('ingo', 'script_categories'))) {
            $notification->push(_("Vacation is not supported in the current filtering driver."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get vacation object and rules. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $vacation = $ingo_storage->retrieve(Ingo_Storage::ACTION_VACATION);
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vac_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);
        $vac_rule = $filters->getRule($vac_id);

        /* Load libraries. */
        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Build form. */
        $form = new Ingo_Form_Vacation(
            $this->vars,
            '',
            null,
            $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_VACATION)->availableCategoryFeatures(Ingo_Storage::ACTION_VACATION)
        );

        /* Perform requested actions. */
        if ($form->validate($this->vars)) {
            $form->getInfo($this->vars, $info);
            $vacation->setVacationAddresses(isset($info['addresses']) ? $info['addresses'] : '');
            $vacation->setVacationDays($info['days']);
            $vacation->setVacationExcludes($info['excludes']);
            $vacation->setVacationIgnorelist(($info['ignorelist'] == 'on'));
            $vacation->setVacationReason($info['reason']);
            $vacation->setVacationSubject($info['subject']);
            $vacation->setVacationStart($info['start']);
            $vacation->setVacationEnd($info['end']);

            try {
                $ingo_storage->store($vacation);
                $notification->push(_("Changes saved."), 'horde.success');
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $filters->ruleEnable($vac_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Enabled"), 'horde.success');
                    $vac_rule['disable'] = false;
                } elseif ($this->vars->get('submitbutton') == _("Save and Disable")) {
                    $filters->ruleDisable($vac_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Disabled"), 'horde.success');
                    $vac_rule['disable'] = true;
                }
                if ($prefs->getValue('auto_update')) {
                    Ingo::updateScript();
                }
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }

            /* Update the timestamp for the rules. */
            $session->set('ingo', 'change', time());
        }

        /* Add buttons depending on the above actions. */
        $form->setCustomButtons($vac_rule['disable']);

        /* Make sure we have at least one address. */
        if (!$vacation->getVacationAddresses()) {
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
            $addresses = implode("\n", $identity->getAll('from_addr'));
            /* Remove empty lines. */
            $addresses = trim(preg_replace('/\n+/', "\n", $addresses));
            if (empty($addresses)) {
                $addresses = $GLOBALS['registry']->getAuth();
            }
            $vacation->setVacationAddresses($addresses);
        }

        /* Set default values. */
        if (!$form->isSubmitted()) {
            $this->vars->set('addresses', implode("\n", $vacation->getVacationAddresses()));
            $this->vars->set('excludes', implode("\n", $vacation->getVacationExcludes()));
            $this->vars->set('ignorelist', $vacation->getVacationIgnorelist());
            $this->vars->set('days', $vacation->getVacationDays());
            $this->vars->set('subject', $vacation->getVacationSubject());
            $this->vars->set('reason', $vacation->getVacationReason());
            $this->vars->set('start', $vacation->getVacationStart());
            $this->vars->set('end', $vacation->getVacationEnd());
            $this->vars->set('start_year', $vacation->getVacationStartYear());
            $this->vars->set('start_month', $vacation->getVacationStartMonth() - 1);
            $this->vars->set('start_day', $vacation->getVacationStartDay() - 1);
            $this->vars->set('end_year', $vacation->getVacationEndYear());
            $this->vars->set('end_month', $vacation->getVacationEndMonth() - 1);
            $this->vars->set('end_day', $vacation->getVacationEndDay() - 1);
        }

        /* Set form title. */
        $form_title = _("Vacation");
        if (!empty($vac_rule['disable'])) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'vacation');
        $form->setTitle($form_title);

        $this->header = _("Vacation Edit");

        Horde::startBuffer();
        $form->renderActive(
            new Horde_Form_Renderer(array('encode_title' => false)),
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
        return Horde::url('basic.php')->add('page', 'vacation');
    }

}
