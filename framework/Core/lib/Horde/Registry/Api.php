<?php
/**
 * Template class for application API files.
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
     * Links.
     *
     * @var array
     */
    public $links = array();

    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    public $noPerms = array();

    /**
     * The list of disabled API calls.
     *
     * @var array
     */
    public $disabled = array();


    /* API calls should be declared as public functions, with the function
     * name corresponding to the API name. Create any internal helper
     * functions as protected functions. */

}
