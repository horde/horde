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
 * Provides a classmapper that implements generic pattern for different
 * mapping types within the application directory.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Application
implements Horde_Autoloader_ClassPathMapper
{
    /**
     * The following constants are for naming the positions in the regex for
     * easy readability later.
     */
    const APPLICATION_POS = 1;
    const ACTION_POS = 2;
    const SUFFIX_POS = 3;

    const NAME_SEGMENT = '([0-9A-Z][0-9A-Za-z]+)+';

    /**
     * @var string
     */
    protected $_appDir;

    /**
     * @var array
     */
    protected $_mappings = array();

    /**
     * Constructor.
     *
     * @param string $appDir
     */
    public function __construct($appDir)
    {
        $this->_appDir = rtrim($appDir, '/') . '/';
    }

    /**
     * @param string $classSuffix
     * @param string $subDir
     */
    public function addMapping($classSuffix, $subDir)
    {
        $this->_mappings[$classSuffix] = $subDir;
        $this->_classMatchRegex = '/^' . self::NAME_SEGMENT . '_' .
            self::NAME_SEGMENT . '_' .
            '(' . implode('|', array_keys($this->_mappings)) . ')$/';
    }

    /**
     */
    public function mapToPath($className)
    {
        if (preg_match($this->_classMatchRegex, $className, $matches)) {
            return $this->_appDir . $this->_mappings[$matches[self::SUFFIX_POS]] . '/' . $matches[self::ACTION_POS] . '.php';
        }
    }

    /**
     * String representation of class.
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this) . ' ' . $this->_classMatchRegex . ' [' . $this->_appDir . ']';
    }

}
