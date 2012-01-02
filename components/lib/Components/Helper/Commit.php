<?php
/**
 * Components_Helper_Commit:: helps with collecting for git commit events.
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
 * Components_Helper_Commit:: helps with collecting for git commit events.
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
class Components_Helper_Commit
{
    /**
     * Modified paths.
     *
     * @var array
     */
    private $_added = array();

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Applicaiton options.
     *
     * @var array
     */
    private $_options;

    /**
     * Constructor.
     *
     * @param Component_Output $output  The output handler.
     * @param array            $options Applicaiton options.
     */
    public function __construct(Components_Output $output, $options)
    {
        $this->_output  = $output;
        $this->_options = $options;
    }

    /**
     * Add a path to be included in the commit and record the working directory
     * for this git operation.
     *
     * @param string $path      The path to the modified file.
     * @param string $directory The working directory.
     *
     * @return NULL
     */
    public function add($path, $directory)
    {
        $this->_added[$path] = $directory;
    }

    /**
     * Add all modified files and commit them.
     *
     * @param string $log The commit message.
     *
     * @return NULL
     */
    public function commit($log)
    {
        if (empty($this->_added)) {
            return;
        }
        foreach ($this->_added as $path => $wd) {
            $this->systemInDirectory('git add ' . $path, $wd);
        }
        $this->systemInDirectory('git commit -m "' . $log . '"', $wd);
        $this->_added = array();
    }

    /**
     * Tag the component.
     *
     * @param string $tag       Tag name.
     * @param string $message   Tag message.
     * @param string $directory The working directory.
     *
     * @return NULL
     */
    public function tag($tag, $message, $directory)
    {
        $this->systemInDirectory(
            'git tag -f -m "' . $message . '" ' . $tag, $directory
        );
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
        if (empty($this->_options['pretend'])) {
            //@todo Error handling
            return system($call);
        } else {
            $this->_output->info(sprintf('Would run "%s" now.', $call));
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
        if (empty($this->_options['pretend'])) {
            $old_dir = getcwd();
            chdir($target_dir);
        }
        $result = $this->system($call);
        if (empty($this->_options['pretend'])) {
            chdir($old_dir);
        }
        return $result;
    }

}
