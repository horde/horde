<?php
/**
 * Components_Qc_Task_Base:: provides core functionality for qc tasks.
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
 * Components_Qc_Task_Base:: provides core functionality for qc tasks.
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
class Components_Qc_Task_Base
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    protected $_config;

    /**
     * The tasks handler.
     *
     * @var Components_Qc_Tasks
     */
    private $_tasks;

    /**
     * The task output.
     *
     * @var Components_Output
     */
    private $_output;

    /**
     * The component that should be checked
     *
     * @var Components_Component
     */
    private $_component;

    /**
     * The task name.
     *
     * @var string
     */
    private $_name;

    /**
     * Constructor.
     *
     * @param Components_Config   $config The configuration for the current job.
     * @param Components_Qc_Tasks $tasks  The task handler.
     * @param Components_Output   $output Accepts output.
     */
    public function __construct(Components_Config $config,
                                Components_Qc_Tasks $tasks,
                                Components_Output $output)
    {
        $this->_config = $config;
        $this->_tasks = $tasks;
        $this->_output = $output;
    }

    /**
     * Set the component this task should act upon.
     *
     * @param Components_Component $component The component to be checked.
     *
     * @return NULL
     */
    public function setComponent(Components_Component $component)
    {
        $this->_component = $component;
    }

    /**
     * Get the component this task should act upon.
     *
     * @return Components_Component The component to be checked.
     */
    protected function getComponent()
    {
        return $this->_component;
    }

    /**
     * Set the name of this task.
     *
     * @param string $name The task name.
     *
     * @return NULL
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the tasks handler.
     *
     * @return Components_Release_Tasks The release tasks handler.
     */
    protected function getTasks()
    {
        return $this->_tasks;
    }

    /**
     * Get the output handler.
     *
     * @return Components_Output The output handler.
     */
    protected function getOutput()
    {
        return $this->_output;
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
        return array();
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
    }

    /**
     * Run a system call.
     *
     * @param string $call The system call to execute.
     *
     * @return string The command output.
     */
    protected function system($call)
    {
        if (!$this->getTasks()->pretend()) {
            //@todo Error handling
            return system($call);
        } else {
            $this->getOutput()->info(sprintf('Would run "%s" now.', $call));
        }
    }

    /**
     * Run a system call.
     *
     * @param string $call       The system call to execute.
     * @param string $target_dir Run the command in the provided target path.
     *
     * @return string The command output.
     */
    protected function systemInDirectory($call, $target_dir)
    {
        if (!$this->getTasks()->pretend()) {
            $old_dir = getcwd();
            chdir($target_dir);
        }
        $result = $this->system($call);
        if (!$this->getTasks()->pretend()) {
            chdir($old_dir);
        }
        return $result;
    }
}