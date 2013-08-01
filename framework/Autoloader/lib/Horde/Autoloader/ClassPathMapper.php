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
 * Interface for class name to path mappers.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */
interface Horde_Autoloader_ClassPathMapper
{
    /**
     * Map the provided class name to a file path.
     *
     * @param string $className  The class name that should be mapped to a
     *                           path.
     *
     * @return string  The path to the source file.
     */
    public function mapToPath($className);

}
