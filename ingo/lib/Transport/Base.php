<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
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
 * Ingo_Transport defines an API to activate filter scripts on a server.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
abstract class Ingo_Transport_Base
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
     * @param array $script  The filter script information. Passed elements:
     *                       - 'name': (string) the script name.
     *                       - 'recipes': (array) the filter recipe objects.
     *                       - 'script': (string) the filter script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
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

    /**
     * Quotes user input if supported by the transport driver.
     *
     * @param string $string  A string to quote.
     *
     * @return string  The quoted string.
     */
    public function quote($string)
    {
        return $string;
    }
}
