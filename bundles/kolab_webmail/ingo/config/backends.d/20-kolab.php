<?php

$backends = array();

$backends['kolab'] = array(
    'disabled' => false,
    'transport' => 'timsieved',
    'hordeauth' => 'full',
    'params' => array(
        'hostspec' => $GLOBALS['injector']->getInstance('Horde_Kolab_Session')->getImapServer(),
        'logintype' => 'PLAIN',
        'usetls' => true,
        'port' => $GLOBALS['conf']['kolab']['imap']['sieveport'],
        'scriptname' => 'kmail-vacation.siv',
        'debug' => false,
    ),
    'script' => 'sieve',
    'scriptparams' => array(),
    'shares' => false
);
