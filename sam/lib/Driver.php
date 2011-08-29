<?php
/**
 * Sam_Driver defines an API for implementing storage backends for Sam.
 *
 * @author  Chris Bowlby <excalibur@hub.org>
 * @author  Max Kalika <max@gentoo.org>
 * @package Sam
 */
class Sam_Driver
{
    /**
     * Array holding a user's SPAM options.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Array holding the defaults to use if user hasn't defined
     * a value. Used by the drivers that support global defaults.
     *
     * @var array
     */
    protected $_defaults = array();

    /**
     * Hash containing connection parameters for this backend.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The user name to whom the SPAM options belong.
     *
     * @var string
     */
    protected $_user;

    /**
     * Attempts to return a concrete Sam_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Sam_Driver subclass to
     *                        return.
     * @param string $user    The name of the user who owns these SPAM options.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Sam_Driver  The newly created concrete Sam_Driver instance, or
     *                     false on error.
     */
    public function factory($driver, $user, $params = array())
    {
        $driver = basename($driver);
        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Sam_Driver_' . $driver;
        if (class_exists($class)) {
            $sam = &new $class($user, $params);
        } else {
            $sam = false;
        }

        return $sam;
    }

    /**
     * Attempts to return a reference to a concrete Sam_Driver instance based
     * on $driver.
     *
     * It will only create a new instance if no Sam_Driver instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Sam_Driver::singleton()
     *
     * @param string $driver  The type of concrete Sam_Driver subclass to
     *                        return.
     * @param string $user    The name of the user who owns these SPAM options.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Sam_Driver  The created concrete Sam_Driver instance, or false
     *                     on error.
     */
    public function singleton($driver, $user, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $user, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Sam_Driver::factory($driver, $user, $params);
        }

        return $instances[$signature];
    }

    /**
     * Check to see if the backend supports a particular capability.
     *
     * @param string $capability  The name of the capability to check.
     *
     * @return boolean  True if the backend is capable, false otherwise.
     */
    public function hasCapability($capability)
    {
        return in_array($capability, $this->_capabilities);
    }

    /**
     * Convert a boolean option to a backend specific value.
     *
     * @param boolean $boolean  The value to convert.
     *
     * @return mixed    Either a backend-specific boolean value or
     *                  1 if true and 0 if false.
     */
    public function booleanToOption($boolean)
    {
        if ($boolean) {
            return defined('_SAM_OPTION_ON') ? _SAM_OPTION_ON : 1;
        } else {
            return defined('_SAM_OPTION_OFF') ? _SAM_OPTION_OFF : 0;
        }
    }

    /**
     * Convert a backend specific boolean value to a PHP boolean.
     *
     * @param mixed $option  The value to convert.
     *
     * @return boolean  True if the backend-specific value is true,
     *                  false otherwise.
     */
    public function optionToBoolean($option)
    {
        return $option === $this->booleanToOption(true);
    }

    /**
     * Retrieve a set SPAM option from user settings or global defaults.
     *
     * @param string $option  The option to retrieve.
     *
     * @return string  The requested option value.
     */
    public function getOption($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        } elseif (isset($this->_defaults[$option])) {
            return $this->_defaults[$option];
        } else {
            return null;
        }
    }

    /**
     * Sets the specified SPAM option value for the user or as global
     * defaults, depending on the parameters. Does not automatically
     * store options to the backend.
     *
     * @param string $option     The option to set.
     * @param string $value      The new value.
     * @param boolean $defaults  Whether to set the global defaults instead of
     *                           user options.
     */
    public function setOption($option, $value, $defaults = false)
    {
        if ($defaults) {
            $this->_defaults[$option] = $value;
        } else {
            $this->_options[$option] = $value;
        }

    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded.
     *
     * This function requests retrieval of related user data and global
     * defaults where supported.
     *
     * @abstract
     */
    public function retrieve()
    {
        return false;
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded.
     *
     * This function request storage of related user data or global
     * defaults where supported.
     *
     * @abstract
     */
    public function store($defaults = false)
    {
        return false;
    }

    /**
     * Retrieve an internal address array in string form of whitelists
     * and blacklists.
     *
     * @param string $option  The option to retrieve and convert to a string
     *                        value.
     *
     * @return string  New-line separated list value of the requested option.
     *
     */
    public function getListOption($option)
    {
        $list = $this->getOption($option);
        return is_array($list) ? implode("\n", array_unique($list)) : $list;
    }

    /**
     * Set an internal address array of whitelists and blacklists. Does not
     * automatically store options to the backend.
     *
     * @param string $option     The option to set.
     * @param string $value      A string of data that will be converted to an
     *                           array and stored for later storage to the
     *                           backend.
     * @param boolean $defaults  Whether to set the global defaults instead of
     *                           user options.
     *
     * @return boolean  Results of the setOption() call.
     */
    public function setListOption($option, $value, $defaults = false)
    {
        $list = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $this->setOption($option, array_unique($list), $defaults);
    }

    /**
     * Sets an internal array of options which have multiple elements of data
     * stored in their value (e.g. rewrite_header takes two elements, a header
     * and a string, as in 'rewrite_header' => 'Subject ***SPAM***').
     *
     * There can be multiple entries for these options, so they cannot be
     * treated independently.
     *
     * Does not automatically store options to the backend.
     *
     * @param string $option     The base option to set.
     *                           Should only be 'rewrite_header' ATM.
     * @param string $value      A string of data that will be converted to an
     *                           array and stored for later storage to the
     *                           backend.
     * @param boolean $defaults  Whether to set the global defaults instead of
     *                           user options.
     *
     * @return boolean  Results of the setOption() call.
     */
    public function setStackedOption($option, $value, $defaults = false)
    {
        $list = preg_split('/\n/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $this->setOption($option, array_unique($list), $defaults);
    }
}
