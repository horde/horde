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
 * The basic definition for generating an elemnt.
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
class Horde_Provider_Injection_Base implements Horde_Provider_Injection
{

    protected $loading = false;

    /**
     * Create an instance of the proxied object.
     *
     * @param Horde_Provider_Base $provider The class providing additional
     *                                      required dependencies.
     *
     * @return NULL
     */
    public function getInstance(Horde_Provider_Base $provider)
    {
        if ($this->loading) {
            throw new Horde_Provider_Exception('Element already loading!');
        }

        $this->loading = true;
    }
}