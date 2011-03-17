<?php
/**
 * Components_Release_Task_Timestamp:: timestamps the package right before the
 * release.
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
 * Components_Release_Task_Timestamp:: timestamps the package right before the
 * release.
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
class Components_Release_Task_Timestamp
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
        try {
            if (!file_exists($this->getPackage()->getPackageXml())) {
                return array(
                    sprintf(
                        '%s is missing but required!',
                        $this->getPackage()->getPackageXml())
                );
            }
        } catch (Components_Exception $e) {
            return array($e->getMessage());
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
        if (!$this->getTasks()->pretend()) {
            $this->getPackage()->timestampAndSync();
        } else {
            $this->getOutput()->info(
                sprintf(
                    'Would timestamp %s now and synchronize its change log.',
                    $this->getPackage()->getPackageXml()
                )
            );
        }

        if ($this->getTasks()->isTaskActive('CommitPreRelease')) {
            $this->systemInDirectory(
                'git add ' . $this->getPackage()->getPackageXml(),
                dirname($this->getPackage()->getPackageXml())
            );
        }
    }
}