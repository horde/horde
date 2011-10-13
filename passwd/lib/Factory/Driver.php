<?php
/**
 * A Horde_Injector:: based Passwd_Driver:: factory.
 *
 * PHP version 5
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl.php
 * @package  Passwd
 */
 
class Passwd_Factory_Driver extends Horde_Core_Factory_Base
{

    /**
    * Backends
    *
    * @var array
    */
    protected $_backends = array();

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Passwd_Driver:: instance.
     *
     * @param string $name  A string containing the internal name of this backend
     *
     * @return Passwd_Driver  The singleton instance.
     * @throws Passwd_Exception
     */
    public function create($name, $params = array() )
    {
        if (!empty($params['is_subdriver'])) {
            $backends = array($name => $params);
        } else {
            $backends = $this->getBackends();
        }

        $key = $name;

        if (empty($backends[$name])) {
            throw new Passwd_Exception(sprintf(_("The password backend \"%s\" does not exist."), $name));
        }
        $backend = $backends[$name];

        if (!isset($this->_instances[$key])) {
            $class = 'Passwd_Driver_' . Horde_String::ucfirst(basename($backend['driver']));
            if (!class_exists($class)) {
                throw new Passwd_Exception(sprintf(_("Unable to load the definition of %s."), $class));
            }

            if (empty($backend['params'])) {
                $backend['params'] = array();
            }
            if (empty($backend['password policy'])) {
                $backend['password policy'] = array();
            }
            if (!empty($params)){
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
                        $backend['params']['db'] = empty($backend['params'])
                        ? $this->_injector->getInstance('Horde_Db_Adapter')
                        : $this->_injector->getInstance('Horde_Core_Factory_Db')
                            ->create('passwd', $backend['params']);
                    } catch (Horde_Db_Exception $e) {
                        throw new Passwd_Exception($e);
                    }
                }
                break;
            /* more to come later as drivers are upgraded to H4 / PHP5 */
            default:
                /* Anything left to do with the rest? */
                break;
            }

            $driver = new $class($backend['params']);

            /* shouldn't we fetch policy from backend and inject some handler class here ? */

            if (empty($backend['params']['is_subdriver'])) {
                $this->_instances[$key] = $driver;
            } else {
                return $driver;
            }
        }

        return $this->_instances[$key];
    }

    /**
     * Set the backends available in this factory
     *
     * @param array $backends A list of backends in the format of backends.php
     *
     * @return Passwd_Factory_Driver The object itself for fluid interface.
     */
    public function setBackends(array $backends) {
        $this->_backends = $backends;
        return $this;
    }

    /**
     * Get the backends available in this factory
     *
     * @return array $backends A list of backends in the format of backends.php
     * @throws Passwd_Exception If no backends have been set
     */
    public function getBackends() {
        if (empty($this->_backends)) {
            throw new Passwd_Exception('No Backends have been set before getBackends was called');
        }
        return $this->_backends;
    }
}
