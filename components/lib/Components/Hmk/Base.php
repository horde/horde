<?php
/**
 * Components_Hmk_Base:: provides core functionality for the
 * different hmk modules.
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
 * Components_Hmk_Base:: provides core functionality for the
 * different hmk modules.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
abstract class Components_Hmk_Base
implements Components_Module
{
    /**
     * The dependency provider.
     *
     * @var Components_Dependencies
     */
    protected $_dependencies;

    /**
     * Constructor.
     *
     * @param Components_Dependencies $dependencies The dependency provider.
     */
    public function __construct(Components_Dependencies $dependencies)
    {
        $this->_dependencies = $dependencies;
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array();
    }

    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return false;
    }

    public function getOptionGroupTitle()
    {
        return '';
    }

    public function getOptionGroupDescription()
    {
        return '';
    }

    public function getOptionGroupOptions()
    {
        return array();
    }
}