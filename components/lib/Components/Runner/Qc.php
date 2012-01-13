<?php
/**
 * Components_Runner_Qc:: checks the component for quality.
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
 * Components_Runner_Qc:: checks the component for quality.
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
class Components_Runner_Qc
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
     * The quality control tasks handler.
     *
     * @param Component_Qc_Tasks
     */
    private $_qc;

    /**
     * Constructor.
     *
     * @param Components_Config  $config The configuration for the current job.
     * @param Component_Output   $output The output handler.
     * @param Component_Qc_Tasks $qc     The qc handler.
     */
    public function __construct(Components_Config $config,
                                Components_Output $output,
                                Components_Qc_Tasks $qc)
    {
        $this->_config = $config;
        $this->_output = $output;
        $this->_qc = $qc;
    }

    public function run()
    {
        $sequence = array();
        if ($this->_doTask('unit')) {
            $sequence[] = 'unit';
        }

        if ($this->_doTask('md')) {
            $sequence[] = 'md';
        }

        if ($this->_doTask('cs')) {
            $sequence[] = 'cs';
        }

        if ($this->_doTask('cpd')) {
            $sequence[] = 'cpd';
        }

        if ($this->_doTask('lint')) {
            $sequence[] = 'lint';
        }

        if (!empty($sequence)) {
            $this->_qc->run(
                $sequence,
                $this->_config->getComponent(),
                $this->_config->getOptions()
            );
        } else {
            $this->_output->warn('Huh?! No tasks selected... All done!');
        }
    }

    /**
     * Did the user activate the given task?
     *
     * @param string $task The task name.
     *
     * @return boolean True if the task is active.
     */
    private function _doTask($task)
    {
        $arguments = $this->_config->getArguments();
        if ((count($arguments) == 1 && $arguments[0] == 'qc')
            || in_array($task, $arguments)) {
            return true;
        }
        return false;
    }
}
