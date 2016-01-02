<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

use \Horde\ManageSieve;

/**
 * Ingo_Transport_Timsieved implements an Ingo transport driver to allow
 * scripts to be installed and set active via a Cyrus timsieved server.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport_Timsieved extends Ingo_Transport_Base
{
    /**
     * The ManageSieve object.
     *
     * @var \Horde\ManageSieve
     */
    protected $_sieve;

    /**
     */
    protected $_supportShares = true;

    /**
     * Constructor.
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'admin'      => '',
            'debug'      => false,
            'euser'      => '',
            'hostspec'   => 'localhost',
            'logintype'  => 'PLAIN',
            'port'       => 4190,
            'scriptname' => 'ingo',
            'usetls'     => true
        ), $params));
    }

    /**
     * Connects to the sieve server.
     *
     * @throws Ingo_Exception
     */
    protected function _connect()
    {
        global $injector;

        if (!empty($this->_sieve)) {
            return;
        }

        $auth = empty($this->_params['admin'])
            ? $this->_params['username']
            : $this->_params['admin'];

        try {
            $this->_sieve = new ManageSieve(array(
                'user'       => $auth,
                'password'   => $this->_params['password'],
                'host'       => $this->_params['hostspec'],
                'port'       => $this->_params['port'],
                'authmethod' => $this->_params['logintype'],
                'euser'      => $this->_params['euser'],
                'usetls'     => $this->_params['usetls'],
                'logger'     => $this->_params['debug']
                    ? $injector->getInstance('Horde_Log_Logger')
                    : null,
            ));
        } catch (ManageSieve\Exception $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Sets a script running on the backend.
     *
     * @param array $script  The filter script information. Passed elements:
     *                       - 'name': (string) the script name.
     *                       - 'recipes': (array) the filter recipe objects.
     *                       - 'script': (string) the filter script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $this->_connect();

        try {
            if (!strlen($script['script'])) {
                $this->_sieve->setActive('');
                $this->_sieve->removeScript($script['name']);
                return;
            }

            if (!$this->_sieve->hasSpace($script['name'], strlen($script['script']))) {
                throw new Ingo_Exception(_("Not enough free space on the server."));
            }
            $this->_sieve->installScript(
                $script['name'], $script['script'], true
            );
        } catch (ManageSieve\Exception $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     * @throws Ingo_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getScript()
    {
        $this->_connect();
        try {
            $active = $this->_sieve->getActive();
            if (!strlen($active)) {
                throw new Horde_Exception_NotFound();
            }
            return array(
                'name' => $active,
                'script' => $this->_sieve->getScript($active)
            );
        } catch (ManageSieve\Exception $e) {
            throw new Ingo_Exception($e);
        }
    }
}
