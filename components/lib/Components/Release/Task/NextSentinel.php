<?php
/**
 * Components_Release_Task_NextSentinel:: updates the CHANGES and the
 * Application.php/Bundle.php files with the next package version.
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
 * Components_Release_Task_CurrentSentinel:: updates the CHANGES and the
 * Application.php/Bundle.php files with the current package version.
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
class Components_Release_Task_NextSentinel
extends Components_Release_Task_Sentinel
{
    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     *
     * @return NULL
     */
    public function run(&$options)
    {
        if (empty($options['next_version'])) {
            $options['next_version'] = Components_Helper_Version::nextVersion($this->getComponent()->getVersion());
        }
        $changes_version = $options['next_version'];
        $application_version = Components_Helper_Version::pearToHordeWithBranch(
            $options['next_version'], $this->getNotes()->getBranch()
        );
        $result = $this->getComponent()->nextSentinel(
            $changes_version, $application_version, $options
        );
        if (!$this->getTasks()->pretend()) {
            foreach ($result as $message) {
                $this->getOutput()->ok($message);
            }
        } else {
            foreach ($result as $message) {
                $this->getOutput()->info($message);
            }
        }
    }
}