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
     * The services provided by this application.
     * TODO: Describe structure.
     *
     * @var array
     */
    public $services = array(
        'perms' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
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
}
