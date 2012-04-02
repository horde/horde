<?php

/* Variables used in core javascript files. */
$code['conf'] = array(
    'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                                 array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                                 Horde_Nls::getLangInfo(D_FMT)),
    'time_format' => $GLOBALS['prefs']->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
);

$GLOBALS['page_output']->addInlineJsVars(array(
    'var Nag' => $code
), array('top' => true));
