<?php
/**
 * Components_Release_Tasks:: organizes the different tasks required for
 * releasing a package.
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
 * Components_Release_Tasks:: organizes the different tasks required for
 * releasing a package.
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
class Components_Release_Tasks
{
    /**
     * Provides the tasks.
     *
     * @var Components_Dependencies
     */
    private $_dependencies;

    /**
     * The options for the current release run.
     *
     * @var array
     */
    private $_options = array();

    /**
     * The sequence for the current release run.
     *
     * @var array
     */
    private $_sequence = array();

    /**
     * Constructor.
     *
     * @param Components_Dependencies $dependencies The task factory.
     */
    public function __construct(
        Components_Dependencies $dependencies
    ) {
        $this->_dependencies = $dependencies;
    }

    /**
     * Return the named task.
     *
     * @param string                  $name    The name of the task.
     * @param Components_Pear_Package $package The package to be released.
     *
     * @return Components_Release_Task The task.
     */
    public function getTask($name, Components_Pear_Package $package)
    {
        $task = $this->_dependencies->getInstance(
            'Components_Release_Task_' . ucfirst($name)
        );
        $task->setPackage($package);
        return $task;
    }

    /**
     * Run a sequence of release tasks.
     *
     * @param array                   $sequence The task sequence.
     * @param Components_Pear_Package $package  The package to be released.
     * @param array                   $options  Additional options.
     *
     * @return NULL
     */
    public function run(
        array $sequence,
        Components_Pear_Package $package,
        $options = array()
    ) {
        $this->_options = $options;
        $this->_sequence = $sequence;

        $task_sequence = array();
        foreach ($sequence as $name) {
            $task_sequence[] = $this->getTask($name, $package);
        }
        $errors = array();
        foreach ($task_sequence as $task) {
            $errors = array_merge($errors, $task->validate($options));
        }
        if (!empty($errors)) {
            throw new Components_Exception(
                "Unable to release:\n\n" . join("\n", $errors)
            );
        }
        foreach ($task_sequence as $task) {
            $task->run($options);
        }
    }

    /**
     * Is the current run operating in "pretend" mode?
     *
     * @return boolean True in case we should be pretending.
     */
    public function pretend()
    {
        return !empty($this->_options['pretend']);
    }

    /**
     * Is the specified task active for the current run?
     *
     * @param string $task The task name.
     *
     * @return boolean True in case the task is active.
     */
    public function isTaskActive($task)
    {
        return in_array($task, $this->_sequence);
    }

}