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
     * @param Kronolith_Event &$event
     */
    function Kronolith_View_Event(&$event)
    {
        $this->event = &$event;
    }

    function getTitle()
    {
        if (!$this->event || is_a($this->event, 'PEAR_Error')) {
            return _("Not Found");
        }
        return $this->event->getTitle();
    }

    function link()
    {
        return $this->event->getViewUrl();
    }

    function html($active = true)
    {
        global $conf, $prefs;

        if (!$this->event || is_a($this->event, 'PEAR_Error')) {
            echo '<h3>' . _("The requested event was not found.") . '</h3>';
            return;
        }

        $createdby = '';
        $modifiedby = '';
        $userId = Horde_Auth::getAuth();
        if ($this->event->getUID()) {
            /* Get the event's history. */
            $history = &Horde_History::singleton();
            $log = $history->getHistory('kronolith:' . $this->event->getCalendar() . ':' .
                                        $this->event->getUID());
            if ($log && !is_a($log, 'PEAR_Error')) {
                foreach ($log->getData() as $entry) {
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
            }
        }

        $creatorId = $this->event->getCreatorId();
        $description = $this->event->getDescription();
        $location = $this->event->getLocation();
        $private = $this->event->isPrivate() && $creatorId != Horde_Auth::getAuth();
        $owner = Kronolith::getUserName($creatorId);
        $status = Kronolith::statusToString($this->event->getStatus());
        $attendees = $this->event->getAttendees();
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
            if ($this->event->hasPermission(Horde_Perms::EDIT)) {
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
