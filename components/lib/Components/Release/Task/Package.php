<?php
/**
 * Components_Release_Task_Package:: prepares and uploads a release package.
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
 * Components_Release_Task_Package:: prepares and uploads a release package.
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
class Components_Release_Task_Package
extends Components_Release_Task_Base
{
    /**
     * Can the task be skipped?
     *
     * @param array $options Additional options.
     *
     * @return boolean True if it can be skipped.
     */
    public function skip($options)
    {
        return false;
    }

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
        if (empty($options['releaseserver'])) {
            $errors[] = 'The "releaseserver" option has no value. Where should the release be uploaded?';
        }
        if (empty($options['releasedir'])) {
            $errors[] = 'The "releasedir" option has no value. Where is the remote pirum install located?';
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
        if (!$this->getTasks()->pretend()) {
            $options['keep_version'] = true;
            $path = $this->getComponent()->placeArchive(getcwd(), $options);
        } else {
            $path = '[PATH TO RESULTING]/[PACKAGE.TGZ - PRETEND MODE]';
            $this->getOutput()->info(
                sprintf(
                    'Would package %s now.',
                    $this->getComponent()->getName()
                )
            );
        }

        if (!empty($options['upload'])) {
            $this->system('scp ' . $path . ' ' . $options['releaseserver'] . ':~/');
            $this->system('ssh '. $options['releaseserver'] . ' "pirum add ' . $options['releasedir'] . ' ~/' . basename($path) . ' && rm ' . basename($path) . '"') . "\n";
            if (!$this->getTasks()->pretend()) {
                unlink($path);
            }
        }
    }
}