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

use SebastianBergmann\PHPCPD;
use SebastianBergmann\FinderFacade\FinderFacade;

/**
 * Components_Qc_Task_Cpd:: runs a copy/paste check on the component.
 *
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
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
        if (!class_exists('SebastianBergmann\\PHPCPD\\Detector\\Detector')) {
            return array('PHPCPD is not available!');
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
        $lib = realpath($this->_config->getPath() . '/lib');

        $finder = new FinderFacade(
            array($lib),
            array(null),
            array('*.php'),
            array(null)
        );
        $files = $finder->findFiles();

        $detector = new PHPCPD\Detector\Detector(
            new PHPCPD\Detector\Strategy\DefaultStrategy()
        );
        $clones   = $detector->copyPasteDetection(
            $files, 5, 70
        );

        $this->_printResult($clones);

        return count($clones);
    }

    /**
     * Prints a result set from Detector::copyPasteDetection().
     *
     * @param CodeCloneMap    $clones
     */
    protected function _printResult(PHPCPD\CodeCloneMap $clones)
    {
        $numClones = count($clones);

        if ($numClones > 0) {
            $buffer = '';
            $files  = array();
            $lines  = 0;

            foreach ($clones as $clone) {
                foreach ($clone->getFiles() as $file) {
                    $filename = $file->getName();

                    if (!isset($files[$filename])) {
                        $files[$filename] = true;
                    }
                }

                $lines  += $clone->getSize() * (count($clone->getFiles()) - 1);
                $buffer .= "\n  -";

                foreach ($clone->getFiles() as $file) {
                    $buffer .= sprintf(
                        "\t%s:%d-%d\n ",
                        $file->getName(),
                        $file->getStartLine(),
                        $file->getStartLine() + $clone->getSize()
                    );
                }
            }

            printf(
                "Found %d exact clones with %d duplicated lines in %d files:\n%s",
                $numClones,
                $lines,
                count($files),
                $buffer
            );
        }

        printf(
            "%s%s duplicated lines out of %d total lines of code.\n\n",
            $numClones > 0 ? "\n" : '',
            $clones->getPercentage(),
            $clones->getNumLines()
        );
    }
}
