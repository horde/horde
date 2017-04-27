<?php
/**
 * Horde_Pear_Package_Type_Horde:: deals with packages provided by Horde.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Deals with packages provided by Horde in the split repository structure.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Type_HordeSplit extends Horde_Pear_Package_Type_Horde
{
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
     * Get the package name.
     *
     * @return string The package name.
     */
    public function getName()
    {
        if ($this->getType() == 'Application') {
            return basename($this->getRootPath());
        } else {
            return 'Horde_' . basename($this->getRootPath());
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
     * The repository root is the same as the package root.
     *
     * @return string The repository path.
     */
    public function getRepositoryRoot()
    {
        return $this->getRootPath();
    }

}
