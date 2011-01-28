<?php
/**
 * A Horde_Injector:: based factory for creating Horde_Ldap objects.
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
 * A Horde_Injector:: based factory for creating Horde_Ldap objects.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Core_Factory_Ldap
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

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
     * Return the LDAP instance.
     *
     * @param string $app   The application.
     * @param string $type  The type.
     *
     * @return Horde_Ldap  The singleton instance.
     * @throws Horde_Exception
     * @throws Horde_Ldap_Exception
     */
    public function getLdap($app = 'horde', $type = null)
    {
        $sig = $app . '|' . $type;

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $pushed = ($app == 'horde')
            ? false
            : $GLOBALS['registry']->pushApp($app);

        $config = $this->getConfig($type);

        /* BC check for old configuration without 'user' setting, so that
         administrators can still log in through LDAP and update the
         configuration. */
        if (!isset($config['user'])) {
            $config['user'] = $config;
        }
        $config['cache'] = $this->_injector->getInstance('Horde_Cache');

        try {
            $this->_instances[$sig] = new Horde_Ldap($config);
            if (isset($config['bindas']) && $config['bindas'] == 'user') {
                $this->_instances[$sig]->bind(
                    $this->_instances[$sig]->findUserDN($GLOBALS['registry']->getAuth()),
                    $GLOBALS['registry']->getAuthCredential('password'));
            }
        } catch (Horde_Exception $e) {
            if ($pushed) {
                $GLOBALS['registry']->popApp();
            }
            throw $e;
        }

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $this->_instances[$sig];
    }

    /**
     */
    public function getConfig($type)
    {
        return Horde::getDriverConfig($type, 'ldap');
    }

}
