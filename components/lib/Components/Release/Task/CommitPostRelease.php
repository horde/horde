<?php
/**
 * Components_Release_Task_CommitPostRelease:: commits any changes after to the
 * release.
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
 * Components_Release_Task_CommitPostRelease:: commits any changes after to the
 * release.
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
class Components_Release_Task_CommitPostRelease
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
        if (empty($options['next'])) {
            return array('The "next" option has no value! What should the next version number be?');
        }
        return array();
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
        $this->systemInDirectory(
            'git commit -m "Development mode for ' . $this->getPackage()->getName()
            . '-' . $options['next'] . '"',
            $this->getPackage()->getComponentDirectory()
        );
    }
}