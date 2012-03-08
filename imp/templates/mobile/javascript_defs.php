<?php
/**
 * IMP Mobile base JS file.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

$code = $flags = array();

/* Generate flag array. */
foreach ($GLOBALS['injector']->getInstance('IMP_Flags')->getList() as $val) {
    $flags[$val->id] = array_filter(array(
        'b' => $val->bgdefault ? null : $val->bgcolor,
        'c' => $val->css,
        'f' => $val->fgcolor,
        'i' => $val->css ? null : $val->cssicon,
        'l' => $val->label,
        // Indicate if this is a flag that can be *s*earched for
        's' => intval($val instanceof IMP_Flag_Imap),
        // Indicate if this is a *u*ser flag
        'u' => intval($val instanceof IMP_Flag_User)
    ));
}

/* Variables used in core javascript files. */
$code['conf'] = array_filter(array(
    // URL variables
    'URI_AJAX' => Horde::getServiceLink('ajax', 'imp')->url,

    // Other variables
    'allow_folders' => $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS),
    'disable_compose' => !IMP::canCompose(),
    'flags' => $flags,
    /* Needed to maintain flag ordering. */
    'flags_o' => array_keys($flags),
    'ham_spammbox' => !empty($GLOBALS['conf']['notspam']['spamfolder']),
    'mailbox_return' => $GLOBALS['prefs']->getValue('mailbox_return'),
    'pop3' => intval($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->pop3),
    'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_QUICKSEARCH),
    'refresh_time' => intval($GLOBALS['prefs']->getValue('refresh_time')),
    'sort' => array(
        'sequence' => array(
            't' => '',
            'v' => Horde_Imap_Client::SORT_SEQUENCE
        ),
        'from' => array(
            't' => _("From"),
            'v' => Horde_Imap_Client::SORT_FROM
        ),
        'to' => array(
            't' => _("To"),
            'v' => Horde_Imap_Client::SORT_TO
        ),
        'subject' => array(
            't' => _("Subject"),
            'v' => Horde_Imap_Client::SORT_SUBJECT
        ),
        'thread' => array(
            't' => _("Thread"),
            'v' => Horde_Imap_Client::SORT_THREAD
        ),
        'date' => array(
            't' => _("Date"),
            'v' => IMP::IMAP_SORT_DATE
        ),
        'size' => array(
            't' => _("Size"),
            'v' => Horde_Imap_Client::SORT_SIZE
        )
    ),
    'spam_mbox' => IMP_Mailbox::formTo($GLOBALS['prefs']->getValue('spam_folder')),
    'spam_spammbox' => !empty($GLOBALS['conf']['spam']['spamfolder']),
));

/* Gettext strings used in core javascript files. */
$code['text'] = array(
    'confirm' => array(
        'text' => array(
            'delete' => _("Are you sure you want to delete this message?"),
            'spam'   => _("Are you sure you wish to report this message as spam?"),
            'ham'    => _("Are you sure you wish to report this message as innocent?")),
        'action' => array(
            'delete' => _("Delete"),
            'spam'   => _("Report as Spam"),
            'ham'    => _("Report as Innocent")),
    ),
    'copy' => _("Copy"),
    'more_messages' => _("%d more messages..."),
    'move' => _("Move"),
    'nav' => _("%d to %d of %d"),
    'new_message' => _("New Message"),
    'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
);

Horde::addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
