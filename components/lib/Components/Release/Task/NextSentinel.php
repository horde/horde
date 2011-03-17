<?php
/**
 * Components_Release_Task_NextSentinel:: updates the CHANGES and the
 * Application.php files with the next package version.
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
class Components_Release_Task_NextSentinel
extends Components_Release_Task_Sentinel
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
        $errors = parent::validate($options);
        if (empty($options['next_version'])) {
            $errors[] = 'The "next_version" option has no value! What should the next version number be?';
        }
        return $errors;
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
            $this->getPackage()->getComponentDirectory()
        );
        $changes_version = Components_Helper_Version::pearToHorde(
            $options['next_version']
        );
        $application_version = Components_Helper_Version::pearToHordeWithBranch(
            $options['next_version'], $this->getNotes()->getBranch()
        );
        if (!$this->getTasks()->pretend()) {
            $sentinel->updateChanges($changes_version);
            $sentinel->updateApplication($application_version);
        } else {
            if ($changes = $sentinel->changesFileExists()) {
                $this->_updateInfo('extend', $changes, $changes_version);
            }
            if ($application = $sentinel->applicationFileExists()) {
                $this->_updateInfo('replace', $application, $application_version);
            }
        }
        $this->_commit($sentinel, 'CommitPostRelease');
    }
}