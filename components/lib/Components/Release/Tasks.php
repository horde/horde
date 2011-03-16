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
     * Constructor.
     *
     * @param Components_Dependencies $dependencies The task factory.
     */
    public function __construct(
        Components_Dependencies $dependencies
    ) {
        $this->_dependencies = $dependencies;
    }

    public function getTask($name, Components_Pear_Package $package)
    {
        $task = $this->_dependencies->getInstance(
            'Components_Release_Task_' . ucfirst($name)
        );
        $task->setPackage($package);
        return $task;
    }
}