<?php
/**
 * Horde_Form for subscribing to remote calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_SubscribeRemoteCalendarForm class provides the form for
 * subscribing to remote calendars.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_SubscribeRemoteCalendarForm extends Horde_Form
{
    public function __construct($vars)
    {
        parent::Horde_Form($vars, _("Subscribe to a Remote Calendar"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("URL"), 'url', 'text', true);
        $this->addVariable(_("Description"), 'desc', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Username"), 'user', 'text', false);
        $this->addVariable(_("Password"), 'password', 'password', false);

        $this->setButtons(array(_("Subscribe")));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        $info = array();
        foreach (array('name', 'url', 'user', 'password', 'color', 'desc') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        Kronolith::subscribeRemoteCalendar($info);
    }

}
