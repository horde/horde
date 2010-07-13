<?php
/**
 * IMP base JS file.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$code = array(
/* Variables used in core javascript files. */
    'conf' => array(
        'pop3' => intval((isset($_SESSION['imp']) && ($_SESSION['imp']['protocol'] == 'pop'))),
        'fixed_folders' => empty($GLOBALS['conf']['server']['fixed_folders'])
            ? array()
            : $GLOBALS['conf']['server']['fixed_folders'],
    ),

    /* Gettext strings used in core javascript files. */
    'text' => array(
        /* Strings used in compose.js */
        'compose_cancel' => _("Cancelling this message will permanently discard its contents.") . "\n" . _("Are you sure you want to do this?"),
        'compose_discard' => _("Doing so will discard this message permanently."),
        'compose_recipient' => _("You must specify a recipient."),
        'compose_nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
        'compose_file' => _("File"),
        'compose_attachment' => _("Attachment"),
        'compose_inline' => _("Inline"),

        /* Strings used in mailbox.js */
        'mailbox_submit' => _("You must select at least one message first."),
        'mailbox_delete' => _("Are you sure you wish to PERMANENTLY delete these messages?"),
        'mailbox_delete_all' => _("Are you sure you wish to delete all mail in this mailbox?"),
        'mailbox_delete_vfolder' => _("Are you sure you want to delete this Virtual Folder Definition?"),
        'mailbox_selectone' => _("You must select at least one message first."),
        'yes' => _("Yes"),
        'no' => _("No"),

        /* Strings used in contacts.js */
        'contacts_select' => _("You must select an address first."),
        'contacts_closed' => _("The message being composed has been closed."),
        'contacts_called' => _("This window must be called from a compose window."),

        /* Strings used in folders.js */
        'folders_select' => _("Please select a folder before you perform this action."),
        'folders_oneselect' => _("Only one folder should be selected for this action."),
        'folders_subfolder1' => _("You are creating a sub-folder to"),
        'folders_subfolder2' => _("Please enter the name of the new folder:"),
        'folders_toplevel' => _("You are creating a top-level folder.") . "\n" . _("Please enter the name of the new folder:"),
        'folders_download1' => _("All messages in the following folder(s) will be downloaded into one MBOX file:"),
        'folders_download2' => _("This may take some time. Are you sure you want to continue?"),
        'folders_rename1' => _("You are renaming the folder:"),
        'folders_rename2' => _("Please enter the new name:"),
        'folders_no_rename' => _("This folder may not be renamed:"),

        /* Strings used in imp.js */
        'popup_block' => _("A popup window could not be opened. Perhaps you have set your browser to block popup windows?"),

        /* Strings used in login.js */
        'login_username' => _("Please provide your username."),
        'login_password' => _("Please provide your password."),

        /* Strings used in multiple pages. */
        'moveconfirm' => _("Are you sure you want to move the message(s)? (Some message information might get lost, like message headers, text formatting or attachments!)"),
        'spam_report' => _("Are you sure you wish to report this message as spam?"),
        'notspam_report' => _("Are you sure you wish to report this message as innocent?"),
        'newfolder' => _("You are copying/moving to a new folder.") . "\n" . _("Please enter a name for the new folder:") . "\n",
        'target_mbox' => _("You must select a target mailbox first."),
    )
);

Horde::addInlineScript(array(
    'var IMP = ' . Horde_Serialize::serialize($code, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
), null, true);
