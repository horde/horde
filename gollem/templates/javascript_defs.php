<?php

/* Variables used in core javascript files. */
$var = array(
    'empty_input' => intval($GLOBALS['browser']->hasQuirk('empty_file_input_value'))
);

/* Gettext strings used in core javascript files. */
$gettext = array_map('addslashes', array(
    /* Strings used in login.js */
    'login_username' => _("Please provide your username."),
    'login_password' => _("Please provide your password."),

    /* Strings used in manager.js */
    'change_directory' => _("Change Folder"),
    'create_folder' => _("Create Folder"),
    'delete_confirm_1' => _("The following items will be permanently deleted:"),
    'delete_confirm_2' => _("Are you sure?"),
    'delete_recurs_1' => _("The following item(s) are folders:"),
    'delete_recurs_2' => _("Are you sure you wish to continue?"),
    'file' => _("File"),
    'permissions' => _("Permissions"),
    'rename' => _("Rename"),
    'select_item' => _("Please select an item before this action."),
    'specify_upload' => _("Please specify at least one file to upload."),

    /* Strings used in selectlist.js */
    'opener_window' => _("The original opener window has been closed. Exiting."),
));

$GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineJsVars(array(
    'var GollemVar' => $var,
    'var GollemText' => $gettext
), array('top' => true));
