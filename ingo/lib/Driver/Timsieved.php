<?php
/**
 * Ingo_Driver_Timsieved:: implements the Sieve_Driver api to allow scripts to
 * be installed and set active via a Cyrus timsieved server.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
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
     */
    protected function _connect()
    {
        if (!empty($this->_sieve)) {
            return;
        }

        if (empty($this->_params['admin'])) {
            $auth = $this->_params['username'];
        } else {
            $auth = $this->_params['admin'];
        }
        $this->_sieve = new Net_Sieve($auth,
                                      $this->_params['password'],
                                      $this->_params['hostspec'],
                                      $this->_params['port'],
                                      $this->_params['logintype'],
                                      Ingo::getUser(false),
                                      false,
                                      false,
                                      $this->_params['usetls']);

        $res = $this->_sieve->getError();
        if (is_a($res, 'PEAR_Error')) {
            unset($this->_sieve);
            return $res;
        }

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
        Horde::logMessage($message, __FILE__, __LINE__, PEAR_LOG_DEBUG);
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script  The sieve script.
     *
     * @return mixed  True on success, PEAR_Error on error.
     */
    public function setScriptActive($script)
    {
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (!strlen($script)) {
            return $this->_sieve->setActive('');
        }

        $res = $this->_sieve->haveSpace($this->_params['scriptname'],
                                        strlen($script));
        if (is_a($res, 'PEAR_ERROR')) {
            return $res;
        }

        return $this->_sieve->installScript($this->_params['scriptname'],
                                            $script, true);
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     */
    public function getScript()
    {
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }
        $active = $this->_sieve->getActive();
        if (empty($active)) {
            return '';
        }
        return $this->_sieve->getScript($active);
    }

}
