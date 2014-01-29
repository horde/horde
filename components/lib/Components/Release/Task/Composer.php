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
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */

/**
 * This task updates the composer.json file right before the release.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */
class Components_Release_Task_Composer
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
        if (!$this->getComponent()->hasLocalPackageXml()) {
            return array(
                'The component lacks a local package.xml!',
            );
        }
        $diff_options = $options;
        $diff_options['no_timestamp'] = true;
        $diff = $this->getComponent()->updatePackageXml('diff', $diff_options);
        if (!empty($diff)) {
            return array(
                "The package.xml file is not up-to-date:\n$diff"
            );
        }
        return array();
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
        $result = $this->getComponent()->updateComposer($options);
        if (!$this->getTasks()->pretend()) {
            $this->getOutput()->ok($result);
        } else {
            $this->getOutput()->info($result);
        }
    }
}
