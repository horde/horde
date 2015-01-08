<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

/**
 * Provides a classmapper that loads Horde libraries from a directory
 * containing the git development source trees of the libraries.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */
class Horde_Autoloader_ClassPathMapper_HordeDevel
implements Horde_Autoloader_ClassPathMapper
{
    const PREFIX = 'Horde';
    const PREFIX_LEN = 5;

    /**
     * Library base path.
     *
     * @var string
     */
    private $_libraryPath;

    /**
     * Constructor
     *
     * @param string $libraryPath  Library base path.
     */
    public function __construct($libraryPath)
    {
        $this->_libraryPath = rtrim($libraryPath, '/') . '/';
    }

    /**
     */
    public function mapToPath($className)
    {
        if (stripos($className, self::PREFIX) === 0) {
            if (strlen($className) == self::PREFIX_LEN) {
                return $this->_libraryPath . 'Core/lib/Horde.php';
            }

            $className = strtr($className, '\\', '_');

            if (($c = $className[self::PREFIX_LEN]) && ($c == '_')) {
                $curr = substr($className, self::PREFIX_LEN + 1);
                do {
                    if (file_exists($this->_libraryPath . $curr)) {
                        $file = $this->_libraryPath . $curr . '/lib/' .
                            str_replace($c, '/', $className) . '.php';
                        if (file_exists($file)) {
                            return $file;
                        }
                    }

                    if (($pos = strrpos($curr, $c)) === false) {
                        break;
                    }

                    $curr = substr($curr, 0, $pos);
                } while (true);

                /* Check for Core/Util libraries. */
                foreach (array('Core', 'Util') as $val) {
                    $file = $this->_libraryPath . $val . '/lib/' .
                        str_replace($c, '/', $className) . '.php';
                    if (file_exists($file)) {
                        return $file;
                    }
                }
            }
        }

        return false;
    }

}
