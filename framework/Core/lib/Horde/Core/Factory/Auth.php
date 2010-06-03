<?php
/**
 * A Horde_Injector:: based Horde_Auth:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Auth:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Auth
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Singleton instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the Horde_Auth:: instance.
     *
     * @param string $driver  The driver.
     * @param array $params   Additional parameters to pass to the driver
     *                        (will override Horde defaults).
     *
     * @return Horde_Auth_Base  The singleton instance.
     * @throws Horde_Auth_Exception
     */
    public function getAuth($driver = null, array $params = array())
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['auth']['driver'];
        }

        $params = array_merge(Horde::getDriverConfig('auth', $driver), $params);
        ksort($params);

        /* Get proper driver name now that we have grabbed the
         * configuration. */
        if (strcasecmp($driver, 'httpremote') === 0) {
            /* BC */
            $driver = 'Http_Remote';
        } elseif (strcasecmp($driver, 'application') === 0) {
            $driver = 'Horde_Core_Auth_Application';
        } else {
            $driver = Horde_String::ucfirst(Horde_String::lower(basename($driver)));
        }

        $sig = hash('md5', serialize(array($driver, $params)));

        if (!isset($this->_instances[$sig])) {
            $lc_driver = Horde_String::lower($driver);
            switch ($lc_driver) {
            case 'composite':
                // TODO
                $params['admin_driver'] = null;
                $params['auth_driver'] = null;
                break;

            case 'cyrsql':
            case 'cyrus':
                $imap_config = array(
                    'hostspec' => empty($params['hostspec']) ? null : $params['hostspec'],
                    'password' => $params['cyrpass'],
                    'port' => empty($params['port']) ? null : $params['port'],
                    'secure' => ($params['secure'] == 'none') ? null : $params['secure'],
                    'username' => $params['cyradmin']
                );

                try {
                    $ob = Horde_Imap_Client::factory('Socket', $imap_config);
                    $ob->login();
                    $params['imap'] = $ob;
                } catch (Horde_Imap_Client_Exception $e) {
                    throw new Horde_Auth_Exception($e);
                }
                break;

                if ($lc_driver == 'cyrus') {
                    $params['backend'] = $this->getOb($params['backend']['driver'], $params['backend']['params']);
                }
                break;

            case 'http_remote':
                if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
                    $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
                }
                break;

            case 'kolab':
                $params['kolab'] = $this->_injector->getInstance('Horde_Kolab_Session');
                break;

            case 'ldap':
                $params['ldap'] = $this->_injector->getInstance('Horde_Ldap')->getLdap('horde', 'auth');
                break;

            case 'sql':
                $params['db'] = $this->_injector->getInstance('Horde_Db')->getDb('auth');
                break;
            }

            $params['logger'] = $this->_injector->getInstance('Horde_Log_Logger');
            $params['notify_expire'] = array($this, 'notifyExpire');

            $this->_instances[$sig] = Horde_Auth::factory($driver, $params);
        }

        return $this->_instances[$sig];
    }

    /**
     * Expire notification callback.
     *
     * @param integer $date  UNIX timestamp of password expiration.
     */
    public function notifyExpire($date)
    {
        if (isset($GLOBALS['notification'])) {
            $toexpire = ($date - time()) / 86400;
            $GLOBALS['notification']->push(sprintf(ngettext("%d day until your password expires.", "%d days until your password expires.", $toexpire), $toexpire), 'horde.warning');
        }
    }

}
