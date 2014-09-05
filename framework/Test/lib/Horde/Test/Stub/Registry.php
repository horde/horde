<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * A test replacement for Horde_Registry.
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Stub_Registry
{
    /**
     * A flag that is set once the basic horde application has been
     * minimally configured.
     *
     * @var boolean
     */
    public $hordeInit = false;

    /**
     * The currrent user.
     *
     * @var string
     */
    protected $_user;

    /**
     * The current application.
     *
     * @var string
     */
    protected $_app;

    /**
     * Constructor.
     *
     * @param string $user The current user.
     * @param string $app  The current application.
     */
    public function __construct($user, $app)
    {
        $this->_user = $user;
        $this->_app = $app;
    }

    /**
     * Returns the currently logged in user, if there is one.
     *
     * @param string $format  The return format, defaults to the unique Horde
     *                        ID. Alternative formats:
     * <pre>
     * bare - Horde ID without any domain information.
     *        EXAMPLE: foo@example.com would be returned as 'foo'.
     * domain: Domain of the Horde ID.
     *         EXAMPLE: foo@example.com would be returned as 'example.com'.
     * original: The username used to originally login to Horde.
     * </pre>
     *
     * @return mixed  The user ID or false if no user is logged in.
     */
    public function getAuth($format = null)
    {
        return $this->_user;
    }

    /**
     * Is a user an administrator?
     *
     * @param array $options  Options:
     * <pre>
     * 'permission' - (string) Allow users with this permission admin access
     *                in the current context.
     * 'permlevel' - (integer) The level of permissions to check for.
     *               Defaults to Horde_Perms::EDIT.
     * 'user' - (string) The user to check.
     *          Defaults to self::getAuth().
     * </pre>
     *
     * @return boolean  Whether or not this is an admin user.
     */
    public function isAdmin(array $options = array())
    {
        return false;
    }

    /**
     * Load a configuration file from a Horde application's config directory.
     * This call is cached (a config file is only loaded once, regardless of
     * the $vars value).
     *
     * @param string $conf_file  Configuration file name.
     * @param mixed $vars        List of config variables to load.
     * @param string $app        Application.
     *
     * @return Horde_Test_Stub_Registry_Loadconfig  The config object.
     * @throws Horde_Exception
     */
    public function loadConfigFile($conf_file, $vars = null, $app = null)
    {
        if ($conf_file == 'hooks.php') {
            throw new Horde_Exception('Failed to import configuration file "hooks.php".');
        }

        return new Horde_Test_Stub_Registry_Loadconfig(
                $app,
                $conf_file,
                $vars
        );
    }

    /**
     * Return the requested configuration parameter for the specified
     * application. If no application is specified, the value of
     * the current application is used. However, if the parameter is not
     * present for that application, the Horde-wide value is used instead.
     * If that is not present, we return null.
     *
     * @param string $parameter  The configuration value to retrieve.
     * @param string $app        The application to get the value for.
     *
     * @return string  The requested parameter, or null if it is not set.
     */
    public function get($parameter, $app = null)
    {
        return '';
    }

    /**
     * Return the current application - the app at the top of the application
     * stack.
     *
     * @return string  The current application.
     */
    public function getApp()
    {
        return $this->_app;
    }

    /**
     * Determine if an interface is implemented by an active application.
     *
     * @param string $interface  The interface to check for.
     *
     * @return mixed  The application implementing $interface if we have it,
     *                false if the interface is not implemented.
     */
    public function hasInterface($interface)
    {
        return false;
    }

    /**
     * Returns all available registry APIs.
     *
     * @return array  The API list.
     */
    public function listAPIs()
    {
        return array();
    }
}
