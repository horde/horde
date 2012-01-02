<?php
/**
 * Components_Qc_Tasks:: organizes the different tasks required for
 * releasing a package.
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
 * Components_Qc_Tasks:: organizes the different tasks required for
 * releasing a package.
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
class Components_Qc_Tasks
{
    /**
     * Provides the tasks.
     *
     * @var Components_Dependencies
     */
    private $_dependencies;

    /**
     * The options for the current qc run.
     *
     * @var array
     */
    private $_options = array();

    /**
     * The sequence for the current qc run.
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
     * @param string                  $name      The name of the task.
     * @param Components_Component    $component The component to be checked.
     *
     * @return Components_Release_Task The task.
     */
    public function getTask($name, Components_Component $component)
    {
        $task = $this->_dependencies->getInstance(
            'Components_Qc_Task_' . ucfirst($name)
        );
        $task->setComponent($component);
        $task->setName($name);
        return $task;
    }

    /**
     * Run a sequence of qc tasks.
     *
     * @param array                $sequence The task sequence.
     * @param Components_Component $component The component to be checked.
     * @param array                $options  Additional options.
     *
     * @return NULL
     */
    public function run(
        array $sequence,
        Components_Component $component,
        $options = array()
    ) {
        $this->_options = $options;
        $this->_sequence = $sequence;

        $task_sequence = array();
        foreach ($sequence as $name) {
            $task_sequence[] = $this->getTask($name, $component);
        }
        $selected_tasks = array();
        foreach ($task_sequence as $task) {
            $task_errors = $task->validate($options);
            if (!empty($task_errors)) {
                $this->_dependencies->getOutput()->warn(
                    sprintf(
                        "Deactivated task \"%s\":\n\n%s",
                        $task->getName(),
                        join("\n", $task_errors)
                    )
                );
            } else {
                $selected_tasks[] = $task;
            }
        }
        $output = $this->_dependencies->getInstance('Components_Output');
        foreach ($selected_tasks as $task) {
            $output->bold(str_repeat('-', 30));
            $output->ok(
                'Running ' . $task->getName() . ' on ' . $component->getName() .
                "\n"
            );

            $numErrors = $task->run($options);

            $output->bold("\n");
            if ($numErrors == 1) {
                $output->warn("$numErrors error!");
            } else if ($numErrors) {
                $output->warn("$numErrors errors!");
            } else {
                $output->ok('No problems found.');
            }
            $output->bold(str_repeat('-', 30) . "\n");
        }
    }
}