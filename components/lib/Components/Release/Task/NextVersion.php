<?php
/**
 * Components_Release_Task_NextVersion:: updates the package.xml file with
 * information about the next component version.
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
 * Components_Release_Task_NextVersion:: updates the package.xml file with
 * information about the next component version.
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
class Components_Release_Task_NextVersion
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
        $errors = array();
        if ($options['next_note'] === null) {
            $errors[] = 'The "next_note" option has no value! What should the initial change log note be?';
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
        $api_state = isset($options['next_apistate']) ? $options['next_apistate'] : null;
        $rel_state = isset($options['next_relstate']) ? $options['next_relstate'] : null;
        $next_version = empty($options['next_version'])
            ? Components_Helper_Version::nextPearVersion(
                $this->getComponent()->getVersion())
            : $options['next_version'];
        $result = $this->getComponent()->nextVersion(
            Components_Helper_Version::validatePear($next_version),
            $options['next_note'],
            $api_state,
            $rel_state,
            $options
        );
        if (!$this->getTasks()->pretend()) {
            $this->getOutput()->ok($result);
        } else {
            $this->getOutput()->info($result);
        }
    }
}