<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */

/**
 * Interface for autoloader class path mappers.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
interface Horde_Autoloader_ClassPathMapper
{
    /**
     * Search for a mapping from class to file path.
     *
     * @param string $className  Classname to load.
     *
     * @return mixed  Pathname to class, or false if not found.
     */
    public function mapToPath($className);

}
