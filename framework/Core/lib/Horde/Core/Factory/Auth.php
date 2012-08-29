<?php
/**
 * A Horde_Injector:: based Horde_Auth:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Auth:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Auth extends Horde_Core_Factory_Base
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    private $_instances = array();

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
            $base_params['base'] = $this->_create($GLOBALS['conf']['auth']['driver']);
        }

        $this->_instances[$app] = Horde_Auth::factory(
            'Horde_Core_Auth_Application',
            $base_params
        );

        return $this->_instances[$app];
    }

    /**
     * Returns a Horde_Auth_Base driver for the given driver/configuration.
     *
     * @param string $driver      Driver name.
     * @param array $orig_params  Driver parameters.
     *
     * @return Horde_Auth_Base  Authentication object.
     */
    protected function _create($driver, $orig_params = null)
    {
        /* Get proper driver name now that we have grabbed the
         * configuration. */
        if (strcasecmp($driver, 'application') === 0) {
            $driver = 'Horde_Core_Auth_Application';
        } elseif (strcasecmp($driver, 'httpremote') === 0) {
            /* BC */
            $driver = 'Http_Remote';
        } elseif (strcasecmp($driver, 'composite') === 0) {
            $driver = 'Horde_Core_Auth_Composite';
        } elseif (strcasecmp($driver, 'ldap') === 0) {
            $driver = 'Horde_Core_Auth_Ldap';
        } elseif (strcasecmp($driver, 'msad') === 0) {
            $driver = 'Horde_Core_Auth_Msad';
        } elseif (strcasecmp($driver, 'shibboleth') === 0) {
            $driver = 'Horde_Core_Auth_Shibboleth';
        } elseif (strcasecmp($driver, 'imsp') === 0) {
            $driver = 'Horde_Core_Auth_Imsp';
        } else {
            $driver = implode('_', array_map('Horde_String::ucwords', explode('_', Horde_String::lower(basename($driver)))));
        }

        $params = is_null($orig_params)
            ? Horde::getDriverConfig('auth', $driver)
            : $orig_params;

        $lc_driver = Horde_String::lower($driver);
        switch ($lc_driver) {
        case 'horde_core_auth_composite':
            // Both of these params are required, but we need to skip if
            // non-existent to return a useful error message later.
            if (!empty($params['admin_driver'])) {
                $params['admin_driver'] = $this->_create($params['admin_driver']['driver'], $params['admin_driver']['params']);
            }
            if (!empty($params['auth_driver'])) {
                $params['auth_driver'] = $this->_create($params['auth_driver']['driver'], $params['auth_driver']['params']);
            }
            break;

        case 'cyrsql':
            $imap_config = array(
                'hostspec' => empty($params['cyrhost']) ? null : $params['cyrhost'],
                'password' => $params['cyrpass'],
                'port' => empty($params['cyrport']) ? null : $params['cyrport'],
                'secure' => ($params['secure'] == 'none') ? null : $params['secure'],
                'username' => $params['cyradmin'],
            );

            try {
                $ob = new Horde_Imap_Client_Socket($imap_config);
                $ob->login();
                $params['imap'] = $ob;
            } catch (Horde_Imap_Client_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }

            if (!empty($params['driverconfig']) &&
                $params['driverconfig'] == 'horde') {
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Db_Adapter');
            } else {
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('horde', is_null($orig_params) ? 'auth' : $orig_params);
            }
            break;

        case 'http_remote':
            $params['client'] = $this->_injector->getInstance('Horde_Core_Factory_HttpClient')->create();
            break;

        case 'horde_core_auth_application':
            if (isset($this->_instances[$params['app']])) {
                return $this->_instances[$params['app']];
            }
            break;

        case 'horde_core_auth_imsp':
            $params['imsp'] = $this->_injector->getInstance('Horde_Core_Factory_Imsp')->create();
            break;

        case 'kolab':
            $params['kolab'] = $this->_injector
                ->getInstance('Horde_Kolab_Session');
            break;

        case 'horde_core_auth_ldap':
        case 'horde_core_auth_msad':
            $params['ldap'] = $this->_injector
                ->getInstance('Horde_Core_Factory_Ldap')
                ->create('horde', is_null($orig_params) ? 'auth' : $orig_params);
            break;

        case 'customsql':
        case 'sql':
            if (!empty($params['driverconfig']) &&
                $params['driverconfig'] == 'horde') {
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Db_Adapter');
            } else {
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('horde', is_null($orig_params) ? 'auth' : $orig_params);
            }
            break;
        }

        $params['default_user'] = $GLOBALS['registry']->getAuth();
        $params['logger'] = $this->_injector->getInstance('Horde_Log_Logger');
        if (!empty($params['count_bad_logins'])) {
            $params['history_api'] = $this->_injector->getInstance('Horde_History');
        }
        if (!empty($params['login_block'])) {
            $params['lock_api'] = $this->_injector->getInstance('Horde_Lock');
        }

        $auth_ob = Horde_Auth::factory($driver, $params);
        if ($driver == 'Horde_Core_Auth_Application') {
            $this->_instances[$params['app']] = $auth_ob;
        }

        return $auth_ob;
    }

}
