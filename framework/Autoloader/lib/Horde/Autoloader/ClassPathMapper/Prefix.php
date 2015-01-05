<?php
/**
 * Copyright 2008-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */

/**
 * Provides a classmapper that implements generic pattern for different
 * mapping types within the application directory.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Prefix
implements Horde_Autoloader_ClassPathMapper
{
    /**
     * Include path.
     *
     * @var string
     */
    private $_includePath;

    /**
     * PCRE pattern to match in class.
     *
     * @var string
     */
    private $_pattern;

    /**
     * Constructor
     *
     * @param string $pattern      PCRE pattern.
     * @param string $includePath  Include path.
     */
    public function __construct($pattern, $includePath)
    {
        $this->_includePath = $includePath;
        $this->_pattern = $pattern;
    }

    /**
     */
    public function mapToPath($className)
    {
        if (!preg_match($this->_pattern, $className, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        return (strcasecmp($matches[0][0], $className) === 0)
            ? $this->_includePath . '/' . $className . '.php'
            : str_replace(array('\\', '_'), '/', substr($className, 0, $matches[0][1])) .
                  $this->_includePath . '/' .
                  str_replace(array('\\', '_'), '/', substr($className, $matches[0][1] + strlen($matches[0][0]))) .
                  '.php';
    }
}
