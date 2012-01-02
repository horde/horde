<?php
/**
 * Components_Qc_Task_Cs:: runs a code style check on the component.
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
 * Components_Qc_Task_Cs:: runs a code style check on the component.
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
class Components_Qc_Task_Cs
extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'code style check';
    }

    /**
     * Validate the preconditions required for this release task.
     *
     * @param array $options Additional options.
     *
     * @return array An empty array if all preconditions are met and a list of
     *               error messages otherwise.
     */
    public function validate($options)
    {
        if (!class_exists('PHP_CodeSniffer')) {
            return array('PHP CodeSniffer is not available!');
        }
    }

    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     *
     * @return NULL
     */
    public function run(&$options)
    {
        $lib = realpath($this->_config->getPath() . '/lib');
        $argv = $_SERVER['argv'];
        $argc = $_SERVER['argv'];
        $_SERVER['argv'] = array();
        $_SERVER['argc'] = 0;
        $phpcs = new PHP_CodeSniffer();
        $phpcs->process(
            $lib,
            Components_Constants::getDataDirectory() . '/qc_standards/phpcs.xml'
        );
        $_SERVER['argv'] = $argv;
        $_SERVER['argc'] = $argc;

        $reporting = new PHP_CodeSniffer_Reporting();
        $filesViolations = $phpcs->getFilesErrors();
        return $reporting->printReport('emacs', $filesViolations, false, null);
    }
}