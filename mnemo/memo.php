<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Mnemo
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
            $notification->push(_("This note has been encrypted, please provide the password below"), 'horde.message');
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

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

/* Redirect to the notepad view if no action has been requested. */
$memo_id = Horde_Util::getFormData('memo');
$memolist_id = Horde_Util::getFormData('memolist');
$actionID = Horde_Util::getFormData('actionID');
if (is_null($actionID)) {
    Horde::url('list.php', true)->redirect();
}

/* Load category manager. */
$cManager = new Horde_Prefs_CategoryManager();

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
            $notification->push($memolist_id, 'horde.error');
        }
    }
    $memo_id = null;
    $memo_body = '';
    $memo_category = '';
    $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();

    $title = _("New Note");
    break;

case 'modify_memo':
    /* Check if a passphrase has been sent. */
    $passphrase = Horde_Util::getFormData('memo_passphrase');

    /* Get the current note. */
    $memo = Mnemo::getMemo($memolist_id, $memo_id, $passphrase);
    if (!$memo || !isset($memo['memo_id'])) {
        $notification->push(_("Note not found."), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }
    $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($memolist_id);

    /* Encryption tests. */
    $show_passphrase = showPassphrase($memo);

    /* Set up the note attributes. */
    $memo_body = $memo['body'];
    $memo_category = $memo['category'];
    $memo_encrypted = $memo['encrypted'];
    $title = sprintf(_("Edit: %s"), $memo['desc']);
    break;

case 'save_memo':
    /* Get the form values. */
    $memo_id = Horde_Util::getFormData('memo');
    $memo_body = Horde_Util::getFormData('memo_body');
    $memo_category = Horde_Util::getFormData('memo_category');
    $memolist_original = Horde_Util::getFormData('memolist_original');
    $notepad_target = Horde_Util::getFormData('notepad_target');
    $memo_passphrase = Horde_Util::getFormData('memo_passphrase');
    $memo_passphrase2 = Horde_Util::getFormData('memo_passphrase2');

    try {
        $share = $mnemo_shares->getShare($notepad_target);
    } catch (Horde_Share_Exception $e) {
        throw new Mnemo_Exception($e);
    }

    if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied saving note to %s."), $share->get('name')), 'horde.error');
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
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($memolist_original);
        break;
    } else {
        if ($new_category = Horde_Util::getFormData('new_category')) {
            $new_category = $cManager->add($new_category);
            $memo_category = $new_category ? $new_category : '';
        }
        if (!strlen($memo_passphrase)) {
            $memo_passphrase = Mnemo::getPassphrase($memo_id);
        }

        /* If $memo_id is set, we're modifying an existing note.  Otherwise,
         * we're adding a new note with the provided attributes. */
        if (!empty($memo_id)) {
            $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($memolist_original);
            if ($memolist_original != $notepad_target) {
                /* Moving the note to another notepad. */
                try {
                    $share = $mnemo_shares->getShare($memolist_original);
                } catch (Horde_Share_Exception $e) {
                    throw new Mnemo_Exception($e);
                }
                if ($share->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
                    try {
                        $share = $mnemo_shares->getShare($notepad_target);
                    } catch (Horde_Share_Exception $e) {
                        throw new Mnemo_Exception($e);
                    }
                    if ($share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                        $result = $storage->move($memo_id, $notepad_target);
                        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad_target);
                    } else {
                        $notification->push(_("Access denied moving the note."), 'horde.error');
                    }
                } else {
                    $notification->push(_("Access denied moving the note."), 'horde.error');
                }
            }
            $memo_desc = $storage->getMemoDescription($memo_body);
            if (empty($memo_passphrase) &&
                Horde_Util::getFormData('memo_encrypt') == 'on') {
                $memo_passphrase = Mnemo::getPassphrase($memo_id);
            }
            try {
                $storage->modify($memo_id, $memo_desc, $memo_body, $memo_category, $memo_passphrase);
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
            $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad_target);
            $memo_desc = $storage->getMemoDescription($memo_body);
            try {
                $result = $memo_id = $storage->add($memo_desc, $memo_body,
                                                   $memo_category, null,
                                                   $memo_passphrase);
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
    $memo_id = Horde_Util::getFormData('memo');
    $memolist_id = Horde_Util::getFormData('memolist');

    if (!is_null($memo_id) && Mnemo::getMemo($memolist_id, $memo_id)) {
        try {
            $share = $mnemo_shares->getShare($memolist_id);
        } catch (Horde_Share_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        if ($share->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
            $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($memolist_id);
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

$notepads = Mnemo::listNotepads(false, Horde_Perms::EDIT);
$page_output->header(array(
    'title' => $title
));
echo Mnemo::menu();
$notification->notify();
require MNEMO_TEMPLATES . '/memo/memo.inc';
$page_output->footer();
