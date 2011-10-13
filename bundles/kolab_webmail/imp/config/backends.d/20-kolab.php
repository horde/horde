<?php

$servers = array();

$servers['kolab'] = array(
    'name' => 'Kolab Cyrus IMAP Server',
    'hostspec' => $GLOBALS['injector']->getInstance('Horde_Kolab_Session')->getImapServer(),
    'hordeauth' => 'full',
    'protocol' => 'imap',
    'port' => $GLOBALS['conf']['kolab']['imap']['port'],
    'secure' => true,
    'maildomain' => $GLOBALS['conf']['kolab']['imap']['maildomain'],
    'quota' => array(
        'driver' => 'imap',
        'params' => array(
            'hide_quota_when_unlimited' => true,
            'unit' => 'MB'
        )
    ),
    'acl' => true,
    'cache' => false,
);
