<?php
$conf['storage']['default_domain'] = $GLOBALS['conf']['kolab']['imap']['maildomain'];
$conf['reminder']['server_name'] = $GLOBALS['conf']['kolab']['imap']['maildomain'];
$conf['reminder']['from_addr'] = 'postmaster@' . $GLOBALS['conf']['kolab']['imap']['maildomain'];
