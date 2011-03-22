<?php
/**
 * Components_Release_Task_NextVersion:: updates the package.xml file with
 * information about the next component version.
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
 * Components_Release_Task_NextVersion:: updates the package.xml file with
 * information about the next component version.
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
class Components_Release_Task_NextVersion
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
        $errors = array();
        if (empty($options['next_version'])) {
            $errors[] = 'The "next_version" option has no value! What should the next version number be?';
        }
        if ($options['next_note'] === null) {
            $errors[] = 'The "next_note" option has no value! What should the initial change log note be?';
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
        $api_state = isset($options['next_apistate']) ? $options['next_apistate'] : null;
        $rel_state = isset($options['next_relstate']) ? $options['next_relstate'] : null;
        if (!$this->getTasks()->pretend()) {
            $this->getPackage()->nextVersion(
                $options['next_version'],
                $options['next_note'],
                $api_state,
                $rel_state
            );
        } else {
            $info = sprintf(
                'Would add next version "%s" with the initial note "%s" to %s.',
                $options['next_version'],
                $options['next_note'],
                $this->getPackage()->getPackageXml()
            );
            if ($rel_state !== null) {
                $info .= ' Release stability: "' . $rel_state . '".';
            }
            if ($api_state !== null) {
                $info .= ' API stability: "' . $api_state . '".';
            }
            $this->getOutput()->info($info);
        }

        if ($this->getTasks()->isTaskActive('CommitPostRelease')) {
            $this->systemInDirectory(
                'git add ' . $this->getPackage()->getPackageXml(),
                dirname($this->getPackage()->getPackageXml())
            );
        }
    }
}