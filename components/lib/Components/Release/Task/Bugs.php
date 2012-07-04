<?php
/**
 * Components_Release_Task_Bugs:: adds the new release to the issue tracker.
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
 * Components_Release_Task_Bugs:: adds the new release to the issue tracker.
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
class Components_Release_Task_Bugs
extends Components_Release_Task_Base
{
    /**
     * Queue id.
     *
     * @var string|boolean
     */
    private $_qid;

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
        if (empty($options['horde_user'])) {
            $errors[] = 'The "horde_user" option has no value. Who is updating bugs.horde.org?';
        }
        if (empty($options['horde_pass'])) {
            $errors[] = 'The "horde_pass" option has no value. What is your password for updating bugs.horde.org?';
        }
        if (!class_exists('Horde_Release_Whups')) {
            $errors[] = 'The Horde_Release package is missing (specifically the class Horde_Release_Whups)!';
        }
        try {
            $this->_qid = $this->_getBugs($options)
                ->getQueueId($this->getComponent()->getName());
        } catch (Horde_Exception $e) {
            $errors[] = sprintf(
                'Failed accessing bugs.horde.org: %s', $e->getMessage()
            );
        }
        if (!$this->_qid) {
            $errors[] = 'No queue on bugs.horde.org available. The new version will not be added to the bug tracker!';
        }
        return $errors;
    }

    /**
     * Return the handler for bugs.horde.org.
     *
     * @param array $options Additional options.
     *
     * @return NULL
     */
    public function _getBugs($options)
    {
        if (!isset($options['horde_user']) || !isset($options['horde_user'])) {
            throw new Components_Exception('Missing credentials!');
        }
        return new Horde_Release_Whups(
            array(
                'url' => 'https://dev.horde.org/horde/rpc.php',
                'user' => $options['horde_user'],
                'pass' => $options['horde_pass']
            )
        );
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
        if (!$this->_qid) {
            $this->getOutput()->warn(
                'No queue on bugs.horde.org available. The new version will not be added to the bug tracker!'
            );
            return;
        }

        $ticket_version = $this->getComponent()->getVersion();

        $ticket_description = Components_Helper_Version::pearToTicketDescription(
            $this->getComponent()->getVersion()
        );
        $branch = $this->getNotes()->getBranch();
        if (!empty($branch)) {
            $ticket_description = $branch
                . preg_replace('/([^ ]+) (.*)/', ' (\1) \2', $ticket_description);
        }
        $ticket_description = $this->getNotes()->getName() . ' ' . $ticket_description;

        if (!$this->getTasks()->pretend()) {
            $this->_getBugs($options)->addNewVersion(
                $this->getComponent()->getName(),
                $ticket_version,
                $ticket_description
            );
        } else {
            $this->getOutput()->info(
                sprintf(
                    'Would add new version "%s: %s" to queue "%s".',
                    $ticket_version,
                    $ticket_description,
                    $this->getComponent()->getName()
                )
            );
        }
    }
}