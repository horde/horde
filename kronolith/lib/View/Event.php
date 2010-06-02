<?php
/**
 * The Kronolith_View_Event:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Event {

    var $event;

    /**
     * @param Kronolith_Event $event
     */
    function Kronolith_View_Event($event)
    {
        $this->event = $event;
    }

    function getTitle()
    {
        if (!$this->event) {
            return _("Not Found");
        }
        if (is_string($this->event)) {
            return $this->event;
        }
        return $this->event->getTitle();
    }

    function link()
    {
        return $this->event->getViewUrl();
    }

    function html($active = true)
    {
        if (!$this->event) {
            echo '<h3>' . _("Event not found") . '</h3>';
            exit;
        }
        if (is_string($this->event)) {
            echo '<h3>' . $this->event . '</h3>';
            exit;
        }

        global $conf, $prefs;

        $createdby = '';
        $modifiedby = '';
        $userId = $GLOBALS['registry']->getAuth();
        if ($this->event->uid) {
            /* Get the event's history. */
            try {
                $log = $GLOBALS['injector']->getInstance('Horde_History')
                    ->getHistory('kronolith:' . $this->event->calendar . ':' . $this->event->uid);
                foreach ($log as $entry) {
                    switch ($entry['action']) {
                    case 'add':
                        $created = new Horde_Date($entry['ts']);
                        if ($userId != $entry['who']) {
                            $createdby = sprintf(_("by %s"), Kronolith::getUserName($entry['who']));
                        } else {
                            $createdby = _("by me");
                        }
                        break;

                    case 'modify':
                        $modified = new Horde_Date($entry['ts']);
                        if ($userId != $entry['who']) {
                            $modifiedby = sprintf(_("by %s"), Kronolith::getUserName($entry['who']));
                        } else {
                            $modifiedby = _("by me");
                        }
                        break;
                    }
                }
            } catch (Exception $e) {}
        }

        $creatorId = $this->event->creator;
        $description = $this->event->description;
        $location = $this->event->location;
        $eventurl = $this->event->url;
        $private = $this->event->private && $creatorId != $GLOBALS['registry']->getAuth();
        $owner = Kronolith::getUserName($creatorId);
        $status = Kronolith::statusToString($this->event->status);
        $attendees = $this->event->attendees;
        $resources = $this->event->getResources();
        if ($datetime = Horde_Util::getFormData('datetime')) {
            $datetime = new Horde_Date($datetime);
            $month = $datetime->month;
            $year = $datetime->year;
        } else {
            $month = (int)Horde_Util::getFormData('month', date('n'));
            $year = (int)Horde_Util::getFormData('year', date('Y'));
        }

        $dateFormat = $prefs->getValue('date_format');
        $timeFormat = $prefs->getValue('twentyFour') ? 'G:i' : 'g:ia';

        // Tags
        $tags = implode(', ', $this->event->tags);


        echo '<div id="Event"' . ($active ? '' : ' style="display:none"') . '>';
        require KRONOLITH_TEMPLATES . '/view/view.inc';
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            /* We check for read permissions, because we can always save a
             * copy if we can read the event. */
            if ($this->event->hasPermission(Horde_Perms::READ)) {
                $edit = new Kronolith_View_EditEvent($this->event);
                $edit->html(false);
            }
            if ($this->event->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Kronolith_View_DeleteEvent($this->event);
                $delete->html(false);
            }
        }
    }

    function getName()
    {
        return 'Event';
    }

}
