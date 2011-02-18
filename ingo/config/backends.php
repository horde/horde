<?php
/**
 * Ingo works purely on a preferred mechanism for server selection. There are
 * a number of properties that you can set for each backend:
 *
 * IMPORTANT: Local overrides should be placed in backends.local.php, or
 * backends-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 *
 * disabled: (boolean) If true, the config entry is disabled.
 *
 * transport: (string) The Ingo_Transport driver to use to store the script on
 *            the backend server. Valid options:
 *            - 'ldap':      LDAP server
 *            - 'null':      No backend server
 *            - 'timsieved': Timsieved (managesieve) server
 *            - 'vfs':       Use Horde VFS
 *
 * preferred: (string) This is the field that is used to choose which server
 *            is used. The value for this field may be a single string or an
 *            array of strings containing the hostnames to use with this
 *            server.
 *
 * hordeauth: (mixed) One of the following:
 *            - true:   Ingo will attempt to use the user's existing
 *                      credentials (the username/password they used to log in
 *                      to Horde) to login to this source. (DEFAULT)
 *            - 'full': The username will be used unmodified.
 *
 * params: (array) An array containing any additional information that the
 *         Ingo_Transport class needs.
 *
 * script: (string) The type of Ingo_Script driver this server uses.
 *         Valid options:
 *         - 'imap':     IMAP client side filtering (POP3 servers NOT
 *                       supported)
 *         - 'maildrop': Maildrop scripts
 *         - 'procmail': Procmail scripts
 *         - 'sieve':    Sieve scripts
 *
 * scriptparams: (array) An array containing any additional information that
 *               the Ingo_Script driver needs.
 *
 * shares: (boolean) Some drivers support sharing filter rules with other
 *         users. Users can then configure filters for each other if they
 *         give them permissions to do so. If you want to enable this feature,
 *         you need to set this parameter to true.
 */

/* IMAP Example */
$backends['imap'] = array(
    // ENABLED by default
    'disabled' => false,
    'transport' => 'null',
    'hordeauth' => true,
    'params' => array(),
    'script' => 'imap',
    'scriptparams' => array(),
    'shares' => false
);

/* Maildrop Example */
$backends['maildrop'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'vfs',
    'hordeauth' => true,
    'params' => array(
        // Hostname of the VFS server
        'hostspec' => 'localhost',
        // Name of the maildrop config file to write
        'filename' => '.mailfilter',
        // The VFS username to use, defaults to current user. If you want to
        // use a different user, you also need to disable 'hordeauth' above.
        // 'username' => 'user',
        // The VFS password to use, defaults to current user's password
        // 'password' => 'secret',
        // The path to the .mailfilter filter file, defaults to the filters'
        // owner's home directory.
        // You can use the following variables:
        //   %u = name of the filters' owner
        //   %d = domain name of the filters' owner
        //   %U = the 'username' from above
        // Example:
        //   '/data/maildrop/filters/%d/%u'
        //   This would be translated into:
        //   '/data/maildrop/filters/<filter_owners_domainname>/<filter_owners_username>/.mailfilter'
        // 'vfs_path' => '/path/to/maildrop',

        // VFS: FTP example
        // The VFS driver to use
        'vfstype' => 'ftp',
        // Port of the VFS server
        'port' => 21,
        // Specify permissions for uploaded files if necessary:
        // 'file_perms' => '0640',

        // VFS: SSH2 example
        // The VFS driver to use
        // 'vfstype' => 'ssh2',
        // Port of the VFS server
        // 'port' => 22,
    ),
    'script' => 'maildrop',
    'scriptparams' => array(
        // What path style does the IMAP server use ['mbox'|'maildir']?
        'path_style' => 'mbox',
        // Strip 'INBOX.' from the beginning of folder names in generated
        // scripts?
        'strip_inbox' => false,
        // An array of variables to append to every generated script.
        // Use if you need to set up specific environment variables.
        'variables' => array(
            // Example for the $PATH variable
            // 'PATH' => '/usr/bin'
        )
    ),
    'shares' => false
);

/* Procmail Example */
$backends['procmail'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'vfs',
    'hordeauth' => true,
    'params' => array(
        // Hostname of the VFS server
        'hostspec' => 'localhost',
        // Name of the procmail config file to write
        'filename' => '.procmailrc',
        // The VFS username to use, defaults to current user. If you want to
        // use a different user, you also need to disable 'hordeauth' above.
        // 'username' => 'user',
        // The VFS password to use, defaults to current user's password
        // 'password' => 'secret',
        // The path to the .procmailrc filter file, defaults to the filters'
        // owner's home directory.
        // You can use the following variables:
        //   %u = name of the filters' owner
        //   %U = the 'username' from above
        // Example:
        //   '/data/procmail/filters/%u'
        //   This would be translated into:
        //   '/data/procmail/filters/<filter_owners_username>/.procmailrc'
        // 'vfs_path' => '/path/to/procmail',

        // If procmail needs an external command for mail delivery, you
        // can specify it below. You can also set a prefix for the mailbox name
        // eg. for /usr/local/sbin/dmail +INBOX
        // 'delivery_agent' => '/usr/local/sbin/dmail',
        // 'delivery_mailbox_prefix' => '+',

        // If you need procmail to be called from .forward in the user's home
        // directory, set the file and the content below:
        // 'forward_file' => '.forward',
        // 'forward_string' => '"|/usr/local/bin/procmail"',

        // if the GNU utilities cannot be found in the path
        // or have different names, you can specify their location below
        // 'date' => '/opt/csw/bin/gdate',
        // 'echo' => '/opt/csw/bin/gecho',
        // 'ls' => '/opt/csw/bin/gls',

        // VFS: FTP example
        // The VFS driver to use
        'vfstype' => 'ftp',
        // Port of the VFS server
        'port' => 21,

        // VFS: SSH2 example
        // The VFS driver to use
        // 'vfstype' => 'ssh2',
        // Port of the VFS server
        // 'port' => 22,
    ),
    'script' => 'procmail',
    'scriptparams' => array(
        // What path style does the IMAP server use ['mbox'|'maildir']?
        'path_style' => 'mbox',
        // An array of variables to append to every generated script.
        // Use if you need to set up specific environment variables.
        'variables' => array(
            // Example for the $PATH variable
            // 'PATH' => '/usr/bin',
            // Example for the $DEFAULT variable
            // 'DEFAULT' => '$HOME/Maildir',
            // Example for the $VACATION_DIR variable (used to store vacation files)
            // 'VACATION_DIR' => '$HOME',
        )
    ),
    'shares' => false
);

