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

@define('SESHA_BASE', dirname(__FILE__));
require_once SESHA_BASE . '/lib/base.php';
require_once SESHA_BASE . '/lib/Forms/Stock.php';

// Basic actions and configuration.
$actionId = Horde_Util::getFormData('actionId');
$stock_id = Horde_Util::getFormData('stock_id');
$active = Horde_Auth::isAdmin('sesha:admin') || $perms->hasPermission('sesha:addStock', Horde_Auth::getAuth(), Horde_Perms::READ);

// Determine action.
switch ($actionId) {
case 'add_stock':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('actionId', $actionId);
    $vars->set('stock_id', $stock_id);
    $params = array('varrenderer_driver' => array('sesha', 'stockedit_html'));
    $renderer = new Horde_Form_Renderer($params);
    $form = new StockForm($vars);
    $form->setTitle(_("Add Stock To Inventory"));

    $valid = $form->validate($vars);
    if ($valid && $form->isSubmitted()) {
        // Add the item to the inventory.
        $ret = $backend->add(array(
            'stock_id'   => $vars->get('stock_id'),
            'stock_name' => $vars->get('stock_name'),
            'note'       => $vars->get('note')));
        if (is_a($ret, 'PEAR_Error')) {
            $notification->push(sprintf(
                _("There was a problem adding the item: %s"),
                $ret->getMessage()), 'horde.error');
        } else {
            $stock_id = $ret;
            $notification->push(_("The item was added succcessfully."),
                                'horde.success');
            // Add categories to the item.
            $backend->updateCategoriesForStock($stock_id,
                                               $vars->get('category_id'));
            // Add properties to the item as well.
            $backend->updatePropertiesForStock($stock_id,
                                               $vars->get('property'));
        }

        $url = Horde::selfUrl(false, true, true);
        $url = Horde_Util::addParameter($url, array('actionId' => 'view_stock',
                                              'stock_id' => $stock_id),
                                  null, false);
        header('Location: ' . $url);
        exit;
    }
    break;

case 'remove_stock':
    if (!Horde_Auth::isAdmin('sesha:admin', Horde_Perms::DELETE)) {
        $notification->push(
                            _("You do not have sufficient permissions to delete."),
                            'horde.error');
        header('Location: ' . Horde::applicationUrl('list.php', true));
        exit;
    }
    $ret = $backend->delete($stock_id);
    if (is_a($ret, 'PEAR_Error')) {
        $notification->push(sprintf(
            _("There was a problem with the driver while deleting: %s"),
            $ret->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(
            _("Item number %d was successfully deleted"), $stock_id),
            'horde.success');
    }
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;

case 'view_stock':
    $active = false;

case 'update_stock':
    if (!$active) {
        $form_title = _("View Inventory Item");
    }
    // Get the stock item.
    $stock = $backend->fetch($stock_id);
    $categories = $backend->getCategories($stock_id);
    $properties = $backend->getPropertiesForStock($stock_id);

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
        foreach ($properties as $property) {
            $p[$property['property_id']] = $property['txt_datavalue'];
        }
        $vars->set('property', $p);
    }

    // Set up form variables.
    $params = array('varrenderer_driver' => array('sesha', 'stockedit_html'));
    $renderer = new Horde_Form_Renderer($params);
    $form = new StockForm($vars);
    $form->setTitle((!isset($form_title) ? _("Edit Inventory Item") : $form_title));
    if (!$active) {
        $form->setExtra('<span class="smallheader">' . Horde::link(Horde_Util::addParameter(Horde::applicationUrl('stock.php'), array('stock_id' => $vars->get('stock_id'), 'actionId' => 'update_stock'))) . _("Edit") . '</a></span>');
    }

    if ($form->validate($vars) && $form->isSubmitted()) {
        // Update the stock item.
        $result = $backend->modify($vars->get('stock_id'), array(
            'stock_name' => Horde_Util::getFormData('stock_name'),
            'note'       => Horde_Util::getFormData('note')));

        // Update categories for the stock item.
        $category = $vars->get('category_id');
        if (!empty($category)) {
            $backend->updateCategoriesForStock($stock_id, $category);
            $backend->clearPropertiesForStock($stock_id, $category);
        }

        // Update properties.
        $property = $vars->get('property');
        if (count($property)) {
            $backend->updatePropertiesForStock($stock_id, $property);
        }

        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(_("The stock item was successfully updated."),
                'horde.success');

            // Redirect after update.
            $url = Horde::selfUrl(false, true, true);
            $url = Horde_Util::addParameter($url, array('actionId' => 'view_stock',
                                                  'stock_id' => $vars->get('stock_id')),
                                      null, false);
            header('Location: ' . $url);
            exit;
        } else {
            $notification->push(sprintf(
                _("There was a problem updating the inventory: %s"),
                $result->getMessage()), 'horde.error');
        }
    }
    break;

default:
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

// Begin page display.
require SESHA_TEMPLATES . '/common-header.inc';
require SESHA_TEMPLATES . '/menu.inc';
if ($active) {
    $form->renderActive($renderer, $vars, Horde::selfUrl(), 'post');
} else {
    $form->renderInactive($renderer, $vars, Horde::selfUrl(), 'post');
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
