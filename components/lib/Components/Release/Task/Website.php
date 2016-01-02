<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Jan Schneider <jan@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Release_Task_Website adds the new release to the Horde website.
 *
 * @category Horde
 * @package  Components
 * @author   Jan Schneider <jan@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Release_Task_Website extends Components_Release_Task_Base
{
    /**
     * Database handle.
     *
     * @var PDO
     */
    protected $_db;

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
        if (empty($options['web_dir'])) {
            $errors[] = 'The "web" option has no value. Where is the local checkout of the horde-web repository?';
        } elseif (!file_exists($options['web_dir'] . '/config/versions.sqlite') ||
            !is_writable($options['web_dir'] . '/config/versions.sqlite')) {
            $errors[] = 'The database at ' . $options['web_dir'] . '/config/versions.sqlite doesn\'t exist or is not writable';
        } else {
            $this->_db = new PDO('sqlite:' . $options['web_dir'] . '/config/versions.sqlite');
        }
        return $errors;
    }

    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     */
    public function run(&$options)
    {
        if (!$this->getComponent()->getReleaseNotesPath()) {
            $this->getOutput()->warn(
                'Not an application. Will not add a new version to the website.'
            );
            return;
        }

        $module = $this->getComponent()->getName();
        $version = $this->getComponent()->getVersion();

        if ($this->getTasks()->pretend()) {
            $this->getOutput()->info(
                sprintf(
                    'Would add new version "%s" to module "%s" on the website.',
                    $version,
                    $module
                )
            );
        } else {
            $website = new Horde_Release_Website($this->_db);
            $website->addNewVersion(array(
                'application' => $module,
                'version' => $version,
            ));
       }
    }
}
