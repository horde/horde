<?php
/**
 * Components_Qc_Task_Unit:: runs the test suite of the component.
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
 * Components_Qc_Task_Unit:: runs the test suite of the component.
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
class Components_Qc_Task_Unit
extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'PHPUnit testsuite';
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
        if (!class_exists('PHPUnit_TextUI_TestRunner')) {
            return array('PHPUnit is not available!');
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
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($this->_config->getPath() . '/test')));
        } catch (Exception $e) {
            return false;
        }

        foreach ($iterator as $file) {
            if ($file->getFilename() == 'AllTests.php') {
                $runner = new PHPUnit_TextUI_Command();
                $result = $runner->run(
                    array(
                        $this->getComponent()->getName() . '_AllTests',
                        $file->getPath()
                    ),
                    false
                );
            }
        }

        return !empty($result);
    }
}
