<?php
/**
 * Horde_Form for unsubscribing from remote calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_UnsubscribeRemoteCalendarForm class provides the form for
 * unsubscribing from remote calendars.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_UnsubscribeRemoteCalendar extends Horde_Form
{
    public function __construct($vars, $calendar)
    {
        parent::__construct($vars, sprintf(_("Unsubscribe from %s"), $calendar['name']));

        $this->addHidden('', 'url', 'text', true);
        $this->addVariable(sprintf(_("Really unsubscribe from the calendar \"%s\" (%s)?"), $calendar['name'], $calendar['url']), 'desc', 'description', false);

        $this->setButtons(array(
            array('class' => 'horde-delete', 'value' => _("Unsubscribe")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            Horde::url($GLOBALS['prefs']->getValue('defaultview') . '.php', true)
                ->redirect();
        }
        return Kronolith::unsubscribeRemoteCalendar($this->_vars->get('url'));
    }
}
