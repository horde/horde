<?php
/**
 * Horde_Form for editing calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_EditCalendarForm class provides the form for editing a
 * calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_EditCalendar extends Horde_Form
{
    /**
     * Calendar being edited.
     */
    protected $_calendar;

    public function __construct($vars, $calendar)
    {
        $this->_calendar = $calendar;
        parent::__construct($vars, sprintf(_("Edit %s"), $calendar->get('name')));

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Tags"), 'tags', 'text', false);
        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(_("System Calendar"), 'system', 'boolean', false, false, _("System calendars don't have an owner. Only administrators can change the calendar settings and permissions."));
        }

        $this->setButtons(array(_("Save")));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description', 'tags', 'system') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Kronolith::updateShare($this->_calendar, $info);
    }

}
