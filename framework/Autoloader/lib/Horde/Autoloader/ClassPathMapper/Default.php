<?php
/**
 * Copyright 2008-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */

/**
 * Provides classmapper that maps classes to paths following the PHP Framework
 * Interop Group PSR-0 reference implementation.
 *
 * Under this guideline, the following rules apply:
 *
 * - Each namespace separator is converted to a DIRECTORY_SEPARATOR when
 *   loading from the file system.
 * - Each "_" character in the CLASS NAME is converted to a
 *   DIRECTORY_SEPARATOR. The "_" character has no special meaning in the
 *   namespace.
 * - The fully-qualified namespace and class is suffixed with ".php" when
 *   loading from the file system.
 *
 * Examples:
 *
 * - \Doctrine\Common\IsolatedClassLoader =>
 *   /path/to/project/lib/vendor/Doctrine/Common/IsolatedClassLoader.php
 * - \namespace\package\Class_Name =>
 *   /path/to/project/lib/vendor/namespace/package/Class/Name.php
 * - \namespace\package_name\Class_Name =>
 *   /path/to/project/lib/vendor/namespace/package_name/Class/Name.php
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Default
implements Horde_Autoloader_ClassPathMapper
{
    /**
     * @var string
     */
    private $_includePath;

    /**
     * Constructor.
     *
     * @param string $includePath
     */
    public function __construct($includePath)
    {
        $this->_includePath = $includePath;
    }

    /**
     */
    public function mapToPath($className)
    {
        // @FIXME: Follow reference implementation
        return $this->_includePath . DIRECTORY_SEPARATOR .
            str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $className) .
            '.php';
    }

}
