<?php
/**
 * Ingo_Driver_Timsieved:: implements the Sieve_Driver api to allow scripts to
 * be installed and set active via a Cyrus timsieved server.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Driver_Timsieved extends Ingo_Driver
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
    public function __construct($params = array())
    {
        $this->_support_shares = true;

        $default_params = array(
            'hostspec'   => 'localhost',
            'logintype'  => 'PLAIN',
            'port'       => 2000,
            'scriptname' => 'ingo',
            'admin'      => '',
            'usetls'     => true
        );

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

        $this->_sieve = new Net_Sieve($auth,
                                      $this->_params['password'],
                                      $this->_params['hostspec'],
                                      $this->_params['port'],
                                      $this->_params['logintype'],
                                      Ingo::getUser(false),
                                      $this->_params['debug'],
                                      false,
                                      $this->_params['usetls'],
                                      null,
                                      array($this, '_debug'));

        $res = $this->_sieve->getError();
        if ($res instanceof PEAR_Error) {
            unset($this->_sieve);
            throw new Ingo_Exception($res);
        }

        /* BC for older Net_Sieve versions that don't allow specify the debug
         * handler in the constructor. */
        if (!empty($this->_params['debug'])) {
            $this->_sieve->setDebug(true, array($this, '_debug'));
        }
    }

    /**
     * Routes the Sieve protocol log to the Horde log.
     *
     * @param Net_Sieve $sieve  A Net_Sieve object.
     * @param string $message   The tracked Sieve communication.
     */
    protected function _debug($sieve, $message)
    {
        Horde::logMessage($message, 'DEBUG');
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script  The sieve script.
     *
     * @return mixed  True on success.
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $res = $this->_connect();

        if (!strlen($script)) {
            return $this->_sieve->setActive('');
        }

        $res = $this->_sieve->haveSpace($this->_params['scriptname'], strlen($script));
        if ($res instanceof PEAR_Error) {
            throw new Ingo_Exception($res);
        }

        return $this->_sieve->installScript($this->_params['scriptname'],
                                            $script, true);
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     * @throws Ingo_Exception
     */
    public function getScript()
    {
        $res = $this->_connect();
        $active = $this->_sieve->getActive();

        return empty($active)
            ? ''
            : $this->_sieve->getScript($active);
    }

}
