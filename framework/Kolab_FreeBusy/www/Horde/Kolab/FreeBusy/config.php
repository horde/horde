<?php
/**
 * This file provides configuration settings for both the freebusy.php
 * and the pfb.php scripts.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <p@rdus.de>
 * @author  Thomas Arendsen Hein <thomas@intevation.de>
 * @package Kolab_FreeBusy
 */

$conf = array();

/* Horde::Log configuration */
$conf['log']['enabled']          = true;
$conf['log']['priority']         = PEAR_LOG_DEBUG; // Leave this on DEBUG for now. We just restructured the package...
$conf['log']['type']             = 'file';
$conf['log']['name']             = '/kolab/var/kolab-freebusy/log/freebusy.log';
$conf['log']['ident']            = 'Kolab Free/Busy';
$conf['log']['params']['append'] = true;

/* PHP error logging */
ini_set('error_log', '/kolab/var/kolab-freebusy/log/php-error.log');

/* Horde::Kolab::LDAP configuration */
$conf['kolab']['ldap']['server'] = 'example.com';
$conf['kolab']['ldap']['basedn'] = 'dc=example,dc=com';
$conf['kolab']['ldap']['phpdn']  = 'cn=nobody,cn=internal,dc=example,dc=com';
$conf['kolab']['ldap']['phppw']  = 'xyz';

/* Horde::Kolab::IMAP configuration */
$conf['kolab']['imap']['server']   = 'example.com';
$conf['kolab']['imap']['port']     = 143;
$conf['kolab']['imap']['protocol'] = 'notls/readonly';

/* Horde::Auth configuration */
$conf['auth']['params']['login_block'] = 0;
$conf['auth']['checkbrowser']          = false;
$conf['auth']['checkip']               = false;

/* Kolab::Freebusy configuration */

/* Should we redirect using a Location header, if the user is not local? If this
 * is false we silently download the file ourselves and output it so that it
 * looks as though the free/busy information is coming from us.
 */
$conf['fb']['redirect']     = false;

/* What is the address of the current server where the calendar data is stored?
 * This is also used as the LDAP server address where user objects reside.
 */
$conf['kolab']['freebusy']['server']  = 'https://example.com/freebusy';

/* What is our default mail domain? This is used if any users do not have
 * '@domain' specified after their username as part of their email address.
 */
$conf['fb']['email_domain'] = 'example.com';

/* Location of the cache files */
$conf['fb']['cache_dir']    = '/kolab/var/kolab-freebusy/cache';

/* What db type to use for the freebusy caches */
$conf['fb']['dbformat']     = 'db4';

/* Should we send a Content-Type header, indicating what the mime type of the
 * resulting VFB file is?
 */
$conf['fb']['send_content_type'] = false;

/* Should we send a Content-Length header, indicating how large the resulting
 * VFB file is?
 */
$conf['fb']['send_content_length'] = false;

/* Should we send a Content-Disposition header, indicating what the name of the
 * resulting VFB file should be?
 */
$conf['fb']['send_content_disposition'] = false;

/* Should we use ACLs or does everybody get full rights? DO NOT set
 * this to false if you don't know what you are doing. Your free/busy
 * service should not be visible to any outside networks when
 * disabling the use of ACL settings.
 */
$conf['fb']['use_acls'] = true;

/* Are there remote servers on which users have additional (shared)
 * folders? In that case free/busy information should also be fetched
 * from these servers.
 *
 * Add them like this:
 *
 * array('remote1.example.com', 'remote2.example.com')
 */
$conf['fb']['remote_servers'] = array();

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//
// If you modify this file, please do not forget to also modify the
// template in kolabd!
//
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

// DEBUGGING
// =========
//
// Activate this to see the log messages on the screen
// $conf['log']['type'] = 'display';
//
// Activate this to see the php messages on the screen
// ini_set('display_errors', 1);
//
// Both settings will disrupt header delivery (which should not cause a
// problem).
