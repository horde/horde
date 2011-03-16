<?php
/**
 * Components_Release_Task_CurrentSentinel:: updates the CHANGES and the
 * Application.php files with the current package version.
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
 * Components_Release_Task_CurrentSentinel:: updates the CHANGES and the
 * Application.php files with the current package version.
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
class Components_Release_Task_CurrentSentinel
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
        if (!class_exists('Horde_Release')) {
            return array('The Horde_Release package is missing!');
        }
        return array();
    }

    /**
     * Run the task.
     *
     * @param array $options Additional options.
     *
     * @return NULL
     */
    public function run($options)
    {
        $sentinel = new Horde_Release_Sentinel(
            dirname($this->getPackage()->getPackageXml())
        );
        $changes_version = Components_Helper_Version::pearToHorde(
            $this->getPackage()->getVersion()
        );
        $application_version = Components_Helper_Version::pearToHordeWithBranch(
            $this->getPackage()->getVersion(), $this->getNotes()->getBranch()
        );
        if (!$this->getTasks()->pretend()) {
            $sentinel->replaceChanges($changes_version);
            $sentinel->updateApplication($application_version);
        } else {
            if ($changes = $sentinel->changesFileExists()) {
                $this->_updateInfo('replace', $changes, $changes_version);
            }
            if ($application = $sentinel->applicationFileExists()) {
                $this->_updateInfo('replace', $application, $application_version);
            }
        }

        if ($this->getTasks()->isTaskActive('CommitPreRelease')) {
            if ($changes = $sentinel->changesFileExists()) {
                $this->systemInDirectory(
                    'git add ' . $changes,
                    dirname($this->getPackage()->getPackageXml())
                );
            }
            if ($application = $sentinel->applicationFileExists()) {
                $this->systemInDirectory(
                    'git add ' . $application,
                    dirname($this->getPackage()->getPackageXml())
                );
            }
        }
    }

    private function _updateInfo($action, $file, $version)
    {
        $this->getOutput()->info(
            sprintf('Would %s %s with %s now.', $action, $file, $version)
        );
    }
}