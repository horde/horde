<?php
/**
 * Provides a generic pattern for different mapping types within the application
 * directory.
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */

/**
 * Provides a generic pattern for different mapping types within the application
 * directory.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Application implements Horde_Autoloader_ClassPathMapper
{
    /**
     * The full path to the application directory.
     *
     * @var string
     */
    protected $_appDir;

    /**
     * Mappings within the application directory. Class names suffixes are used
     * as keys, the application sub directories are the values.
     *
     * @var array
     */
    protected $_mappings = array();

    /**
     * The following constants are for naming the positions in the regex for
     * easy readability later.
     */
    const APPLICATION_POS = 1;
    const ACTION_POS = 2;
    const SUFFIX_POS = 3;

    const NAME_SEGMENT = '([0-9A-Z][0-9A-Za-z]+)+';

    /**
     * Constructor.
     *
     * @param string $appDir The path to the application directory.
     */
    public function __construct($appDir)
    {
        $this->_appDir = rtrim($appDir, '/') . '/';
    }

    /**
     * Add a mapping that will map App_SomethingSuffix to
     * APP/SUBDIR/SUFFIX/Something.php.
     *
     * @param string $classSuffix The class name suffix that specifies the
     *                            application directory.
     * @param string $subDir      The directory within the $appDir of this mapper.
     */
    public function addMapping($classSuffix, $subDir)
    {
        $this->_mappings[$classSuffix] = $subDir;
        $this->_classMatchRegex = '/^' . self::NAME_SEGMENT . '_' . self::NAME_SEGMENT . '_('
            . implode('|', array_keys($this->_mappings)) . ')$/';
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
        if (preg_match($this->_classMatchRegex, $className, $matches)) {
            return $this->_appDir . $this->_mappings[$matches[self::SUFFIX_POS]] . '/' . $matches[self::ACTION_POS] . '.php';
        }
    }

    /**
     * Return a description of the class path mapper.
     *
     * @return string A string describing the patterns this class path mapper
     * uses.
     */
    public function __toString()
    {
        return get_class($this) . ' ' . $this->_classMatchRegex . ' [' . $this->_appDir . ']';
    }
}
