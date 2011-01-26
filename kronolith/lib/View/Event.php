<?php
/**
 * The Kronolith_View_Event:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Event
{
    /**
     *
     * @var Kronolith_Event
     */
    protected $_event;

    /**
     * @param Kronolith_Event $event
     */
    public function __construct(Kronolith_Event $event)
    {
        $this->_event = $event;
    }

    public function __get($property)
    {
        switch ($property) {
        case 'event':
            return $this->_event;
        default:
            throw new Exception(_("Property does not exist."));
        }
    }

    public function getTitle()
    {
        if (!$this->_event) {
            return _("Not Found");
        }
        if (is_string($this->_event)) {
            return $this->_event;
        }
        return $this->_event->getTitle();
    }

    public function link()
    {
        return $this->_event->getViewUrl();
    }

    public function html($active = true)
    {
        if (!$this->_event) {
            echo '<h3>' . _("Event not found") . '</h3>';
            exit;
        }
        if (is_string($this->_event)) {
            echo '<h3>' . $this->_event . '</h3>';
            exit;
        }

        global $conf, $prefs;

        $createdby = '';
        $modifiedby = '';
        $userId = $GLOBALS['registry']->getAuth();
        if ($this->_event->uid) {
            /* Get the event's history. */
            try {
                $log = $GLOBALS['injector']->getInstance('Horde_History')
                    ->getHistory('kronolith:' . $this->_event->calendar . ':' . $this->_event->uid);
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

        $creatorId = $this->_event->creator;
        $description = $this->_event->description;
        $location = $this->_event->location;
        $eventurl = $this->_event->url;
        $private = $this->_event->private && $creatorId != $GLOBALS['registry']->getAuth();
        $owner = Kronolith::getUserName($creatorId);
        $status = Kronolith::statusToString($this->_event->status);
        $attendees = $this->_event->attendees;
        $resources = $this->_event->getResources();
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
        $tags = implode(', ', $this->_event->tags);


        echo '<div id="Event"' . ($active ? '' : ' style="display:none"') . '>';
        require KRONOLITH_TEMPLATES . '/view/view.inc';
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            /* We check for read permissions, because we can always save a
             * copy if we can read the event. */
            if ($this->_event->hasPermission(Horde_Perms::READ)) {
                $edit = new Kronolith_View_EditEvent($this->_event);
                $edit->html(false);
            }
            if ($this->_event->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Kronolith_View_DeleteEvent($this->_event);
                $delete->html(false);
            }
        }
    }

    public function getName()
    {
        return 'Event';
    }

}
