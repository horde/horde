<?php
/**
 * Ingo_Transport defines an API to activate filter scripts on a server.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Transport
{
    /**
     * Driver specific parameters
     *
     * @var array
     */
    protected $_params = array(
        'username' => null,
        'password' => null
    );

    /**
     * Whether this driver allows managing other users' rules.
     *
     * @var boolean
     */
    protected $_support_shares = false;

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Ingo_Transport  The newly created concrete instance.
     * @throws Ingo_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = __CLASS__ . '_' . ucfirst(basename($driver));

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ingo_Exception('Could not load driver.');
    }

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script  The filter script.
     *
     * @return boolean  True on success, false if script can't be activated.
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
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
        return ($this->_support_shares &&
                !empty($_SESSION['ingo']['backend']['shares']));
    }

}
