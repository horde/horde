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
    'URI_COMPOSE' => strval(Horde::url('compose-dimp.php')->setRaw(true)->add('ajaxui', 1)),
    'URI_DIMP' => strval(Horde::url('index-dimp.php')),
    'URI_MESSAGE' => strval(Horde::url('message-dimp.php')->setRaw(true)->add('ajaxui', 1)),
    'URI_PREFS_IMP' => strval(Horde::getServiceLink('prefs', 'imp')->setRaw(true)->add('ajaxui', 1)),
    'URI_SEARCH' => strval(Horde::url('search.php')),
    'URI_VIEW' => strval(Horde::url('view.php')),

    'SESSION_ID' => defined('SID') ? SID : '',

    // Other variables
    'flags' => $flags,
    /* Needed to maintain flag ordering. */
    'flags_o' => array_keys($flags),
    'pop3' => intval($GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->pop3),
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
));

/* Gettext strings used in core javascript files. */
$code['text'] = array(
    'more_messages' => _("%d more messages..."),
);

Horde::addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
