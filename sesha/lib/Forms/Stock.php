<?php
/**
 * This class is the Stock form that will be responsible for displaying and
 * editing stock entries in the Sesha application.
 *
 * $Horde: sesha/lib/Forms/Stock.php,v 1.5 2009/07/14 18:43:45 selsky Exp $
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @since   Sesha 1
 * @package Sesha
 */

require_once 'Horde/Form/Action.php';

class StockForm extends Horde_Form {

    /**
     * The default constructor for the StockForm class.
     *
     * @param Horde_Variables $vars  The default variables to use.
     */
    function StockForm(&$vars)
    {
        global $backend;

        parent::Horde_Form($vars);

        // Buttons and hidden configuration
        $this->setButtons(_("Save Item"));
        $this->addHidden('', 'actionId', 'text', true);

        // Prepare the categories
        $cat = array();
        $categories = $backend->getCategories();
        foreach ($categories as $c) {
            $cat[$c['category_id']] = $c['category'];
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
            $cat = _("No categories are currently configured. Click 'Admin' (above) to add some.");
        } else {
            $fieldtype = 'multienum';
        }
        $categoryVar = &$this->addVariable(_("Category"), 'category_id',
                                           $fieldtype, true, false, null,
                                           array($cat));

        // Set the variables already stored in the Driver, if applicable
        foreach ($categoryIds as $categoryId) {
            $properties = $backend->getPropertiesForCategories($categoryId);
            if (!is_a($properties, 'PEAR_Error')) {
                foreach ($properties as $property) {
                    $fieldname   = 'property[' . $property['property_id'] . ']';
                    $fieldtitle  = $property['property'];
                    $fielddesc   = $property['description'];
                    if (!empty($property['unit'])) {
                        if (!empty($fielddesc)) {
                            $fielddesc .= ' -- ';
                        }
                        $fielddesc .= _("Unit: ") . $property['unit'];
                    }
                    $fieldtype   = $property['datatype'];
                    $fieldparams = array();
                    if (is_array($property['parameters'])) {
                        $fieldparams = $property['parameters'];
                        if (in_array($fieldtype, array('link', 'enum', 'multienum', 'mlenum', 'radio', 'set', 'sorter'))) {
                            $fieldparams['values'] = Sesha::getStringlistArray($fieldparams['values']);
                        }
                    }
                    $this->addVariable($fieldtitle, $fieldname, $fieldtype,
                                       false, false, $fielddesc, $fieldparams);
                }
            }
        }
        $this->addVariable(_("Note"), 'note', 'longtext', false);

        // Default action
        $action = &Horde_Form_Action::factory('submit');
        $categoryVar->setAction($action);
        $categoryVar->setOption('trackchange', true);
    }
}

class Horde_Form_Type_client extends Horde_Form_Type_enum {

    function init($values = null, $prompt = null)
    {
        global $conf, $registry;

        // Get list of clients, if available.
        if ($registry->hasMethod('clients/getClientSource')) {
            $source = $registry->call('clients/getClientSource');
            if (!empty($source)) {
                $results = $registry->call('clients/searchClients', array(array('')));
                $clientlist = $results[''];
                $clients = array();
                foreach ($clientlist as $client) {
                    $key = isset($client['id']) ? $client['id'] : $client['__key'];
                    $clients[$key] = isset($client[$conf['client']['field']]) ? $client[$conf['client']['field']] : '';
                }
                asort($clients);
                parent::init($clients);
            }
        }
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        $about = array();
        $about['name'] = _("Client");
        return $about;
    }

}
