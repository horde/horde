<?php
/**
 * Components_Component_Dependency:: wraps PEAR dependency information.
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
 * Components_Component_Dependency:: wraps PEAR dependency information.
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
class Components_Component_Dependency
{
    /**
     * The name of the dependency.
     *
     * @var string
     */
    private $_name = '';

    /**
     * The channel of the dependency.
     *
     * @var string
     */
    private $_channel = '';

    /**
     * The type of the dependency.
     *
     * @var string
     */
    private $_type = '';

    /**
     * Indicates if this is an optional dependency.
     *
     * @var boolean
     */
    private $_optional = true;

    /**
     * Indicates if this is a package dependency.
     *
     * @var boolean
     */
    private $_package = false;

    /**
     * Original dependency information.
     *
     * @var array
     */
    private $_dependency;
    /**
     * The factory for the component representation of a dependency.
     *
     * @var Components_Component_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param array                        $dependency The dependency
     *                                                 information.
     * @param Components_Component_Factory $factory    Helper factory.
     */
    public function __construct(
        $dependency, Components_Component_Factory $factory
    )
    {
        $this->_factory = $factory;
        $this->_dependency = $dependency;
        if (isset($dependency['name'])) {
            $this->_name = $dependency['name'];
        }
        if (isset($dependency['channel'])) {
            $this->_channel = $dependency['channel'];
        }
        if (isset($dependency['optional'])
            && $dependency['optional'] == 'no') {
            $this->_optional = false;
        }
        if (isset($dependency['type'])) {
            $this->_type = $dependency['type'];
        }
        if (isset($dependency['type'])
            && $dependency['type'] == 'pkg') {
            $this->_package = true;
        }
    }

    /**
     * Return the dependency in its component representation.
     *
     * @param array $options The options for resolving the component.
     *
     * @return Component_Component The component.
     */
    public function getComponent($options = array())
    {
        return $this->_factory->createResolver()
            ->resolveDependency($this, $options);
    }

    /**
     * Return the original dependency information.
     *
     * @return array The original dependency information.
     */
    public function getDependencyInformation()
    {
        return $this->_dependency;
    }

    /**
     * Is the dependency required?
     *
     * @return boolen True if the dependency is required.
     */
    public function isRequired()
    {
        return !$this->_optional;
    }

    /**
     * Is this a package dependency?
     *
     * @return boolen True if the dependency is a package.
     */
    public function isPackage()
    {
        return $this->_package;
    }

    /**
     * Is the dependency a Horde dependency?
     *
     * @return boolen True if it is a Horde dependency.
     */
    public function isHorde()
    {
        if (empty($this->_channel)) {
            return false;
        }
        if ($this->_channel != 'pear.horde.org') {
            return false;
        }
        return true;
    }

    /**
     * Is this the PHP dependency?
     *
     * @return boolen True if it is the PHP dependency.
     */
    public function isPhp()
    {
        if ($this->_type != 'php') {
            return false;
        }
        return true;
    }

    /**
     * Is this a PHP extension dependency?
     *
     * @return boolen True if it is a PHP extension dependency.
     */
    public function isExtension()
    {
        if ($this->_type != 'ext') {
            return false;
        }
        return true;
    }

    /**
     * Is the dependency the PEAR base package?
     *
     * @return boolen True if it is the PEAR base package.
     */
    public function isPearBase()
    {
        if ($this->_name == 'PEAR' && $this->_channel == 'pear.php.net') {
            return true;
        }
        return false;
    }

    /**
     * Return the package name for the dependency
     *
     * @return string The package name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the package channel for the dependency
     *
     * @return string The package channel.
     */
    public function getChannel()
    {
        return $this->_channel;
    }

    /**
     * Return the package channel or the type description for the dependency.
     *
     * @return string The package channel.
     */
    public function channelOrType()
    {
        if ($this->isExtension()) {
            return 'PHP Extension';
        } else {
            return $this->_channel;
        }
    }

    /**
     * Return the key for the dependency
     *
     * @return string The uniqe key for this dependency.
     */
    public function key()
    {
        return $this->_channel . '/' . $this->_name;
    }
}
