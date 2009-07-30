<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Horde_Prefs
 */

function handle_sentmailselect($updated)
{
    global $conf, $prefs, $identity;

    if ($conf['user']['allow_folders'] &&
        !$prefs->isLocked('sent_mail_folder')) {
        $sent_mail_folder = Horde_Util::getFormData('sent_mail_folder');
        $sent_mail_new = Horde_String::convertCharset(Horde_Util::getFormData('sent_mail_new'), Horde_Nls::getCharset(), 'UTF7-IMAP');
        $sent_mail_default = $prefs->getValue('sent_mail_folder');
        if (empty($sent_mail_folder) && !empty($sent_mail_new)) {
            $sent_mail_folder = IMP::appendNamespace($sent_mail_new);
        } elseif (($sent_mail_folder == '-1') && !empty($sent_mail_default)) {
            $sent_mail_folder = IMP::appendNamespace($sent_mail_default);
        }
        if (!empty($sent_mail_folder)) {
            $imp_folder = IMP_Folder::singleton();
            if (!$imp_folder->exists($sent_mail_folder)) {
                $imp_folder->create($sent_mail_folder, $prefs->getValue('subscribe'));
            }
        }
        $identity->setValue('sent_mail_folder', IMP::folderPref($sent_mail_folder, false));
        $updated = true;
    }

    return $updated;
}

function handlefolders($updated, $pref, $folder, $new)
{
    global $conf, $prefs;

    if ($conf['user']['allow_folders']) {
        $folder = Horde_Util::getFormData($folder);
        if (isset($folder) && !$prefs->isLocked($pref)) {
            $new = Horde_String::convertCharset(Horde_Util::getFormData($new), Horde_Nls::getCharset(), 'UTF7-IMAP');
            if ($folder == IMP::PREF_NO_FOLDER) {
                $prefs->setValue($pref, '');
            } else {
                if (empty($folder) && !empty($new)) {
                    $folder = IMP::appendNamespace($new);
                    $imp_folder = IMP_Folder::singleton();
                    if (!$imp_folder->create($folder, $prefs->getValue('subscribe'))) {
                        $folder = null;
                    }
                }
                if (!empty($folder)) {
                    $prefs->setValue($pref, IMP::folderPref($folder, false));
                    $updated = true;
                }
            }
        }
    }

    return $updated;
}

function handle_draftsselect($updated)
{
    return $updated | handlefolders($updated, 'drafts_folder', 'drafts', 'drafts_new');
}

function handle_trashselect($updated)
{
    global $prefs;
    $ret = true;

    if (Horde_Util::getFormData('trash') == IMP::PREF_VTRASH) {
        if ($prefs->isLocked('use_vtrash')) {
            $ret = false;
        } else {
            $prefs->setValue('use_vtrash', 1);
            $prefs->setValue('trash_folder', '');
        }
    } else {
        if ($prefs->isLocked('trash_folder')) {
            $ret = false;
        } else {
            $ret = $updated | handlefolders($updated, 'trash_folder', 'trash', 'trash_new');
            if ($ret) {
                $prefs->setValue('use_vtrash', 0);
                $prefs->setDirty('trash_folder', true);
            }
        }
    }

    return $ret;
}

function handle_sourceselect($updated)
{
    global $prefs;

    $search_sources = Horde_Util::getFormData('search_sources');
    if (!is_null($search_sources)) {
        $prefs->setValue('search_sources', $search_sources);
        unset($_SESSION['imp']['cache']['ac_ajax']);
        $updated = true;
    }

    $search_fields_string = Horde_Util::getFormData('search_fields_string');
    if (!is_null($search_fields_string)) {
        $prefs->setValue('search_fields', $search_fields_string);
        $updated = true;
    }

    $add_source = Horde_Util::getFormData('add_source');
    if (!is_null($add_source)) {
        $prefs->setValue('add_source', $add_source);
        $updated = true;
    }

    return $updated;
}

function handle_initialpageselect($updated)
{
    $initial_page = Horde_Util::getFormData('initial_page');
    $GLOBALS['prefs']->setValue('initial_page', $initial_page);
    return true;
}

function handle_encryptselect($updated)
{
    $default_encrypt = Horde_Util::getFormData('default_encrypt');
    $GLOBALS['prefs']->setValue('default_encrypt', $default_encrypt);
    return true;
}

