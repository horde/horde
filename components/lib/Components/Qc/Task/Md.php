<?php
/**
 * Components_Qc_Task_Md:: runs a mess detection check on the component.
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
 * Components_Qc_Task_Md:: runs a mess detection check on the component.
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
class Components_Qc_Task_Md
extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'mess detection';
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
        if (!class_exists('PHP_PMD')) {
            return array('PHP PMD is not available!');
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

        $renderer = new PHP_PMD_Renderer_TextRenderer();
        $renderer->setWriter(new PHP_PMD_Writer_Stream(STDOUT));

        $ruleSetFactory = new PHP_PMD_RuleSetFactory();
        $ruleSetFactory->setMinimumPriority(PHP_PMD_AbstractRule::LOWEST_PRIORITY);

        $phpmd = new PHP_PMD();

        $phpmd->processFiles(
            $lib,
            Components_Constants::getDataDirectory() . '/qc_standards/phpmd.xml',
            array($renderer),
            $ruleSetFactory
        );

        return $phpmd->hasViolations();
    }
}
