<?php
/**
 * Sam_Driver_Base defines an API for implementing storage backends for Sam.
 *
 * @author  Chris Bowlby <excalibur@hub.org>
 * @author  Max Kalika <max@gentoo.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
abstract class Sam_Driver_Base
{
    /**
     * The user's preferences.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * The defaults to use if user hasn't defined a value.
     *
     * @var array
     */
    protected $_defaults = array();

    /**
     * The user name.
     *
     * @var string
     */
    protected $_user;

    /**
     * Capabilities supported by the driver.
     *
     * @var array
     */
    protected $_capabilities = array();

    /**
     * Parameter hash for the backend.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param string $user   A user name.
     * @param array $params  Backend specific class parameters.
     */
    public function __construct($user, $params = array())
    {
        $this->_user = $user;
        $this->_params = $params;
    }

    /**
     * Retrieves user preferences and optionally default values from the
     * backend.
     *
     * @throws Sam_Exception
     */
    abstract public function retrieve();

    /**
     * Stores user preferences and optionally default values in the backend.
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @throws Sam_Exception
     */
    abstract public function store($defaults = false);

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
     * Converts a boolean option to a backend specific value.
     *
     * @param boolean $boolean  The value to convert.
     *
     * @return mixed  1 if true and 0 if false.
     */
    public function booleanToOption($boolean)
    {
        return (int)(bool)$boolean;
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
     * Returns a preference from user settings or global defaults.
     *
     * @param string $option  The option to retrieve.
     *
     * @return mixed  The requested option value.
     */
    public function getOption($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        if (isset($this->_defaults[$option])) {
            return $this->_defaults[$option];
        }
        return null;
    }

    /**
     * Sets a preference value for the user or as global defaults, depending on
     * the parameters.
     *
     * Does not automatically store options to the backend.
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
     * Returns an internal address array as a new-line separated string.
     *
     * Useful for retrieving whitelists and blacklists.
     *
     * @param string $option  The option to retrieve.
     *
     * @return string  New-line separated list value.
     *
     */
    public function getListOption($option)
    {
        $list = $this->getOption($option);
        return is_array($list) ? implode("\n", array_unique($list)) : $list;
    }

    /**
     * Sets an internal address array.
     *
     * Useful for whitelists and blacklists. The passed data is split at
     * whitespace.  Does not automatically store options to the backend.
     *
     * @param string $option     The option to set.
     * @param string $value      A string of data that will be converted to an
     *                           array and stored for later storage to the
     *                           backend.
     * @param boolean $defaults  Whether to set the global defaults instead of
     *                           user options.
     */
    public function setListOption($option, $value, $defaults = false)
    {
        $list = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $this->setOption($option, array_unique($list), $defaults);
    }

    /**
     * Sets an internal array of options which have multiple elements of data
     * stored in their value.
     *
     * E.g. rewrite_header takes two elements, a header and a string, as in
     * 'rewrite_header' => 'Subject ***SPAM***'.
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
     */
    public function setStackedOption($option, $value, $defaults = false)
    {
        $list = preg_split('/\n/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $this->setOption($option, array_unique($list), $defaults);
    }
}