function handle_spamselect($updated)
{
    return $updated | handlefolders($updated, 'spam_folder', 'spam', 'spam_new');
}

function handle_defaultsearchselect($updated)
{
    $default_search = Horde_Util::getFormData('default_search');
    $GLOBALS['prefs']->setValue('default_search', $default_search);
    return true;
}

function handle_soundselect($updated)
{
    return $GLOBALS['prefs']->setValue('nav_audio', Horde_Util::getFormData('nav_audio'));
}

function handle_flagmanagement($updated)
{
    $imp_flags = IMP_Imap_Flags::singleton();
    $action = Horde_Util::getFormData('flag_action');
    $data = Horde_Util::getFormData('flag_data');

    if ($action == 'add') {
        $imp_flags->addFlag($data);
        return false;
    }

    $def_color = $GLOBALS['prefs']->getValue('msgflags_color');

    // Don't set updated on these actions. User may want to do more actions.
    foreach ($imp_flags->getList() as $key => $val) {
        $md5 = hash('md5', $key);

        switch ($action) {
        case 'delete':
            if ($data == ('bg_' . $md5)) {
                $imp_flags->deleteFlag($key);
                return false;
            }
            break;

        default:
            /* Change labels for user-defined flags. */
            if ($val['t'] == 'imapp') {
                $label = Horde_Util::getFormData('label_' . $md5);
                if (strlen($label) && ($label != $val['l'])) {
                    $imp_flags->updateFlag($key, array('l' => $label));
                }
            }

            /* Change background for all flags. */
            $bg = strtolower(Horde_Util::getFormData('bg_' . $md5));
            if ((isset($val['b']) && ($bg != $val['b'])) ||
                (!isset($val['b']) && ($bg != $def_color))) {
                $imp_flags->updateFlag($key, array('b' => $bg));
            }
            break;
        }
    }

    return false;
}

function prefs_callback()
{
    global $prefs;

    /* Always check to make sure we have a valid trash folder if delete to
     * trash is active. */
    if (($prefs->isDirty('use_trash') || $prefs->isDirty('trash_folder')) &&
        $prefs->getValue('use_trash') &&
        !$prefs->getValue('trash_folder') &&
        !$prefs->getValue('use_vtrash')) {
        $GLOBALS['notification']->push(_("You have activated move to Trash but no Trash folder is defined. You will be unable to delete messages until you set a Trash folder in the preferences."), 'horde.warning');
    }

    if ($prefs->isDirty('use_vtrash') || $prefs->isDirty('use_vinbox')) {
        $imp_search = new IMP_Search();
        $imp_search->initialize(true);
    }

    if ($prefs->isDirty('subscribe') || $prefs->isDirty('tree_view')) {
        $imp_folder = IMP_Folder::singleton();
        $imp_folder->clearFlistCache();
        $imaptree = IMP_Imap_Tree::singleton();
        $imaptree->init();
    }

    if ($prefs->isDirty('mail_domain')) {
        $maildomain = preg_replace('/[^-\.a-z0-9]/i', '', $prefs->getValue('mail_domain'));
        $prefs->setValue('maildomain', $maildomain);
        if (!empty($maildomain)) {
            $_SESSION['imp']['maildomain'] = $maildomain;
        }
    }

    if ($prefs->isDirty('compose_popup')) {
        Horde::addInlineScript(array(
            'if (window.parent.frames.horde_menu) window.parent.frames.horde_menu.location.reload();'
        ));
    }
}

/* Make sure we are authenticated here. */
if (!Horde_Auth::isAuthenticated('imp')) {
    // TODO: Handle this more gracefully
    throw new Horde_Exception(_("Not authenticated to imp"));
}

/* Add necessary javascript files here (so they are added to the document
 * HEAD. */
switch ($group) {
case 'flags':
    Horde::addScriptFile('colorpicker.js', 'horde', true);
    Horde::addScriptFile('flagmanagement.js', 'imp', true);

    Horde::addInlineScript(array(
        'ImpFlagmanagement.new_prompt = ' . Horde_Serialize::serialize(_("Please enter the label for the new flag:"), Horde_Serialize::JSON, Horde_Nls::getCharset()),
        'ImpFlagmanagement.confirm_delete = ' . Horde_Serialize::serialize(_("Are you sure you want to delete this flag?"), Horde_Serialize::JSON, Horde_Nls::getCharset())
    ));
    break;
}
