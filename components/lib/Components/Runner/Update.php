<?php
/**
 * Components_Runner_Update:: updates the package.xml of a Horde
 * component.
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
 * Components_Runner_Update:: updates the package.xml of a Horde
 * component.
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
class Components_Runner_Update
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The factory for PEAR handlers.
     *
     * @var Components_Factory
     */
    private $_factory;

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Factory $factory Generator for all
     *                                         required PEAR components.
     * @param Component_Output        $output  The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory,
        Components_Output $output
    ) {
        $this->_config  = $config;
        $this->_factory = $factory;
        $this->_output = $output;
    }

    public function run()
    {
        $arguments = $this->_config->getArguments();
        $options = $this->_config->getOptions();

        if (!file_exists($this->_config->getComponent()->getPackageXml())) {
            $this->_factory->createPackageFile(
                $this->_config->getComponent()->getPath()
            );
        }

        if (!empty($options['updatexml'])
            || (isset($arguments[0]) && $arguments[0] == 'update')) {
            $action = !empty($options['action']) ? $options['action'] : 'update';
            if (!empty($options['pretend']) && $action == 'update') {
                $action = 'diff';
            }
            $result = $this->_config->getComponent()->updatePackageXml(
                $action, $options
            );
            if ($result === true) {
                $this->_output->ok('Successfully updated package.xml of ' . $this->_config->getComponent()->getName() . '.');
            } else {
                print $result;
            }
        }

    }
}
