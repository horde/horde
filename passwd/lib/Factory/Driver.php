<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl.php
 * @package   Passwd
 */

/**
 * A Horde_Injector based Passwd_Driver factory.
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl.php
 * @package   Passwd
 *
 * @property-read array $backends  Backend list.
 */
class Passwd_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * Backend configurations.
     *
     * @var array
     */
    private $_backends = null;

    /**
     * Created Passwd_Driver instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'backends':
            $this->_loadBackends();
            return $this->_backends;
        }
    }

    /**
     * Returns the Passwd_Driver instance.
     *
     * @param string $name   A string containing the internal name of this
     *                       backend.
     * @param array $params  Any backend parameters if not the defaults.
     *
     * @return Passwd_Driver  The singleton instance.
     * @throws Passwd_Exception
     */
    public function create($name, $params = array())
    {
        $this->_loadBackends();

        $backends = empty($params['is_subdriver'])
            ? $this->_backends
            : array($name => $params);

        if (empty($backends[$name])) {
            throw new Passwd_Exception(sprintf(_("The password backend \"%s\" does not exist."), $name));
        }
        $backend = $backends[$name];

        if (!isset($this->_instances[$name])) {
            $class = 'Passwd_Driver_' . Horde_String::ucfirst(basename($backend['driver']));
            if (!class_exists($class)) {
                throw new Passwd_Exception(sprintf(_("Unable to load the definition of %s."), $class));
            }

            if (empty($backend['params'])) {
                $backend['params'] = array();
            }
            if (empty($backend['policy'])) {
                $backend['policy'] = array();
            }
            if (empty($params['is_subdriver']) && !empty($params)) {
                $backend['params'] = array_merge($backend['params'], $params);
            }

            switch ($class) {
            case 'Passwd_Driver_Ldap':
            case 'Passwd_Driver_Smbldap':
                if (isset($backend['params']['admindn'])) {
                    $backend['params']['binddn'] = $backend['params']['admindn'];
                }
                if (isset($backend['params']['adminpw'])) {
                    $backend['params']['bindpw'] = $backend['params']['adminpw'];
                }
                if (isset($backend['params']['host'])) {
                    $backend['params']['hostspec'] = $backend['params']['host'];
                }

                try {
                    $backend['params']['ldap'] = new Horde_Ldap($backend['params']);
                } catch (Horde_Ldap_Exception $e) {
                    throw new Passwd_Exception($e);
                }
                break;

            case 'Passwd_Driver_Sql':
            case 'Passwd_Driver_Vpopmail':
                if (!($backend['params']['db'] instanceof Horde_Db_Adapter)) {
                    try {
                        if (empty($backend['params'])) {
                            $backend['params']['db'] = $this->_injector
                                ->getInstance('Horde_Db_Adapter');
                        } else {
                            $params = $backend['params'];
                            unset($params['table'], $params['user_col'],
                                  $params['pass_col'], $params['encryption'],
                                  $params['show_encryption'],
                                  $params['query_lookup'], $params['query_modify']);
                            $backend['params']['db'] = $this->_injector
                                ->getInstance('Horde_Core_Factory_Db')
                                ->create('passwd', $params);
                        }
                    } catch (Horde_Db_Exception $e) {
                        throw new Passwd_Exception($e);
                    }
                }
                break;

            case 'Passwd_Driver_Horde':
                $backend['params']['auth'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Auth')
                    ->create();
                break;

            case 'Passwd_Driver_Soap':
                if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
                    $backend['params']['soap_params']['proxy_host'] = $GLOBALS['conf']['http']['proxy']['proxy_host'];
                    $backend['params']['soap_params']['proxy_port'] = $GLOBALS['conf']['http']['proxy']['proxy_port'];
                    $backend['params']['soap_params']['proxy_login'] = $GLOBALS['conf']['http']['proxy']['proxy_user'];
                    $backend['params']['soap_params']['proxy_password'] = $GLOBALS['conf']['http']['proxy']['proxy_pass'];
                }
                $backend['params']['soap_params']['encoding'] = 'UTF-8';
                break;
            }

            try {
                $driver = new $class($backend['params']);
            } catch (Passwd_Exception $e) {
                throw $e;
            } catch (Exception $e) {
                throw new Passwd_Exception($e);
            }

            /* Shouldn't we fetch policy from backend and inject some handler
             * class here? */

            if (!empty($backend['params']['is_subdriver'])) {
                return $driver;
            }

            $this->_instances[$name] = $driver;
        }

        return $this->_instances[$name];
    }

    /**
     * @throws Passwd_Exception
     */
    protected function _loadBackends()
    {
        if (!is_null($this->_backends)) {
            return;
        }

        $allbackends = Horde::loadConfiguration('backends.php', 'backends', 'passwd');
        if (!isset($allbackends) || !is_array($allbackends)) {
            throw new Passwd_Exception(_("No backends configured in backends.php"));
        }

        $backends = array();
        foreach ($allbackends as $name => $backend) {
            if (empty($backend['disabled'])) {
                /* Make sure the 'params' entry exists. */
                if (!isset($backend['params'])) {
                    $backend['params'] = array();
                }

                $backends[$name] = $backend;
            }
        }

        /* Check for valid backend configuration. */
        if (empty($backends)) {
            throw new Passwd_Exception(_("No backend configured for this host"));
        }

        $this->_backends = $backends;
    }

}
