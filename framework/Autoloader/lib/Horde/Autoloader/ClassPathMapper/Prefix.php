<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
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
 * Load classes from a specific path matching a specific prefix.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Prefix
implements Horde_Autoloader_ClassPathMapper
{
    /**
     * The base path for class files.
     *
     * @var string
     */
    private $_includePath;

    /**
     * The prefix that the class name must match to be considered loadable by
     * this class.
     *
     * @var string
     */
    private $_pattern;

    /**
     * Constructor.
     *
     * @param string $pattern      The prefix pattern the class name should
     *                             match.
     * @param string $includePath  The base path for class files.
     */
    public function __construct($pattern, $includePath)
    {
        $this->_pattern = $pattern;
        $this->_includePath = $includePath;
    }

    /**
     */
    public function mapToPath($className)
    {
        if (!preg_match($this->_pattern, $className, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        if (strcasecmp($matches[0][0], $className) === 0) {
            return "$this->_includePath/$className.php";
        }

        return str_replace(array('\\', '_'), '/', substr($className, 0, $matches[0][1])) .
            $this->_includePath . '/' .
            str_replace(array('\\', '_'), '/', substr($className, $matches[0][1] + strlen($matches[0][0]))) .
            '.php';
    }

}
