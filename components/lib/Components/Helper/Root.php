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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Root:: handles the root position for a tree of dependencies
 * and takes the Horde component layout into account.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * Path used to determine the root of the Horde repository.
     *
     * @var string
     */
    private $_path;

    /**
     * Component used to determine the root of the Horde repository.
     *
     * @var Components_Component
     */
    private $_component;

    /**
     * Options used to determine the root of the Horde repository.
     *
     * @var array
     */
    private $_options;

    /**
     * Errors that occured while trying to determine the root path.
     *
     * @var array
     */
    private $_errors = array();

    /**
     * Constructor.
     *
     * @param string               $path If given the helper will try to
     *                                   determine the root of the Horde
     *                                   repository based on this path.
     * @param Components_Component $comp If given the helper will try to
     *                                   determine the root of the Horde
     *                                   repository based on this component.
     * @param array                $opts If given the helper will try to
     *                                   determine the root of the Horde
     *                                   repository based on these options.
     */
    public function __construct(
        $opts = array(),
        Components_Component $comp = null,
        $path = null
    )
    {
        $this->_path = $path;
        $this->_component = $comp;
        $this->_options   = $opts;
    }

    /**
     * Return the path to the package.xml for the package with the provided
     * name.
     *
     * @param string $name The name of the package.
     *
     * @return string The path to the package.xml of the requested package.
     *
     * @throws Components_Exception If the Horde repository root could not be
     *                              determined.
     */
    public function getPackageXml($name)
    {
        $package_file = $this->getRoot() . '/' . $name . '/package.xml';
        if (!file_exists($package_file)) {
            $package_file = $this->getRoot() . '/framework/' . $name
                . '/package.xml';
        }
        if (!file_exists($package_file) && substr($name, 0, 6) == 'Horde_') {
            $package_file = $this->getRoot() . '/framework/'
                . substr($name, 6) . '/package.xml';
        }
        if (!file_exists($package_file)) {
            $package_file = $this->getRoot() . '/bundles/' . $name
                . '/package.xml';
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
     *
     * @throws Components_Exception If the Horde repository root could not be
     *                              determined.
     */
    public function getGitIgnore()
    {
        return file_get_contents($this->getRoot() . '/.gitignore');
    }

    /**
     * Return the root position of the repository.
     *
     * @return string The root path.
     *
     * @throws Components_Exception If the Horde repository root could not be
     *                              determined.
     */
    public function getRoot()
    {
        if (empty($this->_root_path)) {
            $this->_root_path = $this->_determineRoot();
        }
        return $this->_root_path;
    }

    /**
     * Try to determine the root path.
     *
     * @return string The root path.
     *
     * @throws Components_Exception If the Horde repository root could not be
     *                              determined.
     */
    private function _determineRoot()
    {
        if (($result = $this->_determineRootFromOptions()) !== false) {
            return $result;
        }
        if (($result = $this->_determineRootFromComponent()) !== false) {
            return $result;
        }
        if (($result = $this->_determineRootFromPath()) !== false) {
            return $result;
        }
        if (($result = $this->_determineRootFromCwd()) !== false) {
            return $result;
        }
        throw new Components_Exception(
            sprintf(
                'Unable to determine Horde root (%s)',
                join(', ', $this->_errors)
            )
        );
    }

    /**
     * Try to determine the root path based on a fixed path.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    private function _determineRootFromPath()
    {
        if (!empty($this->_path)) {
            if (($result = $this->traverseHierarchy($this->_path)) === false) {
                $this->_errors[] = sprintf(
                    'Unable to determine Horde repository root from path "%s"!',
                    $this->_path
                );
            }
            return $result;
        }
        return false;
    }

    /**
     * Try to determine the root path based on a component.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    private function _determineRootFromComponent()
    {
        if (!empty($this->_component)) {
            try {
                return $this->_component->repositoryRoot($this);
            } catch (Components_Exception $e) {
                $this->_errors[] = sprintf(
                    'Component %s has no repository root!',
                    $this->_component->getName()
                );
                return false;
            }
        }
        return false;
    }

    /**
     * Try to determine the root path based on the options.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    private function _determineRootFromOptions()
    {
        if (isset($this->_options['horde_root'])) {
            if ($this->_isValidRoot($this->_options['horde_root'])) {
                return $this->_options['horde_root'];
            }
            $this->_errors[] = sprintf(
                'The path "%s" does not seem to represent the root of the Horde repository!',
                $this->_options['horde_root']
            );
        }
        return false;
    }

    /**
     * Try to determine the root path based on the current working directory.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    private function _determineRootFromCwd()
    {
        if (($result = $this->traverseHierarchy(getcwd())) === false) {
            $this->_errors[] = sprintf(
                'Unable to determine Horde repository root from the current working directory "%s"!',
                getcwd()
            );
            return false;
        }
        return $result;
    }

    /**
     * Traverse the folder tree upwards to determine if a parent folder of the
     * provided file path might be the Horde repository root.
     *
     * @param string $start Path to the file to start from.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    public function traverseHierarchy($start)
    {
        $i = 0;
        $origin = $start;
        while ($start != '/' || $i < 10) {
            if ($this->_isValidRoot($start) !== false) {
                return $start;
            }
            $start = dirname($start);
            $i++;
        }
        return false;
    }

    /**
     * Test if the directory path could be the Horde repository root.
     *
     * @param string $directory Path to the directory to test.
     *
     * @return string|boolean The root path or false if it could not be
     *                        determined.
     */
    private function _isValidRoot($directory)
    {
        if (is_dir($directory)) {
            $objects = scandir($directory);
            if (in_array('framework', $objects)
                && in_array('horde', $objects)
                && in_array('.gitignore', $objects)) {
                return $directory;
            }
        }
        return false;
    }
}