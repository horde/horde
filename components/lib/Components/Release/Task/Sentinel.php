<?php
/**
 * Components_Release_Task_Sentinel:: provides base functionality for the
 * sentinel tasks.
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
 * Components_Release_Task_Sentinel:: provides base functionality for the
 * sentinel tasks.
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
class Components_Release_Task_Sentinel
extends Components_Release_Task_Base
{
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
        if (!class_exists('Horde_Release_Sentinel')) {
            return array('The Horde_Release package is missing (specifically the class Horde_Release_Sentinel)!');
        }
        return array();
    }

    /**
     * Run the commit commands after the change if requested.
     *
     * @param Horde_Release_Sentinel $sentinel    The sentinel handler.
     * @param string                 $commit_task The name of the commit task.
     *
     * @return NULL
     */
    protected function _commit($sentinel, $commit_task)
    {
        if ($this->getTasks()->isTaskActive($commit_task)) {
            if ($changes = $sentinel->changesFileExists()) {
                $this->systemInDirectory(
                    'git add ' . $changes,
                    $this->getPackage()->getComponentDirectory()
                );
            }
            if ($application = $sentinel->applicationFileExists()) {
                $this->systemInDirectory(
                    'git add ' . $application,
                    $this->getPackage()->getComponentDirectory()
                );
            }
        }
    }

    /**
     * Provide information on the update to be done.
     *
     * @param string $action  The action that will be performed.
     * @param string $file    The file that will be changed.
     * @param string $version The new version number.
     *
     * @return NULL
     */
    protected function _updateInfo($action, $file, $version)
    {
        $this->getOutput()->info(
            sprintf('Would %s %s with %s now.', $action, $file, $version)
        );
    }
}