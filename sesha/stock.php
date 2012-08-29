<?php
/**
 * This file will perform all major operations on Stock items with the standard
 * Sesha_Driver backends. This file will also prevent any non-authorized user
 * to modify the inventory (useful for displaying the inventory in a store or
 * catalog).
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Bo Daley <bo@darkwork.net>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('sesha');

$perms = $GLOBALS['injector']->getInstance('Horde_Perms');
$sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
// Basic actions and configuration.
$actionId = Horde_Util::getFormData('actionId');
$stock_id = Horde_Util::getFormData('stock_id');
$active = Sesha::isAdmin(Horde_Perms::READ) ||
    $perms->hasPermission('sesha:addStock', $registry->getAuth(), Horde_Perms::READ);

$baseUrl = $registry->get('webroot', 'sesha');

// Determine action.
switch ($actionId) {
case 'add_stock':
    $url = new Horde_Url($baseUrl . '/stock.php');
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('actionId', $actionId);
    $vars->set('stock_id', $stock_id);
    $params = array('varrenderer_driver' => array('sesha', 'Stockedit_Html'));
    $renderer = new Horde_Form_Renderer($params);
    $form = new Sesha_Form_Stock($vars);
    $form->setTitle(_("Add Stock To Inventory"));

    $valid = $form->validate($vars);
    if ($valid && $form->isSubmitted()) {
        // Add the item to the inventory.
        try  {
            $ret = $sesha_driver->add(array(
            'stock_name' => $vars->get('stock_name'),
            'note'       => $vars->get('note')));
        } catch (Sesha_Exception $e) {
            $notification->push(sprintf(
                _("There was a problem adding the item: %s"),
                $ret->getMessage()), 'horde.error');
            header('Location: ' . $url);
            exit;
        }
        $stock_id = $ret;
        $notification->push(_("The item was added succcessfully."),
                            'horde.success');
        // Add categories to the item.
        $sesha_driver->updateCategoriesForStock($stock_id,
                                            $vars->get('category_id'));
        // Add properties to the item as well.
        $sesha_driver->updatePropertiesForStock($stock_id,
                                            $vars->get('property'));

        $url->add(array('actionId' => 'view_stock',
                        'stock_id' => $stock_id->stock_id));
        header('Location: ' . $url->toString(true, true));
        exit;
    }
    break;

case 'remove_stock':
    if (Sesha::isAdmin(Horde_Perms::DELETE)) {
        try {
            $ret = $sesha_driver->delete($stock_id);
        } catch (Sesha_Exception $e) {
            $notification->push(sprintf(_("There was a problem with the driver while deleting: %s"), $e->getMessage()), 'horde.error');
            header('Location: ' . Horde::url($baseUrl .'/list.php', true));
            exit;
        }
        $notification->push(sprintf(_("Item number %d was successfully deleted"), $stock_id), 'horde.success');
    } else {
        $notification->push(_("You do not have sufficient permissions to delete."), 'horde.error');
    }
    header('Location: ' . Horde::url($baseUrl . '/list.php', true));
    exit;

case 'view_stock':
    $active = false;

case 'update_stock':
    if (!$active) {
        $form_title = _("View Inventory Item");
    }
    // Get the stock item.
    $stock = $sesha_driver->fetch($stock_id);
    $categories = $sesha_driver->getCategories($stock_id);
    $values = $sesha_driver->getValuesForStock($stock_id);

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('actionId', $actionId);
    $vars->set('stock_id', $stock_id);
    $formname = $vars->get('formname');
    if (empty($formname)) {
        // Configure attributes.
        if ($stock) {
            foreach ($stock as $key => $val) {
                $vars->set($key, $val);
            }
        }
        // Set up categories.
        $categoryIds = array();
        foreach ($categories as $c) {
            $categoryIds[] = $c['category_id'];
        }
        $vars->set('category_id', $categoryIds);

        // Properties for categories.
        $p = array();
        foreach ($values as $value) {
            $p[$value->property_id] = $value->getDataValue();
        }
        $vars->set('property', $p);
    }

    // Set up form variables.
    $params = array('varrenderer_driver' => array('sesha', 'stockedit_Html'));
    $renderer = new Horde_Form_Renderer($params);
    $form = new Sesha_Form_Stock($vars);
    $form->setTitle((!isset($form_title) ? _("Edit Inventory Item") : $form_title));
    if (!$active) {
        $form->setExtra('<span class="smallheader">' . Horde::link(Horde_Util::addParameter(Horde::url('stock.php'), array('stock_id' => $vars->get('stock_id'), 'actionId' => 'update_stock'))) . _("Edit") . '</a></span>');
    }

    if ($form->validate($vars) && $form->isSubmitted()) {
        // Update the stock item.
        try {
            $result = $sesha_driver->modify($vars->get('stock_id'), array(
                'stock_name' => Horde_Util::getFormData('stock_name'),
                'note'       => Horde_Util::getFormData('note')));
        } catch (Sesha_Exception $e) {
            $notification->push(sprintf(
                _("There was a problem updating the inventory: %s"),
                $e->getMessage()), 'horde.error');
        }
        // Update categories for the stock item.
        $category = $vars->get('category_id');
        if (!empty($category)) {
            $sesha_driver->updateCategoriesForStock($stock_id, $category);
            $sesha_driver->clearPropertiesForStock($stock_id, $category);
        }

        // Update properties.
        $property = $vars->get('property');
        if (count($property)) {
            $sesha_driver->updatePropertiesForStock($stock_id, $property);
        }

        $notification->push(_("The stock item was successfully updated."), 'horde.success');

        // Redirect after update.
        $url = Horde::selfUrl(false, true, true);
        $url = Horde_Util::addParameter($url, array('actionId' => 'view_stock',
                                                'stock_id' => $vars->get('stock_id')),
                                    null, false);
        header('Location: ' . $url);
        exit;
    }
    break;

default:
    header('Location: ' . Horde::url($baseUrl . '/list.php', true));
    exit;
}

// Begin page display.
// require SESHA_TEMPLATES . '/menu.inc';
$page_output->header(array(
    'title' => $title
));
require SESHA_TEMPLATES . '/menu.inc';
$notification->notify(array('listeners' => 'status'));

if ($active) {
    $form->renderActive($renderer, $vars, Horde::selfUrl(), 'post');
} else {
    $form->renderInactive($renderer, $vars, Horde::selfUrl(), 'post');
}
$page_output->footer();
