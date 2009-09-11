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

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Category"), 'category', 'text', false);
        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        $new = array('name' => $this->_vars->get('name'),
                     'category' =>$this->_vars->get('category'));

        $resource = new Kronolith_Resource_Single($new);
        return $results = Kronolith::addResource($resource);
    }

}
