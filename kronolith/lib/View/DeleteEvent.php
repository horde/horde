<?php
/**
 * The Kronolith_View_DeleteEvent:: class provides an API for viewing
 * event delete forms.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_DeleteEvent {

    var $event;

    /**
     * @param Kronolith_Event $event
     */
    function Kronolith_View_DeleteEvent($event)
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
        return sprintf(_("Delete %s"), $this->event->getTitle());
    }

    function link()
    {
        return $this->event->getDeleteUrl();
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

        if ($datetime = Horde_Util::getFormData('datetime')) {
            $datetime = new Horde_Date($datetime);
            $month = $datetime->month;
            $year = $datetime->year;
            $day = $datetime->mday;
        } else {
            $month = Horde_Util::getFormData('month', date('n'));
            $day = Horde_Util::getFormData('mday', date('j'));
            $year = Horde_Util::getFormData('year', date('Y'));
        }

        $url = Horde_Util::getFormData('url');

        echo '<div id="DeleteEvent"' . ($active ? '' : ' style="display:none"') . '>';
        if (!$this->event->recurs()) {
            require KRONOLITH_TEMPLATES . '/delete/one.inc';
        } else {
            require KRONOLITH_TEMPLATES . '/delete/delete.inc';
        }
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->event->hasPermission(Horde_Perms::READ)) {
                $view = new Kronolith_View_Event($this->event);
                $view->html(false);
            }
            if ($this->event->hasPermission(Horde_Perms::EDIT)) {
                $edit = new Kronolith_View_EditEvent($this->event);
                $edit->html(false);
            }
        }
    }

    function getName()
    {
        return 'DeleteEvent';
    }

}
