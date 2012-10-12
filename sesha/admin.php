<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
$vars = Horde_Variables::getDefaultVariables();
$category_id = $vars->get('category_id');
$property_id = $vars->get('property_id');
$actionID = $vars->get('actionID');

// Admin actions.
$baseUrl = $registry->get('webroot', 'sesha');
$adminurl = Horde::url('admin.php', true);
$tabs = new Horde_Core_Ui_Tabs('actionID', $vars);
$tabs->addTab(_("Manage Categories"), $adminurl, 'list_categories');
$tabs->addTab(_("Manage Properties"), $adminurl, 'list_properties');

if (!Sesha::isAdmin(Horde_Perms::DELETE)) {
    $notification->push(_("You are no administrator"), 'horde.warning');
    header('Location: ' . Horde::url('list.php', true));
    exit;
}

/* Run through the action handlers. */
switch ($actionID) {

case 'add_category':
    $url = Horde::url('admin.php')->add('actionID', 'list_categories');
    $title = _("Add a category");
    $vars->set('actionID', $actionID);
    $renderer = new Horde_Form_Renderer();
    $form = new Sesha_Form_Category($vars);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        // Save category details.
        try {
            $category_id = $sesha_driver->addCategory($info);
        } catch (Sesha_Exception $e) {
            $notification->push(_("Could not add new category.") . $e->getMessage(), 'horde.warning');
            header('Location: ' . Horde::url($baseUrl . $url, true));
            exit;
        }
        try {
            $result = $sesha_driver->setPropertiesForCategory($category_id,
                                                        $vars->get('properties'));
        } catch (Sesha_Exception $e) {
            $notification->push(_("Could not add properties to new category: %s, %s") . $category_id->getMessage(), $result->getMessage(), 'horde.warning');
            header('Location: ' . Horde::url($baseUrl . $url, true));
            exit;
        }
        $notification->push(_("New category added successfully."), 'horde.success');
        header('Location: ' . Horde::url($url, true));
        exit;
    }
    break;

case 'edit_category':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_categories');
    try {
        $category = $sesha_driver->getCategory($category_id);
    } catch (Sesha_Exception $e) {
        $notification->push(_('Could not retrieve category') . $e->getMessage, 'horde.error');
        header('Location: ' . Horde::url($baseUrl . $url, true));
        exit;
    }
    $renderer = new Horde_Form_Renderer();
    if ($vars->get('submitbutton') == _("Edit Category") ||
        $vars->get('submitbutton') == _("Save Category")) {
        $title = sprintf(_("Modifying %s"), $category['category']);
        $vars->set('actionID', $actionID);
        $form = new Sesha_Form_Category($vars);
        $form->setTitle($title);
        if ($form->validate($vars)) {
            // Save category details.
            $form->getInfo($vars, $info);
            try {
                $result = $sesha_driver->updateCategory($info);
            } catch (Sesha_Exception $e) {
                $notification->push(_("Could not update category details."), 'horde.warning');
                header('Location: ' . Horde::url($url, true));
                exit;
            }
            try {
                $result = $sesha_driver->setPropertiesForCategory($vars->get('category_id'), $vars->get('properties'));
            } catch (Sesha_Exception $e) {
                $notification->push(_("Could not update properties for this category."), 'horde.warning');
                header('Location: ' . Horde::url($url, true));
                exit;
            }
            $notification->push(_("Updated category successfully."), 'horde.success');
            header('Location: ' . Horde::url($url, true));
            exit;
        } else {
            foreach ($category as $key => $val) {
                $vars->set($key, $val);
            }
        }
    } elseif ($vars->get('submitbutton') == _("Delete Category")) {
        $title = sprintf(_("Delete Category \"%s\""), $category['category']);
        $vars->set('actionID', 'delete_category');
        $form = new Sesha_Form_CategoryDelete($vars);
        $form->setTitle($title);
    }
    break;

case 'delete_category':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_categories');
    if ($vars->get('confirm') == 'yes') {
        try {
            $sesha_driver->deleteCategory($category_id);
        } catch (Sesha_Exception $e) {
            $notification->push(_("There was an error removing the category."), 'horde.warning');
            header('Location: ' . Horde::url($url, true));
            exit;
        }
        $notification->push(_("The category was deleted."), 'horde.success');
    } else {
        $notification->push(_("The category was not deleted."), 'horde.warning');
    }
    header('Location: ' . Horde::url($url, true));
    exit;

