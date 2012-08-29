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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Runner_Update:: updates the package.xml of a Horde
 * component.
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
class Components_Runner_Update
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

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
     * @param Component_Output        $output  The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Output $output
    ) {
        $this->_config  = $config;
        $this->_output = $output;
    }

    public function run()
    {
        $arguments = $this->_config->getArguments();
        $options = array_merge(array(
            'new_version' => false,
            'new_api' => false,
            'new_state' => false,
            'new_apistate' => false,
        ), $this->_config->getOptions());

        if (!empty($options['updatexml'])
            || (isset($arguments[0]) && $arguments[0] == 'update')) {
            $action = !empty($options['action']) ? $options['action'] : 'update';
            if (!empty($options['pretend']) && $action == 'update') {
                $action = 'diff';
            }
            if (!empty($options['commit'])) {
                $options['commit'] = new Components_Helper_Commit(
                    $this->_output, $options
                );
            }
            $result = $this->_config->getComponent()->updatePackageXml(
                $action, $options
            );
            if (!empty($options['new_version']) || !empty($options['new_api'])) {
                $this->_config->getComponent()->setVersion(
                    $options['new_version'], $options['new_api'], $options
                );
            }
            if (!empty($options['new_state']) || !empty($options['new_apistate'])) {
                $this->_config->getComponent()->setState(
                    $options['new_state'], $options['new_apistate'], $options
                );
            }
            if (!empty($options['commit'])) {
                $options['commit']->commit(
                    'Components updated the package.xml.'
                );
            }
            if ($result === true) {
                $this->_output->ok('Successfully updated package.xml of ' . $this->_config->getComponent()->getName() . '.');
            } else {
                print $result;
            }
        }

    }
}
