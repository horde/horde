<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

/* Check if a passphrase has been sent. */
$passphrase = Horde_Util::getFormData('memo_passphrase');

/* We can either have a UID or a memo id and a notepad. Check for UID
 * first. */
if ($uid = Horde_Util::getFormData('uid')) {
    $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
    try {
        $memo = $storage->getByUID($uid, $passphrase);
    } catch (Mnemo_Exception $e) {
        Horde::url('list.php', true)->redirect();
    }
    $memo_id = $memo['memo_id'];
    $memolist_id = $memo['memolist_id'];
} else {
    /* If we aren't provided with a memo and memolist, redirect to
     * list.php. */
    $memo_id = Horde_Util::getFormData('memo');
    $memolist_id = Horde_Util::getFormData('memolist');
    if (!isset($memo_id) || !$memolist_id) {
        Horde::url('list.php', true)->redirect();
    }

    /* Get the current memo. */
    $memo = Mnemo::getMemo($memolist_id, $memo_id, $passphrase);
}
try {
    $share = $GLOBALS['mnemo_shares']->getShare($memolist_id);
} catch (Horde_Share_Exception $e) {
    $notification->push(sprintf(_("There was an error viewing this notepad: %s"), $e->getMessage()), 'horde.error');
    Horde::url('list.php', true)->redirect();
}
if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(sprintf(_("You do not have permission to view the notepad %s."), $share->get('name')), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* If the requested note doesn't exist, display an error message. */
if (!$memo || !isset($memo['memo_id'])) {
    $notification->push(_("Note not found."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* Get the note's history. */
$userId = $GLOBALS['registry']->getAuth();
$createdby = '';
$modifiedby = '';
if (!empty($memo['uid'])) {
    try {
        $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory('mnemo:' . $memolist_id . ':' . $memo['uid']);
        foreach ($log as $entry) {
            switch ($entry['action']) {
            case 'add':
                $created = $entry['ts'];
                if ($userId != $entry['who']) {
                    $createdby = sprintf(_("by %s"), Mnemo::getUserName($entry['who']));
                } else {
                    $createdby = _("by me");
                }
                break;

            case 'modify':
                $modified = $entry['ts'];
                if ($userId != $entry['who']) {
                    $modifiedby = sprintf(_("by %s"), Mnemo::getUserName($entry['who']));
                } else {
                    $modifiedby = _("by me");
                }
                break;
            }
        }
    } catch (Horde_Exception $e) {
    }
}

/* Encryption tests. */
$show_passphrase = false;
if ($memo['body'] instanceof Mnemo_Exception) {
    /* Check for secure connection. */
    $secure_check = Horde::isConnectionSecure();
    if ($memo['body']->getCode() == Mnemo::ERR_NO_PASSPHRASE) {
        if ($secure_check) {
            $notification->push(_("This note has been encrypted, please provide the password below"), 'horde.message');
            $show_passphrase = true;
        } else {
            $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
            $memo['body'] = '';
        }
    } elseif ($memo['body']->getCode() == Mnemo::ERR_DECRYPT) {
        if ($secure_check) {
            $notification->push(_("This note cannot be decrypted:") . ' ' . $memo['body']->getMessage(), 'horde.message');
            $show_passphrase = true;
        } else {
            $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
            $memo['body'] = '';
        }
    } else {
        $notification->push($memo['body'], 'horde.error');
        $memo['body'] = '';
    }
}

/* Set the page title to the current note's name, if it exists. */
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $memo ? $memo['desc'] : _("Note Details")
));
$notification->notify();
require MNEMO_TEMPLATES . '/view/memo.inc';
$page_output->footer();
