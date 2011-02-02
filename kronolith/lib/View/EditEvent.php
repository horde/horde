<?php
/**
 * The Kronolith_View_EditEvent:: class provides an API for viewing
 * event edit forms.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_EditEvent
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
            throw new Kronolith_Exception('Property does not exist.');
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
        return sprintf(_("Edit %s"), $this->_event->getTitle());
    }

    public function link()
    {
        return $this->_event->getEditUrl();
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

        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();

        if ($this->_event->hasPermission(Horde_Perms::EDIT)) {
            $calendar_id = $this->_event->calendarType . '_' . $this->_event->calendar;
        } else {
            $calendar_id = 'internal_' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT);
        }
        if (!$this->_event->hasPermission(Horde_Perms::EDIT)) {
            try {
                $calendar_id .= ':' . $this->_event->getShare()->get('owner');
            } catch (Exception $e) {
            }
        }
        $GLOBALS['session']->set('kronolith', 'attendees', $this->_event->attendees);
        $GLOBALS['session']->set('kronolith', 'resources', $this->_event->getResources());
        if ($datetime = Horde_Util::getFormData('datetime')) {
            $datetime = new Horde_Date($datetime);
            $month = $datetime->month;
            $year = $datetime->year;
        } else {
            $month = Horde_Util::getFormData('month', date('n'));
            $year = Horde_Util::getFormData('year', date('Y'));
        }

        $url = Horde_Util::getFormData('url');
        $perms = Horde_Perms::EDIT;
        if ($this->_event->creator == $GLOBALS['registry']->getAuth()) {
            $perms |= Kronolith::PERMS_DELEGATE;
        }
        $calendars = Kronolith::listCalendars($perms, true);

        $buttons = array();
        if (!$this->_event->hasPermission(Horde_Perms::EDIT) &&
            ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())) {
            $buttons[] = '<input type="submit" class="button" name="saveAsNew" value="' . _("Save As New") . '" />';
        } else {
            if ($this->_event->hasPermission(Horde_Perms::EDIT)) {
                $buttons[] = '<input type="submit" class="button" name="save" value="' . _("Save Event") . '" />';
            }
            if ($this->_event->initialized) {
                if (!$this->_event->recurs() &&
                    ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
                     $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())) {
                    $buttons[] = '<input type="submit" class="button" name="saveAsNew" value="' . _("Save As New") . '" />';
                }
            }
        }

        if (isset($url)) {
            $cancelurl = new Horde_Url($url);
        } else {
            $cancelurl = Horde::url('month.php', true)
                ->add(array('month' => $month, 'year' => $year));
        }

        $event = &$this->_event;
        $tags = implode(',', array_values($event->tags));

        Horde_Core_Ui_JsCalendar::init(array(
            'full_weekdays' => true
        ));

        Horde::addScriptFile('edit.js', 'kronolith');
        Horde::addScriptFile('popup.js', 'horde');

        echo '<div id="EditEvent"' . ($active ? '' : ' style="display:none"') . '>';
        require KRONOLITH_TEMPLATES . '/edit/edit.inc';
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->_event->hasPermission(Horde_Perms::READ)) {
                $view = new Kronolith_View_Event($this->_event);
                $view->html(false);
            }
            if ($this->_event->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Kronolith_View_DeleteEvent($this->_event);
                $delete->html(false);
            }
        }
    }

    public function getName()
    {
        return 'EditEvent';
    }

}
