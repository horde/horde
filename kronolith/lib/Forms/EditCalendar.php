<?php
/**
 * Horde_Form for editing calendars.
 *
 * $Horde: kronolith/lib/Forms/EditCalendar.php,v 1.2 2008/11/12 09:16:12 wrobel Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/** Variables */
require_once 'Horde/Variables.php';

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Kronolith_EditCalendarForm class provides the form for
 * editing a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Kronolith 2.2
 * @package Kronolith
 */
class Kronolith_EditCalendarForm extends Horde_Form {

    /**
     * Calendar being edited
     */
    var $_calendar;

    function Kronolith_EditCalendarForm(&$vars, &$calendar)
    {
        $this->_calendar = &$calendar;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $calendar->get('name')));

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $original_name = $this->_calendar->get('name');
        $new_name = $this->_vars->get('name');
        $this->_calendar->set('name', $new_name);
        $this->_calendar->set('desc', $this->_vars->get('description'));

        if ($original_name != $new_name) {
            $result = $GLOBALS['kronolith_driver']->rename($original_name, $new_name);
            if (is_a($result, 'PEAR_Error')) {
                return PEAR::raiseError(sprintf(_("Unable to rename \"%s\": %s"), $original_name, $result->getMessage()));
            }
        }

        $result = $this->_calendar->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save calendar \"%s\": %s"), $new_name, $result->getMessage()));
        }
        return true;
    }

}
