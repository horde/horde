<?php
/**
 * IMP base JS file.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

$code = array(
/* Variables used in core javascript files. */
    'conf' => array(
        'pop3' => intval($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->pop3),
        'fixed_mboxes' => empty($GLOBALS['conf']['server']['fixed_folders'])
            ? array()
            : $GLOBALS['conf']['server']['fixed_folders'],
    ),

    /* Gettext strings used in core javascript files. */
    'text' => array(
        /* Strings used in imp.js */
        'popup_block' => _("A popup window could not be opened. Perhaps you have set your browser to block popup windows?"),

        /* Strings used in multiple pages. */
        'moveconfirm' => _("Are you sure you want to move the message(s)? (Some message information might get lost, like message headers, text formatting or attachments!)"),
        'spam_report' => _("Are you sure you wish to report this message as spam?"),
        'notspam_report' => _("Are you sure you wish to report this message as innocent?"),
        'newmbox' => _("You are copying/moving to a new mailbox.") . "\n" . _("Please enter a name for the new mailbox:") . "\n",
        'target_mbox' => _("You must select a target mailbox first."),
    )
);

$GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
