<?php

$backends = array();

$backends['kolab'] = array(
    'transport' => 'timsieved',
    'hordeauth' => 'full',
    'params' => array(
        'hostspec' => 'localhost', //@todo: Kolab::getServer('imap'),
        'logintype' => 'PLAIN',
        'usetls' => true,
        'port' => $GLOBALS['conf']['kolab']['imap']['sieveport'],
        'scriptname' => 'kmail-vacation.siv'
    ),
    'script' => 'sieve',
    'scriptparams' => array(),
    'shares' => false
);
