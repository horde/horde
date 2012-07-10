<?php
/**
 * This class is the Stock form that will be responsible for displaying and
 * editing stock entries in the Sesha application.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @since   Sesha 1
 * @package Sesha
 */

class Sesha_Form_Stock extends Horde_Form {

    /**
     * The default constructor for the StockForm class.
     *
     * @param Horde_Variables $vars  The default variables to use.
     */
    public function __construct($vars)
    {

        parent::__construct($vars);
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();

        // Buttons and hidden configuration
        $this->setButtons(_("Save Item"));
        $this->addHidden('', 'actionId', 'text', true);

        // Prepare the categories
        $cat = array();
        $categories = $sesha_driver->getCategories();
        foreach ($categories as $c) {
            $cat[$c->category_id] = $c->category;
        }
        // Get the list of selected categories
        $categoryIds = array();
        $t = $vars->get('category_id');
        if (!is_array($t)) {
            $t = array($t);
        }
        $categoryIds = array_merge($categoryIds, $t);
        // The stock ID should only be editable if you are adding a new item;
        // otherwise let the user know what the stock_id is, and then make a
        // read-only required hidden variable
        if ($vars->get('actionId') == 'add_stock') {
            $this->addVariable(_("Stock ID"), 'stock_id', 'int', false, false);
        } else {
            $this->addVariable(_("Stock ID"), 'stock_id', 'int', false, true);
            $this->addHidden('', 'stock_id', 'int', true, true);
        }
        // Basic variables for any stock item
        $this->addVariable(_("Name"), 'stock_name', 'text', false, false);
        if (!count($cat)) {
            $fieldtype = 'invalid';
            $cat = _("No categories are currently configured. Click \"Administration\" on the left to add some.");
        } else {
            $fieldtype = 'multienum';
        }
        $categoryVar = $this->addVariable(_("Category"), 'category_id',
                                           $fieldtype, true, false, null,
                                           array($cat));

        // Set the variables already stored in the Driver, if applicable
        try {
            $properties = $sesha_driver->getPropertiesForCategories($categoryIds);
        } catch (Sesha_Exception $e) {
            throw new Sesha_Exception($e);
        }

        foreach ($properties as $property) {
            $fieldname   = 'property[' . $property->property_id . ']';
            $fieldtitle  = $property->property;
            $fielddesc   = $property->description;
            if (!empty($property->unit)) {
                if (!empty($fielddesc)) {
                    $fielddesc .= ' -- ';
                }
                $fielddesc .= _("Unit: ") . $property->unit;
            }
            $fieldtype   = $property->datatype;
            $fieldparams = array();
            if (is_array($property->parameters)) {
                $fieldparams = $property->parameters;
                if (in_array($fieldtype, array('link', 'enum', 'multienum', 'mlenum', 'radio', 'set', 'sorter'))) {
                    $fieldparams->values = Sesha::getStringlistArray($fieldparams->values);
                }
            }
            $this->addVariable($fieldtitle, $fieldname, $fieldtype,
                                false, false, $fielddesc, $fieldparams);
        }
        $this->addVariable(_("Note"), 'note', 'longtext', false);

        // Default action
        $action = Horde_Form_Action::factory('submit');
        $categoryVar->setAction($action);
        $categoryVar->setOption('trackchange', true);
    }
}
