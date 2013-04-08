<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
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
     * The Net_Sieve object.
     *
     * @var Net_Sieve
     */
    protected $_sieve;

    /**
     * Constructor.
     */
    public function __construct(array $params = array())
    {
        $default_params = array(
            'admin'      => '',
            'debug'      => false,
            'euser'      => '',
            'hostspec'   => 'localhost',
            'logintype'  => 'PLAIN',
            'port'       => 4190,
            'scriptname' => 'ingo',
            'usetls'     => true
        );

        $this->_supportShares = true;

        parent::__construct(array_merge($default_params, $params));
    }

    /**
     * Connects to the sieve server.
     *
     * @throws Ingo_Exception
     */
    protected function _connect()
    {
        if (!empty($this->_sieve)) {
            return;
        }

        $auth = empty($this->_params['admin'])
            ? $this->_params['username']
            : $this->_params['admin'];

        $this->_sieve = new Net_Sieve(
            $auth,
            $this->_params['password'],
            $this->_params['hostspec'],
            $this->_params['port'],
            $this->_params['logintype'],
            $this->_params['euser'],
            $this->_params['debug'],
            false,
            $this->_params['usetls'],
            null,
            array($this, 'debug')
        );

        $res = $this->_sieve->getError();
        if ($res instanceof PEAR_Error) {
            unset($this->_sieve);
            throw new Ingo_Exception($res);
        }

        /* BC for older Net_Sieve versions that don't allow specify the debug
         * handler in the constructor. */
        if (!empty($this->_params['debug'])) {
            Ingo_Exception_Pear::catchError($this->_sieve->setDebug(true, array($this, 'debug')));
        }
    }

    /**
     * Routes the Sieve protocol log to the Horde log.
     *
     * @param Net_Sieve $sieve  A Net_Sieve object.
     * @param string $message   The tracked Sieve communication.
     */
    public function debug($sieve, $message)
    {
        Horde::logMessage($message, 'DEBUG');
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

        if (!strlen($script['script'])) {
            Ingo_Exception_Pear::catchError($this->_sieve->removeScript($script['name']));
            return;
        }

        Ingo_Exception_Pear::catchError($this->_sieve->haveSpace($script['name'], strlen($script['script'])));
        Ingo_Exception_Pear::catchError($this->_sieve->installScript($script['name'], $script['script'], true));
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
        $active = Ingo_Exception_Pear::catchError($this->_sieve->getActive());
        if (!strlen($active)) {
            throw new Horde_Exception_NotFound();
        }
        return array(
            'name' => $active,
            'script' => Ingo_Exception_Pear::catchError($this->_sieve->getScript($active))
        );
    }
}
