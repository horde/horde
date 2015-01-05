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
    $notification->push(_("You do not have permission to view this notepad."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* If the requested note doesn't exist, display an error message. */
if (!$memo || !isset($memo['memo_id'])) {
    $notification->push(_("Note not found."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* Get the note's history. */
$userId = $GLOBALS['registry']->getAuth();

/* Encryption tests. */
$show_passphrase = false;
if ($memo['body'] instanceof Mnemo_Exception) {
    /* Check for secure connection. */
    $secure_check = Horde::isConnectionSecure();
    if ($memo['body']->getCode() == Mnemo::ERR_NO_PASSPHRASE) {
        if ($secure_check) {
            $notification->push(_("This note has been encrypted, please provide the password."), 'horde.message');
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

$share = $mnemo_shares->getShare($memolist_id);
$url = Horde::url('memo.php')
    ->add(array('memo' => $memo_id, 'memolist' => $memolist_id));
$body = $injector->getInstance('Horde_Core_Factory_TextFilter')
    ->filter(
        $memo['body'],
        'text2html',
        array('parselevel' => Horde_Text_Filter_Text2html::MICRO)
    );

$view = $injector->createInstance('Horde_View');
$view->assign($memo);
try {
    $view->body = Horde::callHook(
        'format_description',
        array($body),
        'mnemo',
        $body
    );
} catch (Horde_Exception_HookNotSet $e) {
    $view->body = $body;
}
$view->id = $memo_id;
$view->listid = $memolist_id;
$view->passphrase = $show_passphrase;
$view->pdfurl = Horde::url('note/pdf.php')
    ->add(array('note' => $memo_id, 'notepad' => $memolist_id));
$view->tags = implode(', ', $memo['tags']);
if ($share->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
    $view->delete = Horde::widget(array(
        'url' => $url->add('actionID', 'delete_memos'),
        'class' => 'mnemo-delete',
        'id' => 'mnemo-delete',
        'title' => _("_Delete")
    ));
}
if ($share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $view->edit = Horde::widget(array(
        'url' => $url->add('actionID', 'modify_memo'),
        'class' => 'mnemo-edit',
        'title' => _("_Edit")
    ));
}
if (isset($memo['created'])) {
    $view->created = $memo['created']->strftime(
        $prefs->getValue('date_format')
    )
    . ' ' . $memo['created']->format(
        $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a'
    );
}
if (isset($memo['modified'])) {
    $view->modified = $memo['modified']->strftime(
        $prefs->getValue('date_format')
    )
    . ' ' . $memo['modified']->format(
        $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a'
    );
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->addScriptFile('view.js');
$page_output->addInlineJsVars(
    array('Mnemo_View.confirm' => _("Really delete this note?"))
);
$page_output->header(array(
    'title' => $memo ? $memo['desc'] : _("Note Details")
));
$notification->notify();
echo $view->render('view/view');
$page_output->footer();
