<?php
/**
 * Ingo_Driver:: defines an API to activate filter scripts on a server.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Driver
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
     * Attempts to return a concrete Ingo_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Ingo_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Ingo_Driver instance, or
     *                false on error.
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = 'Ingo_Driver_' . ucfirst($driver);

        return class_exists($class)
            ? new $class($params)
            : false;
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
     * @return mixed  True on success, false if script can't be activated.
     *                Returns PEAR_Error on error.
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
        return $this->_support_shares && !empty($_SESSION['ingo']['backend']['shares']);
    }

}
