<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */
class Sesha_Form_Category extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars);
        // This is probably wrong. The library should get the driver 
        // or the properties passed
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
        $this->appendButtons(_("Save Category"));

        $category_id = $vars->get('category_id');

        $priorities = array();
        for ($i = 0; $i < 100; $i++) {
            $priorities[] = $i;
        }

        try {
            $allproperties = $sesha_driver->getProperties();
        }
        catch (Sesha_Exception $e) {
            throw new Sesha_Exception($e);
        }
        $a = array();
        foreach ($allproperties as $p) {
            $a[$p['property_id']] = $p['property'];
        }
        if (!empty($category_id)) {
            try {
                $properties = $sesha_driver->getPropertiesForCategories($category_id);
            } catch (Sesha_Exception $e) {
                throw new Sesha_Exception($e);
            }
            $current = array();
            foreach ($properties as $s) {
                $current[$s['property_id']] = $s['property'];
            }
        }

        $this->addHidden('', 'actionID', 'text', false, false, null);
        $this->addHidden('', 'category_id', 'text', false, false, null);
        $this->addHidden('', 'submitbutton', 'text', false, false, null);
        $this->addVariable(_("Category Name"), 'category', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false);
        $this->addVariable(_("Sort Weight"), 'priority', 'enum', false, false, _("When categories are displayed, they will be shown in weight order from highest to lowest"), array($priorities));
        if (!count($a)) {
            $fieldtype = 'invalid';
            $a = _("No properties are currently configured. Use the \"Manage Properties\" tab above to add some.");
        } else {
            $fieldtype = 'multienum';
        }
        $mp = &$this->addVariable(_("Properties"), 'properties', $fieldtype, true, false, null, array($a));
        if (!empty($current)) {
            $mp->setDefault(array_keys($current));
        }

        $action = Horde_Form_Action::factory('submit');
    }
}
