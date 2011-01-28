<?php
/**
 * Components_Pear_Dependency:: wraps PEAR dependency information.
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
 * Components_Pear_Dependency:: wraps PEAR dependency information.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Components_Pear_Dependency
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
     * Constructor.
     *
     * @param array $dependency The dependency information.
     */
    public function __construct($dependency)
    {
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
     * Is the dependency required?
     *
     * @return boolen True if the dependency is required.
     */
    public function isRequired()
    {
        if (!$this->_package) {
            return false;
        }
        return !$this->_optional;
    }

    /**
     * Is this a pacakge dependency?
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
     * Does the dependency match the given selector?
     *
     * @param string $selector The selector.
     *
     * @return boolen True if the dependency matches.
     */
    public function matches($selector)
    {
        $selectors = split(',', $selector);
        if (in_array('ALL', $selectors)) {
            return true;
        }
        foreach ($selectors as $selector) {
            if (empty($selector)) {
                continue;
            }
            if (strpos($selector, '/') !== false) {
                list($channel, $name) = split('/', $selector, 2);
                if ($this->_channel == $channel && $this->_name == $name) {
                    return true;
                }
                continue;
            }
            if (substr($selector, 0, 8) == 'channel:') {
                if ($this->_channel == substr($selector, 8)) {
                    return true;
                }
                continue;
            }
            if ($this->_name == $selector) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return the package name for the dependency
     *
     * @return string The package name.
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Return the package channel for the dependency
     *
     * @return string The package channel.
     */
    public function channel()
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
