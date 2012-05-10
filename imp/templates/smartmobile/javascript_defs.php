<?php
/**
 * IMP Mobile base JS file.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

$code = array();

/* Variables. */
$code['conf'] = array_filter(array(
    'allow_folders' => $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS),
    'disable_compose' => !IMP::canCompose(),
    'innocent_spammbox' => !empty($GLOBALS['conf']['notspam']['spamfolder']),
    'mailbox_return' => $GLOBALS['prefs']->getValue('mailbox_return'),
    'pop3' => intval($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->pop3),
    'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_QUICKSEARCH),
    'spam_mbox' => IMP_Mailbox::formTo($GLOBALS['prefs']->getValue('spam_folder')),
    'spam_spammbox' => !empty($GLOBALS['conf']['spam']['spamfolder'])
));

/* Gettext strings. */
$code['text'] = array(
    'confirm' => array(
        'text' => array(
            'delete' => _("Are you sure you want to delete this message?"),
            'innocent' => _("Are you sure you wish to report this message as innocent?"),
            'spam' => _("Are you sure you wish to report this message as spam?")
        ),
        'action' => array(
            'delete' => _("Delete"),
            'innocent' => _("Report as Innocent"),
            'spam' => _("Report as Spam")
        ),
    ),
    'copy' => _("Copy"),
    'nav' => _("%d to %d of %d"),
    'new_message' => _("New Message"),
    'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
);

$page_output->addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