case 'edit_property':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_properties');
    try {
        $property = $sesha_driver->getProperty($property_id);
    } catch (Sesha_Exception $e) {
        $notification->push(_('Property not found'), 'horde.warning');
        header('Location: ' . Horde::url($url, true));
        exit;
    }
    $renderer = new Horde_Form_Renderer();
    if ($vars->get('submitbutton') == _("Delete Property")) {
        $title = sprintf(_("Delete Property \"%s\""), $property['property']);
        $vars->set('actionID', 'delete_property');
        $form = new Sesha_Form_PropertyDelete($vars);
        $form->setTitle($title);
    } else {
        $title = sprintf(_("Modifying property \"%s\""), $property['property']);
        $vars->set('actionID', $actionID);
        $form = new Sesha_Form_Property($vars);
        $form->setTitle($title);
        if ($form->validate($vars)) {
            // Save property details.
            $form->getInfo($vars, $info);
            try {
                $result = $sesha_driver->updateProperty($info);
            } catch (Sesha_Exception $e) {
                $notification->push(_("Could not update property details."), 'horde.warning');
                header('Location: ' . Horde::url($url, true));
                exit;
            }
            $notification->push(_("Updated property successfully."), 'horde.success');
            header('Location: ' . Horde::url($url, true));
            exit;
        } elseif ($vars->get('datatype') == $vars->get('__old_datatype')) {
            foreach ($property as $key => $val) {
                $vars->set($key, $val);
            }
        }
    }

    break;

case 'delete_property':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_properties');
    if ($vars->get('confirm') == 'yes') {
        try {
            $sesha_driver->deleteProperty($property_id);
        } catch (Sesha_Exception $e) {
            $notification->push(_("There was an error removing the property."), 'horde.warning');
            header('Location: ' . Horde::url($url, true));
            exit;
        }
            $notification->push(_("The property was deleted."), 'horde.success');
    } else {
        $notification->push(_("The property was not deleted."), 'horde.warning');
    }
    header('Location: ' . Horde::url($url, true));
    exit;

case 'add_property':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_properties');
    $title = _("Add a property");
    $vars->set('actionID', $actionID);
    $renderer = new Horde_Form_Renderer();
    $form = new Sesha_Form_Property($vars);
    $form->setTitle(_("Add a new property"));
    if ($form->validate($vars)) {
        // Save property details.
        $form->getInfo($vars, $info);
        try {
            $property_id = $sesha_driver->addProperty($info);
        } catch (Sesha_Exception $e) {
            $notification->push(_("Could not add property.") . $property_id->getMessage(), 'horde.warning');
            header('Location: ' . Horde::url($url, true));
            exit;
        }
        $notification->push(_("New property added successfully."), 'horde.success');
        header('Location: ' . Horde::url($url, true));
        exit;
    }
    break;

default:
case 'list_categories':
    $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'edit_category');
    $vars->set('actionID', 'edit_category');
    $renderer = new Horde_Form_Renderer();
    $form = new Sesha_Form_CategoryList($vars, 'admin.php', 'post');
    $valid = $form->validate($vars);
    if ($valid) {
        // Redirect to the category list form.
        $url = Horde::url($url, true)->add('category_id', $vars->get('category_id'));
        header('Location: ' . $url);
        exit;
    }
    $vars2 = Horde_Variables::getDefaultVariables();
    $form2 = new Sesha_Form_Category($vars2, 'admin.php', 'post');
    $form2->setTitle(_("Add a new category"));
    $vars2->set('actionID', 'add_category');
    $valid = $form2->validate($vars2);
    if ($valid) {
        // Redirect to the category form.
        $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'list_categories');
        header('Location: ' . Horde::url($url, true));
        exit;
    }
    break;

case 'list_properties':
    $vars->set('actionID', 'edit_property');
    $renderer = new Horde_Form_Renderer();
    $form = new Sesha_Form_PropertyList($vars, 'admin.php', 'post');
    $valid = $form->validate($vars);
    if ($valid) {
        // Redirect to the property list form.
        $url = Horde::url($baseUrl . '/admin.php')->add('actionID', 'edit_property')->add('property_id', $vars->get('property_id'));
        header('Location: ' . Horde::url($url, true));
        exit;
    }
    $vars2 = Horde_Variables::getDefaultVariables();
    $vars2->set('actionID', 'add_property');
    $form2 = new Sesha_Form_Property($vars2, 'admin.php', 'post');
    $form2->setTitle(_("Add a new property"));
    $valid = $form2->validate($vars2);
    if ($valid) {
        // Redirect to the property form.
        $url = Horde::url($baseUrl . '/admin.php', true)->add('actionID', 'list_properties');
        header('Location: ' . $url);
        exit;
    }
    break;
}

$page_output->header(array(
    'title' => $title
));
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

$page_output->footer();
