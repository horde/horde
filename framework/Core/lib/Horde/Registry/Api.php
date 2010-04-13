<?php
/**
 * Default class for application defined API calls.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Registry_Api
{
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
