<?php
/**
 * Components_Helper_Root:: handles the root position for a tree of dependencies
 * and takes the Horde component layout into account.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Root:: handles the root position for a tree of dependencies
 * and takes the Horde component layout into account.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_Root
{
    /**
     * Root path of the Horde repository.
     *
     * @var string
     */
    private $_root_path;

    /**
     * Relative position of the path that has been used to determine the root positionq.
     *
     * @var string
     */
    private $_base = '';

    /**
     * Constructor.
     *
     * @param string $path The helper will try to determine the root of the
     * Horde repository based on this path.
     */
    public function __construct($path)
    {
        $i = 0;
        $root = 0;
        $current = $path;
        while ($current != '/' || $i < 10) {
            if (is_dir($current)) {
                $objects = scandir($current);
                if (in_array('framework', $objects)
                    && in_array('horde', $objects)
                    && in_array('.gitignore', $objects)) {
                    $this->_root_path = $current;
                    break;
                }
            }
            $this->_base .= basename($current) . DIRECTORY_SEPARATOR;
            $current = dirname($current);
            $i++;
        }
        if ($i >= 10) {
            throw new Components_Exception(sprintf('Unable to determine Horde root from path %s!', $path));
        }
    }

    /**
     * Return the path to the package.xml for the package with the provided
     * name.
     *
     * @param string $name The name of the package.
     *
     * @return string The path to the package.xml of the requested package.
     */
    public function getPackageXml($name)
    {
        $package_file = $this->_root_path . DIRECTORY_SEPARATOR
            . $name . DIRECTORY_SEPARATOR . 'package.xml';
        if (!file_exists($package_file)) {
            $package_file = $this->_root_path . DIRECTORY_SEPARATOR
                . 'framework' . DIRECTORY_SEPARATOR . $name
                . DIRECTORY_SEPARATOR . 'package.xml';
        }
        if (!file_exists($package_file) && substr($name, 0, 6) == 'Horde_') {
            $package_file = $this->_root_path . DIRECTORY_SEPARATOR
                . 'framework' . DIRECTORY_SEPARATOR . substr($name, 6)
                . DIRECTORY_SEPARATOR . 'package.xml';
        }
        if (!file_exists($package_file)) {
            throw new Components_Exception(sprintf('Unknown package %s.', $name));
        }
        return $package_file;
    }

    /**
     * Return the contents of the gitignore file.
     *
     * @return string The information from the gitignore file.
     */
    public function getGitIgnore()
    {
        return file_get_contents($this->_root_path . DIRECTORY_SEPARATOR . '.gitignore');
    }

    /**
     * Return the relative position of the path originally used to determine the
     * root position of the repository.
     *
     * @return string The relative path.
     */
    public function getBase()
    {
        return $this->_base;
    }
}