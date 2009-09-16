<?php
/**
 * A simple module for dependency injection.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Provider
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Provider
 */

/**
 * The interface that describes the binding classes.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Provider
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Provider
 */
Interface Horde_Provider_Injection
{
    /**
     * Create an instance of the proxied object.
     *
     * @param Horde_Provider_Base $provider The class providing additional
     *                                      required dependencies.
     *
     * @return mixed The generated instance.
     */
    public function getInstance(Horde_Provider_Base $provider);
}