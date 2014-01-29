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

/**
 * Measure the size and analyze the structure of a PHP component.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */
class Components_Qc_Task_Loc extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'analysis/statistics of component code';
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
        if (!class_exists('SebastianBergmann\\PHPLOC\\TextUI\\Command')) {
            return array('PHPLOC is not available!');
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
        require 'SebastianBergmann/FinderFacade/autoload.php';
        require 'SebastianBergmann/PHPLOC/autoload.php';

        $finder = new SebastianBergmann\FinderFacade\FinderFacade(array(
            realpath($this->_config->getPath())
        ));
        $files  = $finder->findFiles();

        $analyser = new SebastianBergmann\PHPLOC\Analyser(new \ezcConsoleOutput);
        $count    = $analyser->countFiles($files, true);

        $printer = new SebastianBergmann\PHPLOC\TextUI\ResultPrinter;
        $printer->printResult($count, true);
    }
}
