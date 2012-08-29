<?php
/**
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */
class Sesha_Form_CategoryList extends Horde_Form {

    public function __construct($vars)
    {
        parent::__construct($vars);
        // This is probably wrong. The library should get the driver 
        // or the properties passed
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
        $this->setButtons(array(_("Edit Category"),
                                _("Delete Category")));
        $categories = $sesha_driver->getCategories();
        $params = array();
        foreach ($categories as $category) {
            $params[$category->category_id] = $category->category;
        }
        $title = !empty($title) ? $title : _("Edit a category");
        $this->setTitle($title);

        $this->addHidden('', 'actionID', 'text', false, false, null, array('edit_category'));
        if (!count($params)) {
            $fieldtype = 'invalid';
            $params = _("No categories are currently configured. Use the form below to add one.");
        } else {
            $fieldtype = 'enum';
        }
        $this->addVariable(_("Category"), 'category_id', $fieldtype, true, false, null, array($params));
    }
}
