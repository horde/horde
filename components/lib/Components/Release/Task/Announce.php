<?php
/**
 * Components_Release_Task_Announce:: announces new releases to the mailing
 * lists.
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
 * Components_Release_Task_Announce:: announces new releases to the mailing
 * lists.
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
class Components_Release_Task_Announce
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
        if (!$this->getNotes()->hasNotes()) {
            $errors[] = 'No release announcements available! No information will be sent to the mailing lists.';
        }
        if (empty($options['from'])) {
            $errors[] = 'The "from" option has no value. Who is sending the announcements?';
        }
        if (!class_exists('Horde_Release_MailingList')) {
            $errors[] = 'The Horde_Release package is missing (specifically the class Horde_Release_MailingList)!';
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
        if (!$this->getNotes()->hasNotes()) {
            $this->getOutput()->warn(
                'No release announcements available! No information will be sent to the mailing lists.'
            );
            return;
        }

        $mailer = new Horde_Release_MailingList(
            $this->getComponent()->getName(),
            $this->getNotes()->getName(),
            $this->getNotes()->getBranch(),
            $options['from'],
            $this->getNotes()->getList(),
            $this->getComponent()->getVersion(),
            $this->getNotes()->getFocusList()
        );
        $mailer->append($this->getNotes()->getAnnouncement());
        $mailer->append("\n\n" .
            'The full list of changes can be viewed here:' .
            "\n\n" .
            $this->getComponent()->getChangelog(
                new Components_Helper_ChangeLog($this->getOutput())
            ) .
            "\n\n" .
            'Have fun!' .
            "\n\n" .
            'The Horde Team.'
        );

        if (!$this->getTasks()->pretend()) {
            try {
                //@todo: Make configurable again
                $class = 'Horde_Mail_Transport_Sendmail';
                $mailer->getMail()->send(new $class(array()));
            } catch (Exception $e) {
                $this->getOutput()->warn((string)$e);
            }
        } else {
            $info = 'ANNOUNCEMENT

Message headers
---------------

';
            foreach ($mailer->getHeaders() as $key => $value) {
                $info .= $key . ': ' . $value . "\n";
            }
            $info .= '
Message body
------------

';
            $info .= $mailer->getBody();

            $this->getOutput()->info($info);
        }
    }
}