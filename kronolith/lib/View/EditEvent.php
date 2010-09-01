<?php
/**
 * The Kronolith_View_EditEvent:: class provides an API for viewing
 * event edit forms.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_EditEvent {

    var $event;

    /**
     * @param Kronolith_Event $event
     */
    function Kronolith_View_EditEvent($event)
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
        return sprintf(_("Edit %s"), $this->event->getTitle());
    }

    function link()
    {
        return $this->event->getEditUrl();
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

        $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();

        if ($this->event->hasPermission(Horde_Perms::EDIT)) {
            $calendar_id = $this->event->calendarType . '_' . $this->event->calendar;
        } else {
            $calendar_id = 'internal_' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT);
        }
        if (!$this->event->hasPermission(Horde_Perms::EDIT)) {
            try {
                $calendar_id .= ':' . $this->event->getShare()->get('owner');
            } catch (Exception $e) {
            }
        }
        $_SESSION['kronolith']['attendees'] = $this->event->attendees;
        $_SESSION['kronolith']['resources'] = $this->event->getResources();
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
        if ($this->event->creator == $GLOBALS['registry']->getAuth()) {
            $perms |= Kronolith::PERMS_DELEGATE;
        }
        $calendars = Kronolith::listCalendars($perms, true);

        $buttons = array();
        if (!$this->event->hasPermission(Horde_Perms::EDIT) &&
            (!empty($GLOBALS['conf']['hooks']['permsdenied']) ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())) {
            $buttons[] = '<input type="submit" class="button" name="saveAsNew" value="' . _("Save As New") . '" />';
        } else {
            if ($this->event->hasPermission(Horde_Perms::EDIT)) {
                $buttons[] = '<input type="submit" class="button" name="save" value="' . _("Save Event") . '" />';
            }
            if ($this->event->initialized) {
                if (!$this->event->recurs() &&
                    (!empty($conf['hooks']['permsdenied']) ||
                     $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
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

        $event = &$this->event;

        // Tags
        $tagger = Kronolith::getTagger();
        $tags = $tagger->getTags($event->uid, 'event');
        $tags = implode(',', array_values($tags));

        Horde_Core_Ui_JsCalendar::init(array(
            'full_weekdays' => true
        ));

        Horde::addScriptFile('edit.js', 'kronolith');
        Horde::addScriptFile('popup.js', 'horde');

        echo '<div id="EditEvent"' . ($active ? '' : ' style="display:none"') . '>';
        require KRONOLITH_TEMPLATES . '/edit/edit.inc';
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->event->hasPermission(Horde_Perms::READ)) {
                $view = new Kronolith_View_Event($this->event);
                $view->html(false);
            }
            if ($this->event->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Kronolith_View_DeleteEvent($this->event);
                $delete->html(false);
            }
        }
    }

    function getName()
    {
        return 'EditEvent';
    }

}
