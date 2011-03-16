<?php
/**
 * Components_Release_Task_CommitPreRelease:: commits any changes prior to the
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
 * Components_Release_Task_CommitPreRelease:: commits any changes prior to the
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
class Components_Release_Task_CommitPreRelease
extends Components_Release_Task_Base
{
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
            'git commit -m "Released ' . $this->getPackage()->getName()
            . '-' . $this->getPackage()->getVersion() . '"',
            dirname($this->getPackage()->getPackageXml())
        );
    }
}