<?php
/**
 * Components_Release_Task_Base:: provides core functionality for release tasks.
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
 * Components_Release_Task_Base:: provides core functionality for release tasks.
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
class Components_Release_Task_Base
{
    /**
     * The tasks handler.
     *
     * @var Components_Release_Tasks
     */
    private $_tasks;

    /**
     * The package that should be released
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Release_Tasks $tasks The task handler.
     */
    public function __construct(
        Components_Release_Tasks $tasks
    ) {
        $this->_tasks = $tasks;
    }

    /**
     * Set the package this task should act upon.
     *
     * @param Components_Pear_Package $package The package to be released.
     *
     * @return NULL
     */
    public function setPackage(Components_Pear_Package $package)
    {
        $this->_package = $package;
    }

    /**
     * Get the package this task should act upon.
     *
     * @return Components_Pear_Package The package to be released.
     */
    protected function getPackage()
    {
        return $this->_package;
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
     * Validate the preconditions required for this release task.
     *
     * @return array An empty array if all preconditions are met and a list of
     *               error messages otherwise.
     */
    public function validate()
    {
        return array();
    }
}