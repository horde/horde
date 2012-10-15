<?php
/**
 * Components_Qc_Task_Cpd:: runs a copy/paste check on the component.
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
 * Components_Qc_Task_Cpd:: runs a copy/paste check on the component.
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
class Components_Qc_Task_Cpd
extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'copy/paste detection';
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
        if (!class_exists('PHPCPD_Detector')) {
            return array('PHP CPD is not available!');
        }
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
        $lib = realpath($this->_config->getPath() . '/lib');

        $factory = new File_Iterator_Factory();
        $files = array_keys(iterator_to_array($factory->getFileIterator(
            $lib, 'php'
        )));

        $detector = new PHPCPD_Detector(new PHPCPD_Detector_Strategy_Default());
        $clones   = $detector->copyPasteDetection(
            $files, 5, 70
        );

        $printer = new PHPCPD_TextUI_ResultPrinter;
        $printer->printResult($clones, $lib, true);

        return count($clones);
    }
}
