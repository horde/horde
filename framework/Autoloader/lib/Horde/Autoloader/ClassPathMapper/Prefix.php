<?php
/**
 * Load classes from a specific path matching a specific prefix.
 *
 * PHP5
 *
 * @category Horde
 * @package  Autoloader
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */

/**
 * Load classes from a specific path matching a specific prefix.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Prefix implements Horde_Autoloader_ClassPathMapper
{
    /**
     * The prefix that the class name must match to be considered loadable by
     * this class.
     *
     * @var string
     */
    private $_pattern;

    /**
     * The base path for class files.
     *
     * @var string
     */
    private $_includePath;

    /**
     * Constructor.
     *
     * @param string $pattern The prefix pattern the class name should match.
     * @param string $includePath The base path for class files.
     */
    public function __construct($pattern, $includePath)
    {
        $this->_pattern = $pattern;
        $this->_includePath = $includePath;
    }

    /**
     * Map the provided class name to a file path.
     *
     * @param string $className The class name that should be mapped to a path.
     *
     * @return string The path to the source file.
     */
    public function mapToPath($className)
    {
        if (preg_match($this->_pattern, $className, $matches, PREG_OFFSET_CAPTURE)) {
            if (strcasecmp($matches[0][0], $className) === 0) {
                return "$this->_includePath/$className.php";
            } else {
                return str_replace(array('\\', '_'), '/', substr($className, 0, $matches[0][1])) .
                    $this->_includePath . '/' .
                    str_replace(array('\\', '_'), '/', substr($className, $matches[0][1] + strlen($matches[0][0]))) .
                    '.php';
            }
        }

        return false;
    }
}
