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
        'mailbox_selectonlyone' => _("You must select only one message for this action."),
        'yes' => _("Yes"),
        'no' => _("No"),

        /* Strings used in contacts.js */
        'contacts_closed' => _("The message being composed has been closed."),
        'contacts_select' => _("You must select an address first."),

        /* Strings used in folders.js */
        'folders_select' => _("Please select a mailbox before you perform this action."),
        'folders_oneselect' => _("Only one mailbox should be selected for this action."),
        'folders_subfolder1' => _("You are creating a subfolder to"),
        'folders_subfolder2' => _("Please enter the name of the new mailbox:"),
        'folders_toplevel' => _("You are creating a top-level mailbox.") . "\n" . _("Please enter the name of the new mailbox:"),
        'folders_download1' => _("All messages in the following mailbox(es) will be downloaded into one MBOX file:"),
        'folders_download2' => _("This may take some time. Are you sure you want to continue?"),
        'folders_rename1' => _("You are renaming the mailbox:"),
        'folders_rename2' => _("Please enter the new name:"),
        'folders_no_rename' => _("This mailbox may not be renamed:"),

        /* Strings used in imp.js */
        'popup_block' => _("A popup window could not be opened. Perhaps you have set your browser to block popup windows?"),

        /* Strings used in login.js */
        'login_username' => _("Please provide your username."),
        'login_password' => _("Please provide your password."),

        /* Strings used in multiple pages. */
        'moveconfirm' => _("Are you sure you want to move the message(s)? (Some message information might get lost, like message headers, text formatting or attachments!)"),
        'spam_report' => _("Are you sure you wish to report this message as spam?"),
        'notspam_report' => _("Are you sure you wish to report this message as innocent?"),
        'newmbox' => _("You are copying/moving to a new mailbox.") . "\n" . _("Please enter a name for the new mailbox:") . "\n",
        'target_mbox' => _("You must select a target mailbox first."),
    )
);

Horde::addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
