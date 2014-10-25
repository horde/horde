<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */

use SebastianBergmann\PHPDCD;
use SebastianBergmann\FinderFacade\FinderFacade;

/**
 * PHP dead code detection.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
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
        if (!class_exists('SebastianBergmann\\PHPDCD\\Detector')) {
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
        $finder = new FinderFacade(
            array(realpath($this->_config->getPath() . '/lib')),
            array(null),
            array('*.php'),
            array(null)
        );
        $files = $finder->findFiles();

        $detector = new PHPDCD\Detector();
        $result   = $detector->detectDeadCode($files, true);

        $this->_printResult($result);
    }

    /**
     * Prints a result set from PHPDCD_Detector::detectDeadCode().
     *
     * @param array  $result
     */
    protected function _printResult(array $result)
    {
        foreach ($result as $name => $source) {
            printf(
                "  - %s()\n    LOC: %d, declared in %s:%d\n",
                $name,
                $source['loc'],
                $source['file'],
                $source['line']
            );
        }
    }
}
