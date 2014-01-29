<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */

/**
 * Generate config file for use with PHP Composer.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */
class Components_Runner_Composer
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The composer helper.
     *
     * @var Components_Helper_Composer
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Components_Config $config  The configuration for the current
     *                                   job.
     * @param Components_Output $output  The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Output $output
    ) {
        $this->_config = $config;
        $this->_output = $output;
    }

    public function run()
    {
        $composer = new Components_Helper_Composer();
        $options = $this->_config->getOptions();
        $options['logger'] = $this->_output;

        $composer->generateComposeJson(
            $this->_config->getComponent()->getPackageXmlPath(),
            $options
        );
    }
}
