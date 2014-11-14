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
 * Copy a message to a notepad.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Indices_Copy_Notepad
    extends IMP_Indices_Copy
{
    const NOTEPAD_EDIT = "notepad\0";

    /**
     * @return array
     */
    public function getNotepads($notify = false)
    {
        global $conf, $notification, $registry;

        if ($conf['notepad']['use_notepad'] &&
            $registry->hasMethod('notes/listNotepads')) {
            try {
                $lists = $registry->call(
                    'notes/listNotepads',
                    array(false, Horde_Perms::EDIT)
                );

                $out = array();

                foreach ($lists as $key => $val) {
                    $mbox = IMP_Mailbox::formTo(self::NOTEPAD_EDIT . $key);
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

        $list = str_replace(self::NOTEPAD_EDIT, '', $mbox);

        /* Create a new iCalendar. */
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . $registry->getVersion() . '//EN');
        $vCal->setAttribute('METHOD', 'PUBLISH');

        /* Create a new vNote object using this message's contents. */
        $vNote = Horde_Icalendar::newComponent('vnote', $vCal);
        $vNote->setAttribute('BODY', $subject . "\n". $body);

        /* Get the list of editable notepads. */
        $lists = $this->getNotepads(true);

        /* Attempt to add the new vNote item to the requested notepad. */
        try {
            $res = $registry->call(
                'notes/import',
                array($vNote, 'text/x-vnote', $list)
            );
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return;
        }

        if (!$res) {
            $notification->push(
                _("An unknown error occured while creating the new note."),
                'horde.error'
            );
        } elseif (!empty($lists)) {
            $name = '"' . htmlspecialchars($subject) . '"';

            /* Attempt to convert the object name into a hyperlink. */
            if ($registry->hasLink('notes/show')) {
                $name = sprintf(
                    '<a href="%s">%s</a>',
                    Horde::url($registry->link('notes/show', array('uid' => $res))),
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

        return ($conf['notepad']['use_notepad'] &&
                (strpos($mbox, self::NOTEPAD_EDIT) === 0));
    }

}
