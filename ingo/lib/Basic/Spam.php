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
 * Spam page.
 *
 * @author    Jason Felice <jason.m.felice@gmail.com>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Spam extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $session, $prefs;

        if (!in_array(Ingo_Storage::ACTION_SPAM, $session->get('ingo', 'script_categories'))) {
            $notification->push(_("Simple spam filtering is not supported in the current filtering driver."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Get the spam object and rule. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $spam = $ingo_storage->retrieve(Ingo_Storage::ACTION_SPAM);
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $spam_id = $filters->findRuleId(Ingo_Storage::ACTION_SPAM);
        $spam_rule = $filters->getRule($spam_id);

        if ($this->vars->submitbutton == _("Return to Rules List")) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Build form. */
        $form = new Ingo_Form_Spam($this->vars);
        $renderer = new Horde_Form_Renderer(array(
            'encode_title' => false,
            'varrenderer_driver' => array('ingo', 'ingo')
        ));

        /* Perform requested actions. */
        if ($form->validate($this->vars)) {
            $success = false;

            try {
                $spam->setSpamFolder(Ingo::validateFolder($this->vars, 'folder'));
                $success = true;
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }

            $spam->setSpamLevel($this->vars->level);

            try {
                $ingo_storage->store($spam);
                $notification->push(_("Changes saved."), 'horde.success');
                if ($this->vars->submitbutton == _("Save and Enable")) {
                    $filters->ruleEnable($spam_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Enabled"), 'horde.success');
                    $spam_rule['disable'] = false;
                } elseif ($this->vars->submitbutton == _("Save and Disable")) {
                    $filters->ruleDisable($spam_id);
                    $ingo_storage->store($filters);
                    $notification->push(_("Rule Disabled"), 'horde.success');
                    $spam_rule['disable'] = true;
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
        $form->setCustomButtons($spam_rule['disable']);

        /* Set default values. */
        $form->folder_var->type->setFolder($spam->getSpamFolder());
        if (!$form->isSubmitted()) {
            $this->vars->level = $spam->getSpamLevel();
            $this->vars->folder = $spam->getSpamFolder();
            $this->vars->actionID = '';
        }

        /* Set form title. */
        $form_title = _("Spam Filtering");
        if (!empty($spam_rule['disable'])) {
            $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
        }
        $form_title .= ' ' . Horde_Help::link('ingo', 'spam');
        $form->setTitle($form_title);

        $this->header = _("Spam Filtering");

        Horde::startBuffer();
        $form->renderActive($renderer, $this->vars, self::url(), 'post');
        $this->output = Horde::endBuffer();
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'spam');
    }

}


/**
 * Dummy class to hold the select box created by {@link Ingo::flistSelect()}.
 *
 * @see Horde_Core_Ui_VarRenderer_Ingo
 * @see Ingo::flistSelect()
 */
class Horde_Form_Type_ingo_folders extends Horde_Form_Type {

    var $_folder;

    function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    function getFolder()
    {
        return $this->_folder;
    }

    function setFolder($folder)
    {
        $this->_folder = $folder;
    }

}
