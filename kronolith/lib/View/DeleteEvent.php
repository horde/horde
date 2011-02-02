<?php
/**
 * The Kronolith_View_DeleteEvent:: class provides an API for viewing
 * event delete forms.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_DeleteEvent
{
    /**
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
        return sprintf(_("Delete %s"), $this->_event->getTitle());
    }

    public function link()
    {
        return $this->_event->getDeleteUrl();
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
        if (!$this->_event->recurs()) {
            require KRONOLITH_TEMPLATES . '/delete/one.inc';
        } else {
            require KRONOLITH_TEMPLATES . '/delete/delete.inc';
        }
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->_event->hasPermission(Horde_Perms::READ)) {
                $view = new Kronolith_View_Event($this->_event);
                $view->html(false);
            }
            if ($this->_event->hasPermission(Horde_Perms::EDIT)) {
                $edit = new Kronolith_View_EditEvent($this->_event);
                $edit->html(false);
            }
        }
    }

    public function getName()
    {
        return 'DeleteEvent';
    }

}
