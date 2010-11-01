<?php
/**
 * Spam script.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jason Felice <jason.m.felice@gmail.com>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ingo');

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

if (!in_array(Ingo_Storage::ACTION_SPAM, $_SESSION['ingo']['script_categories'])) {
    $notification->push(_("Simple spam filtering is not supported in the current filtering driver."), 'horde.error');
    Horde::url('filters.php', true)->redirect();
}

/* Get the spam object and rule. */
$spam = $ingo_storage->retrieve(Ingo_Storage::ACTION_SPAM);
$filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
$spam_id = $filters->findRuleId(Ingo_Storage::ACTION_SPAM);
$spam_rule = $filters->getRule($spam_id);

$vars = Horde_Variables::getDefaultVariables();
if ($vars->submitbutton == _("Return to Rules List")) {
    Horde::url('filters.php', true)->redirect();
}

/* Build form. */
$form = new Horde_Form($vars);
$renderer = new Horde_Form_Renderer(array('varrenderer_driver' => array('ingo', 'ingo'), 'encode_title' => false));

$v = $form->addVariable(_("Spam Level:"), 'level', 'int', false, false, _("Messages with a likely spam score greater than or equal to this number will be treated as spam."));
$v->setHelp('spam-level');

$folder_var = $form->addVariable(_("Folder to receive spam:"), 'folder', 'ingo_folders', false);
$folder_var->setHelp('spam-folder');
$form->addHidden('', 'actionID', 'text', false);
$form->addHidden('', 'new_folder_name', 'text', false);

$form->setButtons(_("Save"));

/* Perform requested actions. */
if ($form->validate($vars)) {
    $success = true;

    // Create a new folder if requested.
    if ($vars->actionID == 'create_folder') {
        try {
            $result = Ingo::createFolder($vars->new_folder_name);
            if ($result) {
                $spam->setSpamFolder($result);
            } else {
                $success = false;
            }
        } catch (Horde_Exception $e) {
            $success = false;
            $notification->push($e);
        }
    } else {
        $spam->setSpamFolder($vars->folder);
    }

    $spam->setSpamLevel($vars->level);

    try {
        $ingo_storage->store($spam);
        $notification->push(_("Changes saved."), 'horde.success');
        if ($vars->submitbutton == _("Save and Enable")) {
            $filters->ruleEnable($spam_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Enabled"), 'horde.success');
            $spam_rule['disable'] = false;
        } elseif ($vars->submitbutton == _("Save and Disable")) {
            $filters->ruleDisable($spam_id);
            $ingo_storage->store($filters);
            $notification->push(_("Rule Disabled"), 'horde.success');
            $spam_rule['disable'] = true;
        }
    } catch (Ingo_Exception $e) {
        $notification->push($result);
        $success = false;
    }

    if ($success && $prefs->getValue('auto_update')) {
        Ingo::updateScript();
    }

    /* Update the timestamp for the rules. */
    $_SESSION['ingo']['change'] = time();
}

/* Add buttons depending on the above actions. */
if (empty($spam_rule['disable'])) {
    $form->appendButtons(_("Save and Disable"));
} else {
    $form->appendButtons(_("Save and Enable"));
}
$form->appendButtons(_("Return to Rules List"));

/* Set default values. */
$folder_var->type->setFolder($spam->getSpamFolder());
if (!$form->isSubmitted()) {
    $vars->level = $spam->getSpamLevel();
    $vars->folder = $spam->getSpamFolder();
    $vars->actionID = '';
    $vars->new_folder_name = '';
}

/* Set form title. */
$form_title = _("Spam Filtering");
if (!empty($spam_rule['disable'])) {
    $form_title .= ' [<span class="form-error">' . _("Disabled") . '</span>]';
}
$form_title .= ' ' . Horde_Help::link('ingo', 'spam');
$form->setTitle($form_title);

$title = _("Spam Filtering");
$menu = Ingo::menu();
Ingo::addNewFolderJs();
require INGO_TEMPLATES . '/common-header.inc';
echo $menu;
Ingo::status();
$form->renderActive($renderer, $vars, 'spam.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
