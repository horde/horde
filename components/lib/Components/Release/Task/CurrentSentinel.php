<?php
/**
 * Components_Release_Task_CurrentSentinel:: updates the CHANGES and the
 * Application.php/Bundle.php files with the current package version.
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
 * Application.php/Bundle.php files with the current package version.
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
extends Components_Release_Task_Sentinel
{
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
            $this->getPackage()->getComponentDirectory()
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
            if ($bundle = $sentinel->bundleFileExists()) {
                $this->_updateInfo('replace', $bundle, $application_version);
            }
        }
        $this->_commit($sentinel, 'CommitPreRelease');
    }
}