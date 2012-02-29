<?php
/**
 * Interface for class name to path mappers.
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */

/**
 * Interface for class name to path mappers.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */
interface Horde_Autoloader_ClassPathMapper
{
    /**
     * Map the provided class name to a file path.
     *
     * @param string $className The class name that should be mapped to a path.
     *
     * @return string The path to the source file.
     */
    public function mapToPath($className);
}
