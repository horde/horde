<?php
/**
 * Default class for the Horde Application API.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
     * Initialization. Does any necessary init needed to setup the full
     * environment for the application.
     */
    public function init()
    {
    }

}
