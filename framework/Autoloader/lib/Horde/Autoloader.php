<?php
/**
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */

/**
 * Horde_Autoloader interface. Horde_Autoloader manages an application's class
 * name to file name mapping conventions. One or more class-to-filename
 * mappers are defined, and are searched in LIFO order.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */
interface Horde_Autoloader
{
    /**
     * Register this instance as autoloader.
     *
     * @param boolean $prepend  If true, the autoloader will be prepended on
     *                          the autoload stack instead of appending it.
     */
    public function registerAutoloader($prepend = false);

    /**
     * Try to load the definition for the provided class name.
     *
     * @param string $className  The name of the undefined class.
     *
     * @return boolean  True if loading the class succeeded.
     */
    public function loadClass($className);

    /**
     * Try to load a class from the provided path.
     *
     * @param string $path       The path to the source file.
     * @param string $className  The class to load.
     *
     * @return boolean  True if loading the class succeeded.
     */
    public function loadPath($path, $className);

    /**
     * Map a class name to a file path. The registered mappers will be
     * searched in LIFO order.
     *
     * @param string $className  The class name that should be mapped to a
     *                           path.
     *
     * @return string  The path name to the source file.
     */
    public function mapToPath($className);

}
