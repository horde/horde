<?php
/**
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 */

 /**
  * Encryption tests.
  */
function showPassphrase($memo)
{
    global $notification;

    if (!($memo['body'] instanceof Mnemo_Exception)) {
        return false;
    }

    /* Check for secure connection. */
    $secure_check = Horde::isConnectionSecure();

    if ($memo['body']->getCode() == Mnemo::ERR_NO_PASSPHRASE) {
        if ($secure_check) {
            $notification->push(_("This note has been encrypted, please provide the password."), 'horde.message');
            return true;
        }
        $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
        $memo['body'] = '';
        return false;
    }

    if ($memo['body']->getCode() == Mnemo::ERR_DECRYPT) {
        if ($secure_check) {
            $notification->push(_("This note cannot be decrypted:") . ' ' . $memo['body']->getMessage(), 'horde.message');
            return true;
        }
        $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
        $memo['body'] = '';
        return false;
    }

    $notification->push($memo['body'], 'horde.error');
    $memo['body'] = '';

    return false;
}

function getShare($notepad)
{
    global $mnemo_shares, $notification;

    try {
        return $mnemo_shares->getShare($notepad);
    } catch (Horde_Share_Exception $e) {
        $notification->push(sprintf(_("There was an error viewing this notepad: %s"), $e->getMessage()), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }
}

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('mnemo');
$user = $registry->getAuth();

/* Redirect to the notepad view if no action has been requested. */
$memo_id = Horde_Util::getFormData('memo');
$memolist_id = Horde_Util::getFormData('memolist');
$actionID = Horde_Util::getFormData('actionID');
if (is_null($actionID)) {
    Horde::url('list.php', true)->redirect();
}

/* Run through the action handlers. */
switch ($actionID) {
case 'add_memo':
    /* Check permissions. */
    if ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') !== true &&
        $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') <= Mnemo::countMemos()) {
        Horde::permissionDeniedError(
            'mnemo',
            'max_notes',
            sprintf(_("You are not allowed to create more than %d notes."), $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes'))
        );
        Horde::url('list.php', true)->redirect();
    }

    /* Set up the note attributes. */
    if (empty($memolist_id)) {
        try {
            $memolist_id = Mnemo::getDefaultNotepad();
        } catch (Mnemo_Exception $e) {
            $notification->push($e);
            Horde::url('list.php', true)->redirect();
        }
    }
    if (!getShare($memolist_id)->hasPermission($user, Horde_Perms::EDIT)) {
        $notification->push(_("Access denied addings notes to this notepad."), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }
    $memo_id = null;
    $memo_body = '';
    $memo_encrypted = $show_passphrase = false;
    $storage = $injector->getInstance('Mnemo_Factory_Driver')->create();
    $memo_tags = array();

    $title = _("New Note");
    break;

case 'modify_memo':
    if (!getShare($memolist_id)->hasPermission($user, Horde_Perms::EDIT)) {
        $notification->push(_("Access denied editing note."), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }

    /* Check if a passphrase has been sent. */
    $passphrase = Horde_Util::getFormData('memo_passphrase');

    /* Get the current note. */
    try {
        $memo = Mnemo::getMemo($memolist_id, $memo_id, $passphrase);
    } catch (Horde_Exception_NotFound $e) {
        $notification->push(_("Note not found."), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }
    $storage = $injector->getInstance('Mnemo_Factory_Driver')
        ->create($memolist_id);

    /* Encryption tests. */
    $show_passphrase = showPassphrase($memo);

    /* Set up the note attributes. */
    $memo_body = $memo['body'];
    $memo_encrypted = $memo['encrypted'];
    $memo_tags = $memo['tags'];
    $title = sprintf(_("Edit: %s"), $memo['desc']);
    break;

case 'save_memo':
    /* Get the form values. */
    $memo_id = Horde_Util::getFormData('memo');
    $memo_body = Horde_Util::getFormData('memo_body');
    $memo_tags = Horde_Util::getFormData('memo_tags');
    $memolist_original = Horde_Util::getFormData('memolist_original');
    $notepad_target = Horde_Util::getFormData('notepad_target');
    $memo_passphrase = Horde_Util::getFormData('memo_passphrase');
    $memo_passphrase2 = Horde_Util::getFormData('memo_passphrase2');

    // Save the memolist in case saving fails Bug: 12855
    $memolist_id = $notepad_target;

    if (!getShare($notepad_target)->hasPermission($user, Horde_Perms::EDIT)) {
        $notification->push(
            _("Access denied saving note to this notepad."),
            'horde.error'
        );
    } elseif ($memo_passphrase != $memo_passphrase2) {
        $notification->push(_("The passwords don't match."), 'horde.error');
        if (empty($memo_id)) {
            $title = _("New Note");
        } else {
            $actionID = 'modify_memo';
            $memo = Mnemo::getMemo($memolist_original, $memo_id);
            if (!$memo || !isset($memo['memo_id'])) {
                $notification->push(_("Note not found."), 'horde.error');
                Horde::url('list.php', true)->redirect();
            }
            $title = sprintf(_("Edit: %s"), $memo['desc']);
            $show_passphrase = showPassphrase($memo);
            $memo_encrypted = $memo['encrypted'];
            $memolist_id = $memolist_original;
        }
        $storage = $injector->getInstance('Mnemo_Factory_Driver')
            ->create($memolist_original);
        break;
    } else {
        /* If $memo_id is set, we're modifying an existing note.  Otherwise,
         * we're adding a new note with the provided attributes. */
        if (!empty($memo_id)) {
            $storage = $injector->getInstance('Mnemo_Factory_Driver')
                ->create($memolist_original);
            if ($memolist_original != $notepad_target) {
                /* Moving the note to another notepad. */
                if (!getShare($memolist_original)->hasPermission($user, Horde_Perms::DELETE)) {
                    $notification->push(_("Access denied moving the note."), 'horde.error');
                } else {
                    $storage->move($memo_id, $notepad_target);
                    $storage = $injector->getInstance('Mnemo_Factory_Driver')
                        ->create($notepad_target);
                }
            }
            $memo_desc = $storage->getMemoDescription($memo_body);
            if (!strlen($memo_passphrase) &&
                Horde_Util::getFormData('memo_encrypt') == 'on') {
                $memo_passphrase = Mnemo::getPassphrase($memo_id);
            }
            try {
                $storage->modify(
                    $memo_id, $memo_desc, $memo_body,
                    $memo_tags, $memo_passphrase
                );
            } catch (Mnemo_Exception $e) {
                $haveError = $e->getMessage();
            }
        } else {
            /* Check permissions. */
            if ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') !== true &&
                $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') <= Mnemo::countMemos()) {
                Horde::url('list.php', true)->redirect();
            }
            /* Creating a new note. */
            $storage = $injector->getInstance('Mnemo_Factory_Driver')
                ->create($notepad_target);
            $memo_desc = $storage->getMemoDescription($memo_body);
            try {
                $memo_id = $storage->add(
                    $memo_desc, $memo_body, $memo_tags, $memo_passphrase
                );
            } catch (Mnemo_Exception $e) {
                $haveError = $e->getMessage();
            }
        }

        /* Check our results. */
        if (!empty($haveError)) {
            $notification->push(sprintf(_("There was an error saving the note: %s"), $haveError), 'horde.warning');
        } else {
            $notification->push(sprintf(_("Successfully saved \"%s\"."), $memo_desc), 'horde.success');
        }
    }

    /* Return to the notepad view. */
    Horde::url('list.php', true)->redirect();

case 'delete_memos':
    /* Delete the note if we're provided with a valid note ID. */
    if (!is_null($memo_id) && Mnemo::getMemo($memolist_id, $memo_id)) {
        if (getShare($memolist_id)->hasPermission($user, Horde_Perms::DELETE)) {
            $storage = $injector->getInstance('Mnemo_Factory_Driver')
                ->create($memolist_id);
            try {
                $storage->delete($memo_id);
                $notification->push(_("The note was deleted."), 'horde.success');
            } catch (Mnemo_Exception $e) {
                $notification->push(sprintf(_("There was an error removing the note: %s"), $e->getMessage()), 'horde.warning');
            }
        } else {
            $notification->push(_("Access denied deleting note."), 'horde.warning');
        }
    }

    /* Return to the notepad. */
    Horde::url('list.php', true)->redirect();

default:
    Horde::url('list.php', true)->redirect();
}

$view = $injector->createInstance('Horde_View');
$view->formInput = Horde_Util::formInput();
$view->id = $memo_id;
$view->listid = $memolist_id;
$view->modify = $actionID == 'modify_memo';
$view->passphrase = $show_passphrase;
$view->title = $title;
$view->url = Horde::url('memo.php');

if (!$view->modify || !$view->passphrase) {
    $injector->getInstance('Horde_Core_Factory_Imple')
        ->create('Mnemo_Ajax_Imple_TagAutoCompleter', array('id' => 'memo_tags'));
    $view->body = $memo_body;
    $view->count = sprintf(
        _("%s characters"),
        '<span id="mnemo-count">'
            . Horde_String::length(str_replace(array("\r", "\n"), '', $memo_body))
            . '</span>'
    );
    $view->encrypted = $memo_encrypted;
    $view->encryption = $storage->encryptionSupported();
    try {
        $view->help = Horde::callHook('description_help', array(), 'mnemo', '');
    } catch (Horde_Exception_HookNotSet $e) {
    }
    $view->loadingImg = Horde::img('loading.gif', _("Loading..."));
    $view->notepads = array();
    if (!$prefs->isLocked('default_notepad')) {
        foreach (Mnemo::listNotepads(false, Horde_Perms::SHOW) as $id => $notepad) {
            if (!$notepad->hasPermission($user, Horde_Perms::EDIT)) {
                continue;
            }
            $view->notepads[] = array(
                'id' => $id,
                'selected' => $id == $memolist_id,
                'label' => Mnemo::getLabel($notepad)
            );
        }
    }
    $view->tags = implode(', ', $memo_tags);
    if ($memo_id &&
        $mnemo_shares->getShare($memolist_id)->hasPermission($user, Horde_Perms::DELETE)) {
        $view->delete = Horde::url('memo.php')->add(array(
            'memo' => $memo_id,
            'memolist' => $memolist_id,
            'actionID' => 'delete_memos'
        ));
    }
}

$page_output->addScriptFile('memo.js');
$page_output->header(array(
    'title' => $title
));
$notification->notify();
echo $view->render('memo/memo');
$page_output->footer();
