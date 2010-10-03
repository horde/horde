<?php

$charset = 'UTF-8';

/* Variables used in core javascript files. */
$var = array(
    'URI_AJAX' => Horde::getServiceLink('ajax', 'gollem')->url,
    'empty_input' => intval($GLOBALS['browser']->hasQuirk('empty_file_input_value'))
);

/* Gettext strings used in core javascript files. */
$gettext = array_map('addslashes', array(
    /* Strings used in login.js */
    'login_username' => _("Please provide your username."),
    'login_password' => _("Please provide your password."),

    /* Strings used in manager.js */
    'select_item' => _("Please select an item before this action."),
    'delete_confirm_1' => _("The following items will be permanently deleted:"),
    'delete_confirm_2' => _("Are you sure?"),
    'delete_recurs_1' => _("The following item(s) are folders:"),
    'delete_recurs_2' => _("Are you sure you wish to continue?"),
    'specify_upload' => _("Please specify at least one file to upload."),
    'file' => _("File"),

    /* Strings used in selectlist.js */
    'opener_window' => _("The original opener window has been closed. Exiting."),
));

?>
<script type="text/javascript">//<![CDATA[
var GollemVar = <?php echo Horde_Serialize::encode($var, Horde_Serialize::SERIALIZE_JSON, $charset) ?>;
var GollemText = <?php echo Horde_Serialize::encode($gettext, Horde_Serialize::SERIALIZE_JSON, $charset) ?>;
//]]></script>
