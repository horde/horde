<?php
/**
 * Horde_Pear_Package_Type_Horde:: deals with packages provided by Horde.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Type_Horde:: deals with packages provided by Horde.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Type_Horde
implements Horde_Pear_Package_Type
{
    /**
     * The root path for the package.
     *
     * @var string
     */
    private $_root;

    /**
     * The root path for the repository.
     *
     * @var string
     */
    private $_repository_root;

    /**
     * Constructor.
     *
     * @param string $root The root path for the package.
     */
    public function __construct($root)
    {
        $this->_root = $root;
    }

    /**
     * Return the path to the root of the package.
     *
     * @return string The path to the root.
     */
    public function getRootPath()
    {
        return $this->_root;
    }

    /**
     * Return the path to the package.xml file for the package.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXmlPath()
    {
        return $this->_root . '/package.xml';
    }

    /**
     * Return the include handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Include The include handler.
     */
    public function getInclude()
    {
        return new Horde_Pear_Package_Contents_Include_All();
    }

    /**
     * Return the ignore handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Ignore The ignore handler.
     */
    public function getIgnore()
    {
        return new Horde_Pear_Package_Contents_Ignore_Composite(
            array(
                new Horde_Pear_Package_Contents_Ignore_Dot(),
                new Horde_Pear_Package_Contents_Ignore_Hidden(),
                new Horde_Pear_Package_Contents_Ignore_Patterns(
                    array('package.xml', '*~', 'conf.php', 'CVS/*')
                ),
                new Horde_Pear_Package_Contents_Ignore_Git(
                    $this->getGitIgnore(),
                    $this->getRepositoryRoot()
                ),
            )
        );
    }

    /**
     * Return the role handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Role The role handler.
     */
    public function getRole()
    {
        $class = 'Horde_Pear_Package_Contents_Role_Horde' . $this->getType();
        return new $class();
    }

    /**
     * Return the install-as handler for this package.
     *
     * @return Horde_Pear_Package_Contents_InstallAs The install-as handler.
     */
    public function getInstallAs()
    {
        $class = 'Horde_Pear_Package_Contents_InstallAs_Horde' . $this->getType();
        return new $class();
    }

    /**
     * Get the package type.
     *
     * @return string The type: "Application" or "Component".
     */
    public function getType()
    {
        $location = substr($this->getRootPath(), strlen($this->getRepositoryRoot()));
        if (substr($location, 0, 10) == '/framework') {
            return 'Component';
        } else {
            return 'Application';
        }
    }

    /**
     * Return the contents of the Horde .gitignore file.
     *
     * @return string The .gitignore patterns.
     */
    public function getGitIgnore()
    {
        return file_get_contents($this->getRepositoryRoot() . '/.gitignore');
    }

    /**
     * Return the path to the root of the Horde repository..
     *
     * @return string The repository path.
     */
    public function getRepositoryRoot()
    {
        if ($this->_repository_root === null) {
            $i = 0;
            $current = $this->getRootPath();
            while ($current != '/' || $i < 10) {
                if (is_dir($current)) {
                    $elements = scandir($current);
                    if (in_array('framework', $elements)
                        && in_array('horde', $elements)
                        && in_array('.gitignore', $elements)) {
                        $this->_repository_root = $current;
                        break;
                    }
                }
                $current = dirname($current);
                $i++;
            }
            if ($this->_repository_root === null) {
                throw new Horde_Pear_Exception(
                    sprintf(
                        'Unable to determine Horde root from path %s!',
                        $this->getRootPath()
                    )
                );
            }
        }
        return $this->_repository_root;
    }
}