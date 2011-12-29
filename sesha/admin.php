<?php
/**
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Bo Daley <bo@darkwork.net>
 */

@define('SESHA_BASE', dirname(__FILE__));
require_once SESHA_BASE . '/lib/base.php';
require_once 'Horde/UI/Tabs.php';

$vars = Horde_Variables::getDefaultVariables();
$category_id = $vars->get('category_id');
$property_id = $vars->get('property_id');
$actionID = $vars->get('actionID');

// Admin actions.
$adminurl = Horde::applicationUrl('admin.php');
$tabs = new Horde_Ui_Tabs('actionID', $vars);
$tabs->addTab(_("Manage Categories"), $adminurl, 'list_categories');
$tabs->addTab(_("Manage Properties"), $adminurl, 'list_properties');

if (!Horde_Auth::isAdmin('sesha:admin')) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

/* Run through the action handlers. */
switch ($actionID) {

case 'add_category':
    require_once SESHA_BASE . '/lib/Forms/Category.php';
    $title = _("Add a category");
    $vars->set('actionID', $actionID);
    $renderer = new Horde_Form_Renderer();
    $form = new CategoryForm($vars);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        // Save category details.
        $category_id = $backend->addCategory($info);
        if (!is_a($category_id, 'PEAR_Error')) {
            $result = $backend->setPropertiesForCategory($category_id,
                                                         $vars->get('properties'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("New category added successfully."), 'horde.success');
            } else {
                $notification->push(_("Could not add properties to new category: %s, %s") . $category_id->getMessage(), $result->getMessage(), 'horde.warning');
            }
        } else {
            $notification->push(_("Could not add new category.") . $category_id->getMessage(), 'horde.warning');
        }
        $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_categories');
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    break;

case 'edit_category':
    $category = $backend->getCategory($category_id);
    if (!is_a($category, 'PEAR_Error')) {
        require_once SESHA_BASE . '/lib/Forms/Category.php';
        $renderer = new Horde_Form_Renderer();
        if ($vars->get('submitbutton') == _("Edit Category") ||
            $vars->get('submitbutton') == _("Save Category")) {
            $title = sprintf(_("Modifying %s"), $category['category']);
            $vars->set('actionID', $actionID);
            $form = new CategoryForm($vars);
            $form->setTitle($title);
            if ($form->validate($vars)) {
                // Save category details.
                $form->getInfo($vars, $info);
                $result = $backend->updateCategory($info);
                if (!is_a($result, 'PEAR_Error')) {
                    $result = $backend->setPropertiesForCategory($vars->get('category_id'), $vars->get('properties'));
                    if (!is_a($result, 'PEAR_Error')) {
                        $notification->push(_("Updated category successfully."), 'horde.success');
                    } else {
                        $notification->push(_("Could not update properties for this category."), 'horde.warning');
                    }
                } else {
                    $notification->push(_("Could not update category details."), 'horde.warning');
                }
                $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_categories');
                header('Location: ' . Horde::applicationUrl($url, true));
                exit;
            } else {
                foreach ($category as $key => $val) {
                    $vars->set($key, $val);
                }
            }
        } elseif ($vars->get('submitbutton') == _("Delete Category")) {
            $title = sprintf(_("Delete Category \"%s\""), $category['category']);
            $vars->set('actionID', 'delete_category');
            $form = new CategoryDeleteForm($vars);
            $form->setTitle($title);
        }
    } else {
        $title = _("Unknown category");
        $name = '';
    }
    break;

case 'delete_category':
    if ($vars->get('confirm') == 'yes') {
        if (is_a($backend->deleteCategory($category_id), 'PEAR_Error')) {
            $notification->push(_("There was an error removing the category."), 'horde.warning');
        } else {
            $notification->push(_("The category was deleted."), 'horde.success');
        }
    } else {
        $notification->push(_("The category was not deleted."), 'horde.warning');
    }
    $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_categories');
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;

case 'edit_property':
    $property = $backend->getProperty($property_id);
    if (!is_a($property, 'PEAR_Error')) {
        require_once SESHA_BASE . '/lib/Forms/Property.php';
        $renderer = new Horde_Form_Renderer();
        if ($vars->get('submitbutton') == _("Delete Property")) {
            $title = sprintf(_("Delete Property \"%s\""), $property['property']);
            $vars->set('actionID', 'delete_property');
            $form = new PropertyDeleteForm($vars);
            $form->setTitle($title);
        } else {
            $title = sprintf(_("Modifying property \"%s\""), $property['property']);
            $vars->set('actionID', $actionID);
            $form = new PropertyForm($vars);
            $form->setTitle($title);
            if ($form->validate($vars)) {
                // Save property details.
                $form->getInfo($vars, $info);
                $result = $backend->updateProperty($info);
                if (!is_a($result, 'PEAR_Error')) {
                    $notification->push(_("Updated property successfully."), 'horde.success');
                    $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_properties');
                    header('Location: ' . Horde::applicationUrl($url, true));
                    exit;
                } else {
                    $notification->push(_("Could not update property details."), 'horde.warning');
                }
            } elseif ($vars->get('datatype') == $vars->get('__old_datatype')) {
                foreach ($property as $key => $val) {
                    $vars->set($key, $val);
                }
            }
        }
    } else {
        $title = _("Unknown property");
        $name = '';
    }
    break;

case 'delete_property':
    if ($vars->get('confirm') == 'yes') {
        if (is_a($backend->deleteProperty($property_id), 'PEAR_Error')) {
            $notification->push(_("There was an error removing the property."), 'horde.warning');
        } else {
            $notification->push(_("The property was deleted."), 'horde.success');
        }
    } else {
        $notification->push(_("The property was not deleted."), 'horde.warning');
    }
    $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_properties');
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;

case 'add_property':
    require_once SESHA_BASE . '/lib/Forms/Property.php';
    $title = _("Add a property");
    $vars->set('actionID', $actionID);
    $renderer = new Horde_Form_Renderer();
    $form = new PropertyForm($vars);
    $form->setTitle(_("Add a new property"));
    if ($form->validate($vars)) {
        // Save property details.
        $form->getInfo($vars, $info);
        $property_id = $backend->addProperty($info);
        if (!is_a($property_id, 'PEAR_Error')) {
            $notification->push(_("New property added successfully."), 'horde.success');
            $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_properties');
            header('Location: ' . Horde::applicationUrl($url, true));
            exit;
        } else {
            $notification->push(_("Could not add property.") . $property_id->getMessage(), 'horde.warning');
        }
    }
    break;

default:
case 'list_categories':
    require_once SESHA_BASE . '/lib/Forms/Category.php';
    $vars->set('actionID', 'edit_category');
    $renderer = new Horde_Form_Renderer();
    $form = new CategoryListForm($vars, 'admin.php', 'post');
    $valid = $form->validate($vars);
    if ($valid) {
        // Redirect to the category list form.
        $url = Horde_Util::addParameter('admin.php', 'actionID', 'edit_category');
        $url = Horde_Util::addParameter($url, 'category_id', $vars->get('category_id'));
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    $vars2 = Horde_Variables::getDefaultVariables();
    $form2 = new CategoryForm($vars2, 'admin.php', 'post');
    $form2->setTitle(_("Add a new category"));
    $vars2->set('actionID', 'add_category');
    $valid = $form2->validate($vars2);
    if ($valid) {
        // Redirect to the category form.
        $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_categories');
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    break;

case 'list_properties':
    require_once SESHA_BASE . '/lib/Forms/Property.php';
    $vars->set('actionID', 'edit_property');
    $renderer = new Horde_Form_Renderer();
    $form = new PropertyListForm($vars, 'admin.php', 'post');
    $valid = $form->validate($vars);
    if ($valid) {
        // Redirect to the property list form.
        $url = Horde_Util::addParameter('admin.php', 'actionID', 'edit_property');
        $url = Horde_Util::addParameter($url, 'property_id', $vars->get('property_id'));
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    $vars2 = Horde_Variables::getDefaultVariables();
    $vars2->set('actionID', 'add_property');
    $form2 = new PropertyForm($vars2, 'admin.php', 'post');
    $form2->setTitle(_("Add a new property"));
    $valid = $form2->validate($vars2);
    if ($valid) {
        // Redirect to the property form.
        $url = Horde_Util::addParameter('admin.php', 'actionID', 'list_properties');
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
    break;
}

require SESHA_TEMPLATES . '/common-header.inc';
require SESHA_TEMPLATES . '/menu.inc';
echo $tabs->render(strpos($actionID, 'propert') === false ? 'list_categories' : 'list_properties');

// Render forms if they are defined.
if (isset($form)) {
    $form->renderActive($renderer, $vars, Horde::url('admin.php'), 'post');
}
if (isset($form2)) {
    echo '<br />';
    $form2->renderActive($renderer, $vars2, Horde::url('admin.php'), 'post');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
