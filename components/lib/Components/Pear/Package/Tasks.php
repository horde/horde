<?php
/**
 * Components_Pear_Package_Tasks:: is a PEAR package oriented file task handler.
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
 * Components_Pear_Package_Tasks:: is a PEAR package oriented file task handler.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Pear_Package_Tasks
{
    /**
     * Return the files that have special tasks attached.
     *
     * @param PEAR_PackageFile_v2_rw $package The package.
     *
     * @return array The list of files with tasks attached.
     */
    public function denote(PEAR_PackageFile_v2_rw $package)
    {
        $contents = $package->getContents();
        $taskfiles = array();
        foreach ($contents['dir']['file'] as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            $atts = $file['attribs'];
            unset($file['attribs']);
            if (count($file)) {
                $taskfiles[$atts['name']] = $file;
            }
        }
        return $taskfiles;
    }

    /**
     * Return the files that have special tasks attached.
     *
     * @param PEAR_PackageFile_v2_rw $package   The package.
     * @param array                  $taskfiles The tasks to add.
     *
     * @return array The list of files with tasks attached.
     */
    public function annotate(PEAR_PackageFile_v2_rw $package, array $taskfiles)
    {
        $updated = $package->getContents();
        $updated = $updated['dir']['file'];
        foreach ($updated as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            if (isset($taskfiles[$file['attribs']['name']])) {
                foreach ($taskfiles[$file['attribs']['name']] as $tag => $raw) {
                    $taskname = $package->getTask($tag) . '_rw';
                    if (!class_exists($taskname)) {
                        throw new Components_Exception(
                            sprintf('Read/write task %s is missing!', $taskname)
                        );
                    }
                    $logger = new stdClass;
                    $task = new $taskname(
                        $package,
                        $package->_config,
                        $logger,
                        ''
                    );
                    switch ($taskname) {
                    case 'PEAR_Task_Replace_rw':
                        $task->setInfo(
                            $raw['attribs']['from'],
                            $raw['attribs']['to'],
                            $raw['attribs']['type']
                        );
                        break;
                    default:
                        throw new Components_Exception(
                            sprintf('Unsupported task type %s!', $tag)
                        );
                    }
                    $task->init(
                        $raw,
                        $file['attribs']
                    );
                    $package->addTaskToFile($file['attribs']['name'], $task);
                }
            }
        }

    }
}