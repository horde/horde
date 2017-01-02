<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */

/**
 * PHP dead code detection.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */
class Components_Qc_Task_Dcd extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'dead code detection';
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
        if (!class_exists('PHPDCD_Detector')) {
            return array('PHPDCD is not available!');
        }
    }

    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     *
     * @return integer Number of errors.
     */
    public function run(&$options)
    {
        require 'PHPDCD/Autoload.php';

        $facade = new File_Iterator_Facade;
        $result = $facade->getFilesAsArray(
            array(realpath($this->_config->getPath())),
            array('php'),
            array(),
            array(),
            true
        );

        $files      = $result['files'];
        $commonPath = $result['commonPath'];

        $detector = new PHPDCD_Detector(new \ezcConsoleOutput);
        $result   = $detector->detectDeadCode($files, true);

        $printer = new PHPDCD_TextUI_ResultPrinter;
        $printer->printResult($result, $commonPath);
    }
}
