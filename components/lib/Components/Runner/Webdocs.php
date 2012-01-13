<?php
/**
 * Components_Runner_Webdocs:: generates the www.horde.org data for a component.
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
 * Components_Runner_Webdocs:: generates the www.horde.org data for a component.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Components_Runner_Webdocs
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The application configuration.
     *
     * @var Components_Config_Application
     */
    private $_config_application;

    /**
     * The website helper.
     *
     * @var Components_Helper_Website
     */
    private $_website_helper;

    /**
     * Constructor.
     *
     * @param Components_Config         $config The configuration for the current job.
     * @param Components_Helper_Website $helper The website helper.
     */
    public function __construct(
        Components_Config $config,
        Components_Helper_Website $helper
    ) {
        $this->_config = $config;
        $this->_website_helper = $helper;
    }

    public function run()
    {
        $this->_website_helper->update(
            $this->_config->getComponent(),
            $this->_config->getOptions()
        );
    }
}
