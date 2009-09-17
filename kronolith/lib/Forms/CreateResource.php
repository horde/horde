<?php
/**
 * Horde_Form for creating resource calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Kronolith_CreateResourceForm class provides the form for
 * creating a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_CreateResourceForm extends Horde_Form {

    function Kronolith_CreateResourceForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Resource"));
        $responses =  array(Kronolith_Resource::RESPONSETYPE_ALWAYS_ACCEPT => _("Always Accept"),
                            Kronolith_Resource::RESPONSETYPE_ALWAYS_DECLINE => _("Always Decline"),
                            Kronolith_Resource::RESPONSETYPE_AUTO => _("Automatically"),
                            Kronolith_Resource::RESPONSETYPE_MANUAL => _("Manual"),
                            Kronolith_Resource::RESPONSETYPE_NONE => _("None"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Response type"), 'responsetype', 'enum', true, false, null, array('enum' => $responses));
        $this->addVariable(_("Category"), 'category', 'text', false);
        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        $new = array('name' => $this->_vars->get('name'),
                     'category' => $this->_vars->get('category'),
                     'description' => $this->_vars->get('description'),
                     'response_type' => $this->_vars->get('response_type'));

        $resource = new Kronolith_Resource_Single($new);
        return $results = Kronolith_Resource::addResource($resource);
    }

}