/* Sieve Example */
$backends['sieve'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'timsieved',
    'hordeauth' => 'full',
    'params' => array(
        // Hostname of the timsieved server
        'hostspec' => 'localhost',
        // Login type of the server
        'logintype' => 'PLAIN',
        // Enable/disable TLS encryption
        'usetls' => true,
        // Port number of the timsieved server
        'port' => 2000,
        // Name of the sieve script
        'scriptname' => 'ingo',
        // The following settings can be used to specify an administration
        // user to update all users' scripts. If you want to use an admin
        // user, you also need to disable 'hordeauth' above. You have to use
        // an admin user if you want to use shared rules.
        // 'username' => 'cyrus',
        // 'password' => '*****',
        // Enable debugging. With Net_Sieve 1.2.0 or later, the sieve protocol
        // communication is logged with the DEBUG level. Earlier versions
        // print the log to the screen.
        'debug' => false,
    ),
    'script' => 'sieve',
    'scriptparams' => array(
        // If using Dovecot or any other Sieve implementation that requires
        // folder names to be UTF-8 encoded, set this parameter to true.
        'utf8' => false,
     ),
    'shares' => false
);

/* sivtest Example */
$backends['sivtest'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'sivtest',
    'hordeauth' => true,
    'params' => array(
        // Hostname of the timsieved server
        'hostspec' => 'localhost',
        // Login type of the server
        'logintype' => 'GSSAPI',
        // Enable/disable TLS encryption
        'usetls' => true,
        // Port number of the timsieved server
        'port' => 2000,
        // Name of the sieve script
        'scriptname' => 'ingo',
        // Location of sivtest
        'command' => '/usr/bin/sivtest',
        // name of the socket we're using
        'socket' => Horde::getTempDir() . '/sivtest.'
            . uniqid(mt_rand()) . '.sock',
    ),
    'script' => 'sieve',
    'scriptparams' => array(),
    'shares' => false,
);

/* Sun ONE/JES Example (LDAP/Sieve) */
$backends['ldapsieve'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'ldap',
    'hordeauth' => false,
    'params' => array(
        // Hostname of the ldap server
        'hostspec' => 'localhost',
        // Port number of the timsieved server
        'port' => 389,
        // LDAP Protocol Version (default = 2).  3 is required for TLS.
        'version' => 3,
        // Whether or not to use TLS.  If using TLS, you MUST configure
        // OpenLDAP (either /etc/ldap.conf or /etc/ldap/ldap.conf) with the CA
        // certificate which signed the certificate of the server to which you
        // are connecting.  e.g.:
        //
        // TLS_CACERT /usr/share/ca-certificates/mozilla/Equifax_Secure_CA.crt
        //
        // You MAY have problems if you are using TLS and your server is
        // configured to make random referrals, since some OpenLDAP libraries
        // appear to check the certificate against the original domain name,
        // and not the referred-to domain.  This can be worked around by
        // putting the following directive in the ldap.conf:
        //
        // TLS_REQCERT never
        'tls' => true,
        // Bind DN (for bind and script distinguished names, %u is replaced
        // with username, and %d is replaced with the internet domain
        // components (e.g. "dc=example, dc=com") if available).
        'bind_dn' => 'cn=ingo, ou=applications, dc=example, dc=com',
        // Bind password.  If not provided, user's password is used (useful
        // when bind_dn contains %u).
        'bind_password' => 'secret',
        // How to find user object.
        'script_base' => 'ou=People, dc=example, dc=com',
        'script_filter' => '(uid=%u)',
        // Attribute script is stored in.  Will not touch non-Ingo scripts.
        'script_attribute' => 'mailSieveRuleSource'
    ),
    'script' => 'sieve',
    'scriptparams' => array()
);

/* Kolab Example (using Sieve) */
if ($GLOBALS['conf']['kolab']['enabled']) {
    $backends['kolab'] = array(
        // Disabled by default
        'disabled' => true,
        'transport' => 'timsieved',
        'hordeauth' => 'full',
        'params' => array(
            'hostspec' => Kolab::getServer('imap'),
            'logintype' => 'PLAIN',
            'usetls' => false,
            'port' => $GLOBALS['conf']['kolab']['imap']['sieveport'],
            'scriptname' => 'kmail-vacation.siv'
        ),
        'script' => 'sieve',
        'scriptparams' => array(),
        'shares' => false
    );
}

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/backends.local.php')) {
    include dirname(__FILE__) . '/backends.local.php';
}
