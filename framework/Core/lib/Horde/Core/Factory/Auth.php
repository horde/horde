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
     * @param string $app  The application to authenticate to.
     *
     * @return Horde_Auth_Base  The singleton instance.
     * @throws Horde_Auth_Exception
     */
    public function create($app = null)
    {
        if (is_null($app)) {
            $app = 'horde';
        }

        if (isset($this->_instances[$app])) {
            return $this->_instances[$app];
        }

        $base_params = array(
            'app' => $app,
            'logger' => $this->_injector->getInstance('Horde_Log_Logger')
        );

        if ($app == 'horde') {
            $driver = $GLOBALS['conf']['auth']['driver'];
            $params = Horde::getDriverConfig('auth', $driver);

            /* Get proper driver name now that we have grabbed the
             * configuration. */
            if (strcasecmp($driver, 'application') === 0) {
                $driver = 'Horde_Core_Auth_Application';
            } elseif (strcasecmp($driver, 'httpremote') === 0) {
                /* BC */
                $driver = 'Http_Remote';
            } elseif (strcasecmp($driver, 'ldap') === 0) {
                $driver = 'Horde_Core_Auth_Ldap';
            } elseif (strcasecmp($driver, 'msad') === 0) {
                $driver = 'Horde_Core_Auth_Msad';
            } elseif (strcasecmp($driver, 'shibboleth') === 0) {
                $driver = 'Horde_Core_Auth_Shibboleth';
            } else {
                $driver = Horde_String::ucfirst(Horde_String::lower(basename($driver)));
            }

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

                $params['charset'] = 'UTF-8';
                break;

            case 'http_remote':
                if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
                    $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
                }
                break;

            case 'imap':
                $params['charset'] = 'UTF-8';
                break;

            case 'kolab':
                $params['kolab'] = $this->_injector->getInstance('Horde_Kolab_Session');
                break;

            case 'horde_core_auth_ldap':
            case 'horde_core_auth_msad':
                $params['ldap'] = $this->_injector->getInstance('Horde_Core_Factory_Ldap')->getLdap('horde', 'auth');
                break;

            case 'customsql':
            case 'sql':
                $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
                break;
            }

            $params['default_user'] = $GLOBALS['registry']->getAuth();
            $params['logger'] = $this->_injector->getInstance('Horde_Log_Logger');

            $base_params['base'] = Horde_Auth::factory($driver, $params);
            if ($driver == 'Horde_Core_Auth_Application') {
                $this->_instances[$params['app']] = $base_params['base'];
            }
        }

        $this->_instances[$app] = Horde_Auth::factory('Horde_Core_Auth_Application', $base_params);

        return $this->_instances[$app];
    }

}
