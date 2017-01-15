<?php

/* Variables used in core javascript files. */
$code['conf'] = array(
    'URI_AJAX' => (string)$GLOBALS['registry']->getServiceLink('ajax', 'nag'),
    'date_format' => Horde_Core_Script_Package_Datejs::translateFormat(
        $GLOBALS['prefs']->getValue('date_format_mini')
    ),
    'tasklist_info_url' => (string)Horde::url('tasklists/info.php'),
    'time_format' => $GLOBALS['prefs']->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
);

/* Gettext strings used in core javascript files. */
$code['text'] = array(
    'close' => _("Close"),
);

$GLOBALS['page_output']->addInlineJsVars(array(
    'var Nag' => $code
), array('top' => true));
