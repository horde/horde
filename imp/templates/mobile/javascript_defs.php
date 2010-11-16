<?php
/**
 * IMP Mobile base JS file.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$code = $flags = array();

/* Generate flag array. */
foreach ($GLOBALS['injector']->getInstance('IMP_Imap_Flags')->getList(array('fgcolor' => true)) as $val) {
    $flags[$val['flag']] = array_filter(array(
        'b' => isset($val['b']) ? $val['b'] : null,
        'c' => $val['c'],
        'f' => $val['f'],
        'l' => $val['l'],
        'n' => isset($val['n']) ? $val['n'] : null,
        // Indicate if this is a user *P*ref flag
        'p' => intval($val['t'] == 'imapp'),
        // Indicate if this is a flag that can be *S*earched for
        's' => intval(in_array($val['t'], array('imapp', 'imapu')))
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

    'IDX_SEP' => IMP_Dimp::IDX_SEP,
    'SESSION_ID' => defined('SID') ? SID : '',

    // Other variables
    'flags' => $flags,
    /* Needed to maintain flag ordering. */
    'flags_o' => array_keys($flags),
    'refresh_time' => intval($GLOBALS['prefs']->getValue('refresh_time')),
));

/* Gettext strings used in core javascript files. */
$code['text'] = array(
);

Horde::addInlineJsVars(array(
    'var IMP' => $code
), array('top' => true));
