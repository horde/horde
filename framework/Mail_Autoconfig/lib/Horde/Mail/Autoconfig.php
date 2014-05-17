<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */

/**
 * Object to perform autodetermination of mail configuration parameters.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig
{
    /**
     * Drivers. Shared between all instances.
     *
     * @var array
     */
    static protected $_driverlist;

    /**
     * Driver list.
     *
     * @var array
     */
    protected $_drivers;

    /**
     * Load the list of drivers.
     *
     * @return array
     */
    static public function getDrivers()
    {
        if (isset(self::$_driverlist)) {
            return self::$_driverlist;
        }

        $fi = new FilesystemIterator(__DIR__ . '/Autoconfig/Driver');
        $class_prefix = __CLASS__ . '_Driver_';

        $drivers = array();

        foreach ($fi as $val) {
            if ($val->isFile()) {
                $cname = $class_prefix . $val->getBasename('.php');
                if (class_exists($cname)) {
                    $ob = new $cname();
                    if ($ob instanceof Horde_Mail_Autoconfig_Driver) {
                        $drivers[$ob->priority][] = $ob;
                    }
                }
            }
        }

        ksort($drivers, SORT_NUMERIC);

        $flatten = array();
        array_walk_recursive(
            $drivers,
            function($a) use (&$flatten) { $flatten[] = $a; }
        );
        self::$_driverlist = $flatten;

        return $flatten;
    }

    /**
     * Constructor.
     *
     * @param array $opts  Configuration options:
     *   - drivers: (array) Use this list of drivers instead of the default
     *              autodetected list of drivers contained in this package.
     */
    public function __construct(array $opts = array())
    {
        $this->_drivers = isset($opts['drivers'])
            ? $opts['drivers']
            : self::getDrivers();
    }

    /**
     * Determine the configuration for a message submission agent (MSA).
     *
     * @param string $email  An e-mail address.
     * @param array $opts    Additional options:
     *   - auth: (mixed) If set, will perform additional check to verify that
     *           user can authenticate to server. Either a string (password)
     *           or a Horde_Smtp_Password object.
     *   - insecure: (boolean) If true and checking authentication, will allow
     *               non-secure authentication types.
     *
     * @return Horde_Mail_Autoconfig_Server  The server object to use, or
     *                                       false if no server could be
     *                                       found.
     *
     * @throws Horde_Mail_Autoconfig_Exception
     */
    public function getMsaConfig($email, array $opts = array())
    {
        return $this->_getConfig('msaSearch', $email, $opts);
    }

    /**
     * Determine the configuration for a message storage access server (i.e.
     * IMAP and/or POP3 server).
     *
     * @param string $email  An e-mail address.
     * @param array $opts    Additional options:
     *   - auth: (mixed) If set, will perform additional check to verify that
     *           user can authenticate to server. Either a string (password)
     *           or a Horde_Smtp_Password object.
     *   - insecure: (boolean) If true and checking authentication, will allow
     *               non-secure authentication types.
     *   - no_imap: (boolean) If true, will not autoconfig IMAP servers.
     *   - no_pop3: (boolean) If true, will not autoconfig POP3 servers.
     *
     * @return Horde_Mail_Autoconfig_Server  The server object to use, or
     *                                       false if no server could be
     *                                       found.
     * @throws Horde_Mail_Autoconfig_Exception
     */
    public function getMailConfig($email, array $opts = array())
    {
        return $this->_getConfig('mailSearch', $email, $opts);
    }

    /* Internal methods. */

    /**
     * Parse e-mail input.
     *
     * @param string $email  An e-mail address.
     *
     * @return array  The email object and a list of (sub)domains.
     * @throws Horde_Mail_Autoconfig_Exception
     */
    protected function _parseEmail($email)
    {
        $rfc822 = new Horde_Mail_Rfc822();

        try {
            $alist = $rfc822->parseAddressList($email, array(
                'limit' => 1
            ));
        } catch (Horde_Mail_Exception $e) {
            throw new Horde_Mail_Autoconfig_Exception($e);
        }

        if (!($ob = $alist[0])) {
            throw new Horde_Mail_Autoconfig_Exception(
                'Could not parse e-mail address given.'
            );
        }

        $host = $alist[0]->host;
        if (!strlen($host)) {
            throw new Horde_Mail_Autoconfig_Exception(
                'Could not determine domain name from e-mail address given.'
            );
        }

        /* Split into subdomains, and add with deepest subdomain first. */
        $domains = array();
        $parts = explode('.', $host);
        while (count($parts) >= 2) {
            $domains[] = implode('.', $parts);
            array_shift($parts);
        }

        return array($alist[0], $domains);
    }

    /**
     * Determine autoconfiguration details.
     *
     * @param string $type   The type of driver search to do.
     * @param string $email  An e-mail address.
     * @param array $opts    Options (see getMsaConfig() and getMailConfig()).
     *
     * @return mixed  See getMsaConfig() and getMailConfig().
     * @throws Horde_Mail_Autoconfig_Exception
     */
    public function _getConfig($type, $email, $opts)
    {
        list($email_ob, $domains) = $this->_parseEmail($email);

        $dconfig = array(
            'email' => $email_ob,
            /* This is only used for IMAP/POP3 driver, but not harm in
             * adding for MSA driver. */
            'no_imap' => !empty($opts['no_imap']),
            'no_pop3' => !empty($opts['no_pop3'])
        );

        foreach ($this->_drivers as $val) {
            $res = $val->$type($domains, $dconfig);

            if ($res) {
                foreach ($res as $val2) {
                    $vconfig = $opts;

                    if ($val2->username) {
                        $vconfig['users'] = array($val2->username);
                    } elseif ($email_ob->valid) {
                        /* RFC 6186 says to always try full email first, so
                         * use that as a default for all drivers that don't
                         * explicitly set a username. */
                        $vconfig['users'] = array(
                            $email_ob->bare_address,
                            $email_ob->mailbox
                        );
                    }

                    if ($val2->valid($vconfig)) {
                        return $val2;
                    }
                }
            }
        }

        return false;
    }

}
