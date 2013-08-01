<?php

/* Variables used in core javascript files. */
$code['conf'] = array(
    'URI_AJAX' => (string)$GLOBALS['registry']->getServiceLink('ajax', 'nag'),
    'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                                 array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                                 Horde_Nls::getLangInfo(D_FMT)),
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
