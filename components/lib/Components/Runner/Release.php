<?php
/**
 * Components_Runner_Release:: releases a new version for a package.
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
 * Components_Runner_Release:: releases a new version for a package.
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
class Components_Runner_Release
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
     * The release tasks handler.
     *
     * @param Component_Release_Tasks
     */
    private $_release;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Component_Output        $output  The output handler.
     * @param Component_Release_Tasks $release The tasks handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Output $output,
        Components_Release_Tasks $release
    ) {
        $this->_config = $config;
        $this->_output = $output;
        $this->_release = $release;
    }

    public function run()
    {
        $options = $this->_config->getOptions();

        $sequence = array();

        $pre_commit = false;

        if ($this->_doTask('timestamp')) {
            $sequence[] = 'Timestamp';
            $pre_commit = true;
        }

        if ($this->_doTask('sentinel')) {
            $sequence[] = 'CurrentSentinel';
            $pre_commit = true;
        }

        if ($this->_doTask('package')) {
            $sequence[] = 'Package';
            if ($this->_doTask('upload')) {
                $options['upload'] = true;
            } else {
                $this->_output->warn('Are you certain you don\'t want to upload the package? Add the "upload" option in case you want to correct your selection. Waiting 5 seconds ...');
                sleep(5);
            }
        } else if ($this->_doTask('upload')) {
            throw new Components_Exception('Selecting "upload" without "package" is not possible! Please add the "package" task if you want to upload the package!');
        }

        if ($this->_doTask('commit') && $pre_commit) {
            $sequence[] = 'CommitPreRelease';
        }

        if ($this->_doTask('tag')) {
            $sequence[] = 'TagRelease';
        }

        if ($this->_doTask('announce')) {
            $sequence[] = 'Announce';
        }

        if ($this->_doTask('bugs')) {
            $sequence[] = 'Bugs';
        }

        if ($this->_doTask('freecode')) {
            $sequence[] = 'Freecode';
        }

        if ($this->_doTask('next')) {
            $sequence[] = 'NextVersion';
        }
        if ($this->_doTask('nextsentinel')) {
            $sequence[] = 'NextSentinel';
        }
        if (($this->_doTask('next') || $this->_doTask('nextsentinel')) &&
            $this->_doTask('commit')) {
            $sequence[] = 'CommitPostRelease';
        }

        if (in_array('CommitPreRelease', $sequence) ||
            in_array('CommitPostRelease', $sequence)) {
            $options['commit'] = new Components_Helper_Commit(
                $this->_output, $options
            );
        }

        $options['skip_invalid'] = $this->_doTask('release');

        if (!empty($sequence)) {
            $this->_release->run(
                $sequence,
                $this->_config->getComponent(),
                $options
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
        if ((count($arguments) == 1 && $arguments[0] == 'release')
            || in_array($task, $arguments)) {
            return true;
        }
        return false;
    }
}
