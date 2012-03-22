<?php
/**
 * Ingo_Transport defines an API to activate filter scripts on a server.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport
{
    /**
     * Congifuration parameters.
     *
     * @var array
     */
    protected $_params = array(
        'password' => null,
        'username' => null
    );

    /**
     * Whether this driver allows managing other users' rules.
     *
     * @var boolean
     */
    protected $_supportShares = false;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script     The filter script.
     * @param array $additional  Any additional scripts that need to uploaded.
     *
     * @return boolean  True on success, false if script can't be activated.
     * @throws Ingo_Exception
     */
    public function setScriptActive($script, $additional = array())
    {
        return false;
    }

    /**
     * Returns whether the driver supports managing other users' rules.
     *
     * @return boolean  True if the driver supports shares.
     */
    public function supportShares()
    {
        return ($this->_supportShares &&
                $GLOBALS['session']->get('ingo', 'backend/shares'));
    }

}
