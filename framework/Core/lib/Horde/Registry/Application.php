<?php
/**
 * Default class for the Horde Application API.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Core
 */
class Horde_Registry_Application
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
     * The list of disabled API calls.
     *
     * @var array
     */
    public $disabled = array();

    /**
     * The init params used.
     *
     * @var array
     */
    public $initParams = array();

    /**
     * Has init() previously been called?
     *
     * @var boolean
     */
    protected $_initDone = false;

    /**
     * Application-specific code to run if application auth fails.
     * Called from Horde_Registry::appInit().
     *
     * @param Horde_Exception $e  The exception object.
     */
    public function appInitFailure($e)
    {
    }

    /**
     * Initialization. Does any necessary init needed to setup the full
     * environment for the application.
     *
     * Global constants defined:
     * <pre>
     * [APPNAME]_TEMPLATES - (string) Location of template files.
     * </pre>
     */
    public function init()
    {
        if (!$this->_initDone) {
            $this->_initDone = true;

            $appname = Horde_String::upper($GLOBALS['registry']->getApp());
            if (!defined($appname . '_TEMPLATES')) {
                define($appname . '_TEMPLATES', $GLOBALS['registry']->get('templates'));
            }

            $this->_init();
            $this->_initNotification($GLOBALS['notification']);
        }
    }

    /**
     * Initialization code for an application should be defined in this
     * function.
     */
    protected function _init()
    {
    }

    /**
     * Initialization for Notification system.
     * This is run after _init() is called so app-specific code can appear
     * inside.
     *
     * @param Horde_Notification_Handler_Base $notify  The notification
     *                                                 object.
     */
    protected function _initNotification($notify)
    {
        $notify->attach('status');
    }

}
