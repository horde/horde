<?php
/**
 * Components_Pear_Dependencies:: provides dependency handling mechanisms.
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
 * Components_Pear_Dependencies:: provides dependency handling mechanisms.
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
class Components_Pear_Dependencies
{
    /**
     * The package.
     *
     * @param Components_Pear_Package
     */
    private $_package;

    /**
     * This helper will handle the dependencies for the package provided here.
     *
     * @param Components_Pear_Package
     *
     * @return NULL
     */
    public function setPackage(Components_Pear_Package $package)
    {
        $this->_package = $package;
    }

    /**
     * Return the PEAR package for this package.
     *
     * @return Components_Pear_Package
     */
    public function getPackage()
    {
        if ($this->_package === null) {
            throw new Component_Exception('You need to set the package first!');
        }
        return $this->_package;
    }

    /**
     * Return all channels required for the package and its dependencies.
     *
     * @return array The list of channels.
     */
    public function listAllChannels()
    {
        $channel = array();
        foreach ($this->_getDependencies() as $dependency) {
            if (isset($dependency['channel'])) {
                $channel[] = $dependency['channel'];
            }
        }
        $channel[] = $this->getPackage()->getChannel();
        return array_unique($channel);
    }    

    /**
     * Return all dependencies required for this package.
     *
     * @return array The list of required dependencies.
     */
    public function listAllRequiredDependencies()
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if ($d->isRequired()) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }

    /**
     * Return all Horde dependencies required for this package.
     *
     * @return array The list of Horde dependencies.
     */
    public function listAllHordeDependencies()
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if ($d->isHorde()) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }    

    /**
     * Return the Horde dependencies required for this package.
     *
     * @param string $include Optional dependencies to include.
     * @param string $exclude Optional dependencies to exclude.
     *
     * @return array The list of Horde dependencies.
     */
    public function listHordeDependencies($include, $exclude)
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if ($d->isHorde()
                && ($d->isRequired()
                    || ($d->matches($include) && !$d->matches($exclude))
                )
            ) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }    

    /**
     * Return the Horde dependencies absolutely required for this package.
     *
     * @return array The list of Horde dependencies.
     */
    public function listRequiredHordeDependencies()
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if ($d->isHorde() && $d->isRequired()) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }    

    /**
     * Return all external non Horde package dependencies required for this package.
     *
     * @return array The list of external dependencies.
     */
    public function listAllExternalDependencies()
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if (!$d->isHorde() && !$d->isPhp()) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }

    /**
     * Return the external non Horde package dependencies for this package.
     *
     * @param string $include Optional dependencies to include.
     * @param string $exclude Optional dependencies to exclude.
     *
     * @return array The list of external dependencies.
     */
    public function listExternalDependencies($include, $exclude)
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if (!$d->isHorde() && !$d->isPhp() && $d->isPackage()
                && ($d->isRequired()
                    || ($d->matches($include) && !$d->matches($exclude))
                )
            ) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }

    /**
     * Return the required external dependencies.
     *
     * @return array The list of external dependencies.
     */
    public function listRequiredExternalDependencies()
    {
        $dependencies = array();
        foreach ($this->_getDependencies() as $dependency) {
            $d = new Components_Pear_Dependency($dependency);
            if (!$d->isHorde() && !$d->isPhp() && $d->isRequired()) {
                $dependencies[$d->key()] = $d;
            }
        }
        return $dependencies;
    }

    private function _getDependencies()
    {
        $dependencies = $this->getPackage()->getDependencies();
        if (empty($dependencies)) {
            return array();
        }
        return $dependencies;
    }
}