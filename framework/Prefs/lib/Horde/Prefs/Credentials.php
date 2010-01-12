<?php
/**
 * Class for handling a list of credentials stored in a user's preferences.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_Prefs
 */
class Horde_Prefs_Credentials
{
    /**
     * Singleton instance.
     *
     * @var Horde_Prefs_Credentials
     */
    static protected $_instance = null;

    /**
     * Cache for getCredentials().
     *
     * @var array
     */
    static protected $_credentialsCache = null;

    /**
     * The Horde application currently processed.
     *
     * @see singleton()
     * @var string
     */
    protected $app;

    /**
     * A list of preference field names and their values.
     *
     * @var array
     */
    protected $_credentials = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $credentials = @unserialize($GLOBALS['prefs']->getValue('credentials'));
        if ($credentials) {
            foreach ($credentials as $app => $app_prefs) {
                foreach ($app_prefs as $name => $value) {
                    $this->_credentials['credentials[' . $app . '][' . $name . ']'] = $value;
                }
            }
        }
    }

    /**
     * Returns a single instance of the Prefs_Credentials class, and sets the
     * curently processed application.
     *
     * @param string $app  The current application.
     *
     * @return Prefs_Credentials  A Prefs_Credentials instance.
     */
    static public function singleton($app)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        self::$_instance->app = $app;

        return self::$_instance;
    }

    /**
     * Returns a list of available credentials collected from all Horde
     * applications.
     *
     * @return array  A list of Horde applications and their credentials.
     */
    static public function getCredentials()
    {
        if (!is_null(self::$_credentialsCache)) {
            return self::$_credentialsCache;
        }

        self::$_credentialsCache = array();
        foreach ($GLOBALS['registry']->listApps() as $app) {
            try {
                $credentials = $GLOBALS['registry']->callAppMethod($app, 'authCredentials');
            } catch (Horde_Exception $e) {
                continue;
            }

            if (!count($credentials)) {
                continue;
            }

            self::$_credentialsCache[$app] = array();
            foreach ($credentials as $name => $credential) {
                $pref = 'credentials[' . $app . '][' . $name . ']';
                $credential['shared'] = true;
                self::$_credentialsCache[$app][$pref] = $credential;
            }
        }

        return self::$_credentialsCache;
    }

    /**
     * Displays the preference interface for setting all available
     * credentials.
     */
    static public function showUi()
    {
        $credentials = self::getCredentials();
        $vspace = '';
        foreach ($credentials as $app => $_prefs) {
            $prefs = Horde_Prefs_Credentials::singleton($app);
            echo $vspace . '<h2 class="smallheader">';
            printf(_("%s authentication credentials"),
                   $GLOBALS['registry']->get('name', $app));
            echo '</h2>';
            foreach (array_keys($_prefs) as $pref) {
                $helplink = empty($_prefs[$pref]['help'])
                    ? null
                    : Horde_Help::link(!empty($_prefs[$pref]['shared']) ? 'horde' : $GLOBALS['registry']->getApp(), $_prefs[$pref]['help']);
                require $GLOBALS['registry']->get('templates') . '/prefs/' . $_prefs[$pref]['type'] . '.inc';
            }
            $vspace = '<br />';
        }
    }

    /**
     * Returns the value of a credential for the currently processed
     * application.
     *
     * @see Horde_Prefs::getValue()
     *
     * @param string $pref  A credential name.
     *
     * @return mixed  The credential's value, either from the user's
     *                preferences, or from the default value, or null.
     */
    public function getValue($pref)
    {
        if (isset($this->_credentials[$pref])) {
            return $this->_credentials[$pref];
        }
        $credentials = $this->getCredentials();

        return isset($credentials[$this->app][$pref]['value'])
            ? $credentials[$this->app][$pref]['value']
            : null;
    }

}
