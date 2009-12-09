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
     * @param Kronolith_Event &$event
     */
    function Kronolith_View_EditEvent(&$event)
    {
        $this->event = &$event;
    }

    function getTitle()
    {
        if (!$this->event || is_a($this->event, 'PEAR_Error')) {
            return _("Not Found");
        }
        return sprintf(_("Edit %s"), $this->event->getTitle());
    }

    function link()
    {
        return $this->event->getEditUrl();
    }

    function html($active = true)
    {
        $identity = Horde_Prefs_Identity::singleton();

        if (!$this->event || is_a($this->event, 'PEAR_Error')) {
            echo '<h3>' . _("The requested event was not found.") . '</h3>';
            return;
        }

        if ($this->event->hasPermission(Horde_Perms::EDIT)) {
            $calendar_id = $this->event->getCalendar();
        } else {
            $calendar_id = Kronolith::getDefaultCalendar(Horde_Perms::EDIT);
        }
        if (!$this->event->hasPermission(Horde_Perms::EDIT) &&
            !is_a($share = &$this->event->getShare(), 'PEAR_Error')) {
            $calendar_id .= ':' . $share->get('owner');
        }
        $_SESSION['kronolith']['attendees'] = $this->event->getAttendees();
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
        if ($this->event->getCreatorId() == Horde_Auth::getAuth()) {
            $perms |= Kronolith::PERMS_DELEGATE;
        }
        $calendars = Kronolith::listCalendars(false, $perms);

        $buttons = array();
        if (!$this->event->hasPermission(Horde_Perms::EDIT) &&
            (!empty($GLOBALS['conf']['hooks']['permsdenied']) ||
             $GLOBALS['perms']->hasAppPermission('max_events') === true ||
             $GLOBALS['perms']->hasAppPermission('max_events') > Kronolith::countEvents())) {
            $buttons[] = '<input type="submit" class="button" name="saveAsNew" value="' . _("Save As New") . '" />';
        } else {
            if ($this->event->hasPermission(Horde_Perms::EDIT)) {
                $buttons[] = '<input type="submit" class="button" name="save" value="' . _("Save Event") . '" />';
            }
            if ($this->event->isInitialized()) {
                if (!$this->event->recurs() &&
                    (!empty($conf['hooks']['permsdenied']) ||
                     $GLOBALS['perms']->hasAppPermission('max_events') === true ||
                     $GLOBALS['perms']->hasAppPermission('max_events') > Kronolith::countEvents())) {
                    $buttons[] = '<input type="submit" class="button" name="saveAsNew" value="' . _("Save As New") . '" />';
                }
            }
        }

        if (isset($url)) {
            $cancelurl = new Horde_Url($url);
        } else {
            $cancelurl = Horde::applicationUrl('month.php', true)
                ->add(array('month' => $month, 'year' => $year));
        }

        $event = &$this->event;

        // Tags
        $tagger = Kronolith::getTagger();
        $tags = $tagger->getTags($event->getUID(), 'event');
        $tags = implode(',', array_values($tags));

        echo '<div id="EditEvent"' . ($active ? '' : ' style="display:none"') . '>';
        Horde::addScriptFile('popup.js', 'horde');
        require KRONOLITH_TEMPLATES . '/edit/javascript.inc';
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
