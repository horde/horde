<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Copy a message to a tasklist.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Indices_Copy_Tasklist
    extends IMP_Indices_Copy
{
    const TASKLIST_EDIT = "tasklist\0";

    /**
     * @return array
     */
    public function getTasklists($notify = false)
    {
        global $conf, $notification, $registry;

        if ($conf['tasklist']['use_tasklist'] &&
            $registry->hasMethod('tasks/listTasklists')) {
            try {
                $lists = $registry->call(
                    'tasks/listTasklists',
                    array(false, Horde_Perms::EDIT)
                );

                $out = array();

                foreach ($lists as $key => $val) {
                    $mbox = IMP_Mailbox::formTo(self::TASKLIST_EDIT . $key);
                    $out[$mbox] = $val;
                }

                return $out;
            } catch (Horde_Exception $e) {
                if ($notify) {
                    $notification->push($e);
                }
            }
        }

        return array();
    }

    /**
     */
    protected function _create($mbox, $subject, $body)
    {
        global $notification, $registry;

        $list = str_replace(self::TASKLIST_EDIT, '', $mbox);

        /* Create a new iCalendar. */
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . $registry->getVersion() . '//EN');
        $vCal->setAttribute('METHOD', 'PUBLISH');

        /* Create a new vTodo object using this message's contents. */
        $vTodo = Horde_Icalendar::newComponent('vtodo', $vCal);
        $vTodo->setAttribute('SUMMARY', $subject);
        $vTodo->setAttribute('DESCRIPTION', $body);
        $vTodo->setAttribute('PRIORITY', '3');

        /* Get the list of editable tasklists. */
        $lists = $this->getTasklists(true);

        /* Attempt to add the new vTodo item to the requested tasklist. */
        try {
            $res = $registry->call(
                'tasks/import',
                array($vTodo, 'text/calendar', $list)
            );
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return;
        }

        if (!$res) {
            $notification->push(
                _("An unknown error occured while creating the new task."),
                'horde.error'
            );
        } elseif (!empty($lists)) {
            $name = '"' . htmlspecialchars($subject) . '"';

            /* Attempt to convert the object name into a hyperlink. */
            if ($registry->hasLink('tasks/show')) {
                $name = sprintf(
                    '<a href="%s">%s</a>',
                    Horde::url($registry->link('tasks/show', array('uid' => $res))),
                    $name
                );
            }

            $notification->push(
                sprintf(
                    _("%s was successfully added to \"%s\"."),
                    $name,
                    htmlspecialchars($lists[$list]->get('name'))
                ),
                'horde.success',
                array('content.raw')
            );
        }
    }

    /**
     */
    public function match($mbox)
    {
        global $conf;

        return ($conf['tasklist']['use_tasklist'] &&
                (strpos($mbox, self::TASKLIST_EDIT) === 0));
    }

}
