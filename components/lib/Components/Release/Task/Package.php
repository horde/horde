<?php
/**
 * Components_Release_Task_Package:: prepares and uploads a release package.
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
 * Components_Release_Task_Package:: prepares and uploads a release package.
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
        $remote = new Horde_Pear_Remote();
        try {
            $exists = $remote->releaseExists(
                $this->getComponent()->getName(),
                $this->getComponent()->getVersion()
            );
            if ($exists) {
                $errors[] = sprintf(
                    'The remote server already has version "%s" for component "%s".',
                    $this->getComponent()->getVersion(),
                    $this->getComponent()->getName()
                );
            }
        } catch (Horde_Http_Exception $e) {
                $errors[] = 'Failed accessing the remote PEAR server.';
        }
        try {
            Components_Helper_Version::validateReleaseStability(
                $this->getComponent()->getVersion(),
                $this->getComponent()->getState('release')
            );
        } catch (Components_Exception $e) {
            $errors[] = $e->getMessage();
        }
        try {
            Components_Helper_Version::validateApiStability(
                $this->getComponent()->getVersion(),
                $this->getComponent()->getState('api')
            );
        } catch (Components_Exception $e) {
            $errors[] = $e->getMessage();
        }
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
     * @param array &$options Additional options.
     *
     * @return NULL
     */
    public function run(&$options)
    {
        if (!$this->getTasks()->pretend()) {
            $archive_options = $options;
            $archive_options['keep_version'] = true;
            $archive_options['logger'] = $this->getOutput();
            $result = $this->getComponent()->placeArchive(getcwd(), $archive_options);
            if (isset($result[2])) {
                $this->getOutput()->pear($result[2]);
            }
            if (!empty($result[1])) {
                $this->getOutput()->fail(
                    'Generating package failed with:'. "\n\n" . join("\n", $result[1]));
                return;
            }
            $path = $result[0];
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