<?php
/**
 * Ingo_Transport_Timsieved implements the Sieve_Driver api to allow scripts
 * to be installed and set active via a Cyrus timsieved server.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Transport_Timsieved extends Ingo_Transport
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
            'hostspec'   => 'localhost',
            'logintype'  => 'PLAIN',
            'port'       => 4190,
            'scriptname' => 'ingo',
            'admin'      => '',
            'usetls'     => true,
            'debug'      => false
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
            Ingo::getUser(false),
            $this->_params['debug'],
            false,
            $this->_params['usetls'],
            null,
            array($this, 'debug'));

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
     * @param string $script     The filter script.
     * @param array $additional  Any additional scripts that need to uploaded.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script, $additional = array())
    {
        $this->_connect();

        if (!strlen($script)) {
            Ingo_Exception_Pear::catchError($this->_sieve->setActive(''));
            $this->_uploadAdditional($additional);
            return;
        }

        Ingo_Exception_Pear::catchError($this->_sieve->haveSpace($this->_params['scriptname'], strlen($script)));
        Ingo_Exception_Pear::catchError($this->_sieve->installScript($this->_params['scriptname'], $script, true));
        $this->_uploadAdditional($additional);
    }

    /**
     * Uploads additional scripts.
     *
     * This doesn't make much sense in Sieve though, because only one script
     * can be active at any time.
     *
     * @param array $additional  Any additional scripts that need to uploaded.
     *
     * @throws Ingo_Exception
     */
    protected function _uploadAdditional($additional = array())
    {
        /* Delete first. */
        foreach ($additional as $scriptname => $script) {
            if (!strlen($script)) {
                Ingo_Exception_Pear::catchError($this->_sieve->removeScript($scriptname));
            }
        }

        /* Now upload. */
        foreach ($additional as $scriptname => $script) {
            if (strlen($script)) {
                Ingo_Exception_Pear::catchError($this->_sieve->haveSpace($scriptname, strlen($script)));
                Ingo_Exception_Pear::catchError($this->_sieve->installScript($scriptname, $script));
            }
        }
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     * @throws Ingo_Exception
     */
    public function getScript()
    {
        $this->_connect();
        $active = Ingo_Exception_Pear::catchError($this->_sieve->getActive());

        return empty($active)
            ? ''
            : Ingo_Exception_Pear::catchError($this->_sieve->getScript($active));
    }

}
