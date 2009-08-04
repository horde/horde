<?php
/**
 * Template class for application API files.
 *
 * Other Horde-defined API calls
 * =============================
 * Horde_Auth_Application::
 * ------------------------
 *   'authLoginParams' => array(
 *       'args' => array(),
 *       'checkperms' => false,
 *       'type' => '{urn:horde}hashHash'
 *   ),
 *   'authAuthenticate' => array(
 *       'args' => array(
 *           'userID' => 'string',
 *           'credentials' => '{urn:horde}hash',
 *           'params' => '{urn:horde}hash'
 *       ),
 *       'checkperms' => false,
 *       'type' => 'boolean'
 *   ),
 *   'authAuthenticateCallback' => array(
 *       'args' => array(),
 *       'checkperms' => false
 *   ),
 *   'authTransparent' => array(
 *       'args' => array(),
 *       'checkperms' => false,
 *       'type' => 'boolean'
 *   ),
 *   'authAddUser' => array(
 *       'args' => array(
 *           'userId' => 'string',
 *           'credentials' => '{urn:horde}stringArray'
 *       )
 *   ),
 *   'authRemoveUser' => array(
 *       'args' => array(
 *           'userId' => 'string'
 *       )
 *   ),
 *   'authUserList' => array(
 *       'type' => '{urn:horde}stringArray'
 *   )
 *
 * Prefs_UI::
 * ----------
 *   'prefsInit' => array(
 *       'args' => array()
 *   ),
 *   'prefsHandle' => array(
 *       'args' => array(
 *           'item' => 'string',
 *           'updated' => 'boolean'
 *       ),
 *       'type' => 'boolean'
 *   ),
 *   'prefsCallback' => array(
 *       'args' => array()
 *   )
 *
 * TODO:
 * -----
 *   'cacheOutput' => array(
 *       'args' => array(
 *           '{urn:horde}hashHash'
 *       ),
 *       'type' => '{urn:horde}hashHash'
 *   )
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Core
 */
class Horde_Registry_Api
{
    /**
     * Does this application support a mobile view?
     *
     * @var boolean
     */
    public $mobileView = false;

    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'unknown';

    /**
     * The services provided by this application.
     * TODO: Describe structure.
     *
     * @var array
     */
    public $services = array(
        'perms' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
        ),

        'changeLanguage' => array(
            'args' => array(),
            'type' => 'boolean'
        )
    );

    /**
     * TODO
     * TODO: Describe structure.
     *
     * @var array
     */
    public $types = array();

    /* Reserved functions. */

    /**
     * Returns a list of available permissions.
     *
     * @return array  The permissions list.
     *                TODO: Describe structure.
     */
    public function perms()
    {
        return array();
    }

    /**
     * Called when the language is changed.
     */
    public function changeLanguage()
    {
        return array();
    }

}
