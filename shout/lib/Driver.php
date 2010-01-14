<?php
/**
 * Shout_Driver:: defines an API for implementing storage backends for Shout.
 *
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class Shout_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array $_params
     */
    var $_params = array();

    function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
    * Get a list of contexts from the instantiated driver and filter
    * the returned contexts for those which the current user can see/edit
    *
    * @param optional string $filter Filter for types of contexts to return.
    *                                One of "system" "customer" or "all"
    *
    * @param optional string $filterperms Filter contexts for given permissions
    *
    * @return array Contexts valid for this user
    *
    * @access public
    */
    function getContexts($filters = "all", $filterperms = null)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * For the given context and type, make sure the context has the
     * appropriate properties, that it is effectively of that "type"
     *
     * @param string $context the context to check type for
     *
     * @param string $type the type to verify the context is of
     *
     * @return boolean true of the context is of type, false if not
     *
     * @access public
     */
    function checkContextType($context, $type)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a list of users valid for the current context.  Return an array
     * indexed by the extension.
     *
     * @param string $context Context for which users should be returned
     *
     * @return array User information indexed by voice mailbox number
     */
    function getUsers($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Returns the name of the user's default context
     *
     * @return string User's default context
     */
    function getHomeContext()
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a context's properties
     *
     * @param string $context Context to get properties for
     *
     * @return integer Bitfield of properties valid for this context
     */
    function getContextProperties($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a context's extensions and return as a multi-dimensional associative
     * array
     *
     * @param string $context Context to return extensions for
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    function getDialplan($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Save an extension to the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param string $context Context to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveExtension($context, $extension, $details)
    {
        if (empty($context) || empty($extension)) {
            throw new Shout_Exception(_("Invalid extension."));
        }
        
        if (!Shout::checkRights("shout:contexts:$context:extensions", PERMS_EDIT, 1)) {
            throw new Shout_Exception(_("Permission denied to save extensions in this context."));
        }
    }

    public function deleteExtension($context, $extension)
    {
        if (empty($context) || empty($extension)) {
            throw new Shout_Exception(_("Invalid extension."));
        }

        if (!Shout::checkRights("shout:contexts:$context:extensions",
            PERMS_DELETE, 1)) {
            throw new Shout_Exception(_("Permission denied to delete extensions in this context."));
        }
    }

    /**
     * Save a device to the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param string $context Context to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveDevice($context, $devid, &$details)
    {
        if (empty($context)) {
            throw new Shout_Exception(_("Invalid device information."));
        }

        if (!Shout::checkRights("shout:contexts:$context:devices", PERMS_EDIT, 1)) {
            throw new Shout_Exception(_("Permission denied to save devices in this context."));
        }

        if (empty($devid) || !empty($details['genauthtok'])) {
            list($devid, $password) = Shout::genDeviceAuth($context);
            $details['devid'] = $devid;
            $details['password'] = $password;
        }


    }

    /**
     * Delete a device from the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param <type> $context
     * @param <type> $devid
     */
    public function deleteDevice($context, $devid)
    {
        if (empty($context) || empty($devid)) {
            throw new Shout_Exception(_("Invalid device."));
        }

        if (!Shout::checkRights("shout:contexts:$context:devices",
            PERMS_DELETE, 1)) {
            throw new Shout_Exception(_("Permission denied to delete devices in this context."));
        }
    }

    /**
     * Attempts to return a concrete Shout_Driver instance based on
     * $driver.
     *
     * @param string $driver  The type of the concrete Shout_Driver subclass
     *                        to return.  The class name is based on the storage
     *                        driver ($driver).  The code is dynamically
     *                        included.
     *
     * @param array  $params  (optional) A hash containing any additional
     *                        configuration or connection parameters a
     *                        subclass might need.
     *
     * @return mixed  The newly created concrete Shout_Driver instance, or
     *                false on an error.
     */
    function &factory($class, $driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf'][$class]['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            if ($GLOBALS['conf'][$class]['params']['driverconfig'] == 'horde') {
                $params = array_merge(Horde::getDriverConfig('storage', $driver),
                                      $GLOBALS['conf'][$class]['params']);
            } else {
                $params = $GLOBALS['conf'][$class]['params'];
            }
        }

        $params['class'] = $class;

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Shout_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

}
