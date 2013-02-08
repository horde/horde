<?php
/**
 * Ingo works purely on a preferred mechanism for server selection. There are
 * a number of properties that you can set for each backend:
 *
 * IMPORTANT: DO NOT EDIT THIS FILE!
 * Local overrides MUST be placed in backends.local.php or backends.d/.
 * If the 'vhosts' setting has been enabled in Horde's configuration, you can
 * use backends-servername.php.
 *
 * Example configuration file that enables the Sieve backend in favor of the
 * IMAP backend:
 *
 * <code>
 * <?php
 * $backends['imap']['disabled'] = true;
 * $backends['sieve']['disabled'] = false;
 * </code>
 *
 * disabled: (boolean) If true, the config entry is disabled.
 *
 * params: (array) An array containing any additional information that the
 *         transport class needs. See examples below for further details.
 *
 * preferred: (string) This is the field that is used to choose which server
 *            is used. The value for this field may be a single string or an
 *            array of strings containing the hostnames to use with this
 *            server.
 *
 * script: (string) The type of script driver this server uses. Options:
 *   - imap:  IMAP client side filtering (POP3 servers NOT supported).
 *   - maildrop:  Maildrop scripts.
 *   - procmail:  Procmail scripts.
 *   - sieve:  Sieve scripts.
 *   - ispconfig:  ISPConfig SOAP Server (only for vacation notices).
 *
 * scriptparams: (array) An array containing any additional information that
 *               the script driver needs. See below for further details.
 *
 * shares: (boolean) Some drivers support sharing filter rules with other
 *         users. Users can then configure filters for each other if they
 *         give them permissions to do so. If you want to enable this feature,
 *         you need to set this parameter to true.
 *
 * transport: (string) The transport driver to use to store the script on the
 *            backend server. Valid options:
 *   - ldap:  LDAP server.
 *   - null:  No backend server (i.e. for script drivers, such as 'imap', that
 *            does not use scripts).
 *   - timsieved:  Timsieved (managesieve) server.
 *   - vfs:  Use Horde VFS.
 *   - ispconfig:  ISPConfig SOAP Server (only for vacation notices).
 *
 *   NOTE: By default, the transport driver will use Horde credentials to
 *         authenticate to the backend. If a different username/password is
 *         needed, use the 'transport_auth' hook (see hooks.php) to define
 *         these values.
 */

/* IMAP Example */
$backends['imap'] = array(
    // ENABLED by default
    'disabled' => false,
    'transport' => 'null',
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
    'params' => array(
        // Hostname of the VFS server
        'hostspec' => 'localhost',
        // Name of the maildrop config file to write
        'filename' => '.mailfilter',
        // The path to the .mailfilter filter file, defaults to the filters'
        // owner's home directory.
        // You can use the following variables:
        //   %u = name of the filters' owner
        //   %d = domain name of the filters' owner
        //   %U = the transport 'username'
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
        // Any arguments passed to the mailbot command. The -N flag (to not
        // include the original, quoted message content has been added with
        // Maildrop 2.5.1/Courier 0.65.1.
        'mailbotargs' => '-N',
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
    'params' => array(
        // Hostname of the VFS server
        'hostspec' => 'localhost',
        // Name of the procmail config file to write
        'filename' => '.procmailrc',
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
            // The $DEFAULT variable. If using Maildir, Ingo will use this
            // value as the default unless you explicitly configure otherwise.
            // 'DEFAULT' => '$HOME/Maildir/',
            // The $DEFAULT variable. If using Maildir, Ingo will use this
            // value as the default unless you explicitly configure otherwise.
            // 'MAILDIR' => '$HOME/Maildir',
            // Example for the $PATH variable
            // 'PATH' => '/usr/bin',
            // Example for the $VACATION_DIR variable (used to store vacation files)
            // 'VACATION_DIR' => '$HOME',
        ),
        // If you need procmail to be called from .forward in the user's home
        // directory, set the file and the content below:
        // 'forward_file' => '.forward',
        // 'forward_string' => '"|/usr/local/bin/procmail"',
    ),
    'shares' => false
);

/* Sieve Example */
$backends['sieve'] = array(
    // Disabled by default
    'disabled' => true,
    'transport' => 'timsieved',
    'params' => array(
        // Hostname of the timsieved server
        'hostspec' => 'localhost',
        // Login type of the server
        'logintype' => 'PLAIN',
        // Enable/disable TLS encryption
        'usetls' => true,
        // Port number of the timsieved server
        'port' => 4190,
        // Name of the sieve script
        'scriptname' => 'ingo',
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
    'params' => array(
        // Hostname of the timsieved server
        'hostspec' => 'localhost',
        // Login type of the server
        'logintype' => 'GSSAPI',
        // Enable/disable TLS encryption
        'usetls' => true,
        // Port number of the timsieved server
        'port' => 4190,
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

$backends['ispconfig'] = array(
    'disabled' => true,
    'transport' => 'ispconfig',
    // enabling transport_auth() in hooks.php is likely to be required
    'params' => array(
        'soap_uri' => 'http://ispconfig-webinterface.example.com:8080/remote/',
        // This user must be created in the ISPConfig webinterface // under
        // System -> Remote Users.  The required permissions ("functions")
        // is "mail user functions" only.
        'soap_user' => 'horde',
        'soap_pass' => 'secret'
        ),
    'script' => 'ispconfig',
    'scriptparams' => array(),
    'shares' => false
);

