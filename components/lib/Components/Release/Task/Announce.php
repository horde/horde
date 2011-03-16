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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Release_Task_Announce:: announces new releases to the mailing
 * lists.
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
     * @param array $options Additional options.
     *
     * @return NULL
     */
    public function run($options)
    {
        if (!$this->getNotes()->hasNotes()) {
            $this->getOutput()->warn(
                'No release announcements available! No information will be sent to the mailing lists.'
            );
            return;
        }

        $mailer = new Horde_Release_MailingList(
            $this->getPackage()->getName(),
            $this->getNotes()->getName(),
            $this->getNotes()->getBranch(),
            $options['from'],
            $this->getNotes()->getList(),
            Components_Helper_Version::pearToHorde($package->getVersion()),
            $this->getNotes()->getFocusList()
        );
        $mailer->append($this->getNotes()->getAnnouncement());

        if (!$this->getTasks()->pretend()) {
            //$class = 'Horde_Mail_Transport_' . ucfirst($this->_options['mailer']['type']);
            //$mailer->getMail()->send(new $class($this->_options['mailer']['params']));
        } else {
            $this->getOutput()->info('Message headers');
            foreach ($mailer->getHeaders() as $key => $value) {
                $this->getOutput()->info($key . ': ' . $value);
            }
            $this->getOutput()->info('Message body');
            $this->getOutput()->info($mailer->getBody());
        }
    }
}