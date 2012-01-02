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
class CategoryForm extends Horde_Form
{
    function CategoryForm(&$vars)
    {
        parent::Horde_Form($vars);

        $this->appendButtons(_("Save Category"));

        $category_id = $vars->get('category_id');

        $priorities = array();
        for ($i = 0; $i < 100; $i++) {
            $priorities[] = $i;
        }

        $allproperties = $GLOBALS['backend']->getProperties();
        $a = array();
        foreach ($allproperties as $property_id => $p) {
            $a[$property_id] = $p['property'];
        }
        if (!empty($category_id)) {
            $properties = $GLOBALS['backend']->getPropertiesForCategories($category_id);
            $current = array();
            if (!is_a($properties, 'PEAR_Error')) {
                foreach ($properties as $s) {
                    $current[$s['property_id']] = $s['property'];
                }
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
            $a = _("No properties are currently configured. Use the 'Manage Properties' tab (above) to add some.");
        } else {
            $fieldtype = 'multienum';
        }
        $mp = &$this->addVariable(_("Properties"), 'properties', $fieldtype, true, false, null, array($a));
        if (!empty($current)) {
            $mp->setDefault(array_keys($current));
        }

        require_once 'Horde/Form/Action.php';
        $action = Horde_Form_Action::factory('submit');
    }

}

class CategoryListForm extends Horde_Form {

    function CategoryListForm(&$vars)
    {
        parent::Horde_Form($vars);
        $this->setButtons(array(_("Edit Category"),
                                _("Delete Category")));
        $categories = $GLOBALS['backend']->getCategories();
        $params = array();
        foreach ($categories as $category) {
            $params[$category['category_id']] = $category['category'];
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

class CategoryDeleteForm extends Horde_Form {

    function CategoryDeleteForm(&$vars)
    {
        parent::Horde_Form($vars);

        $this->appendButtons(_("Delete Category"));
        $params = array('yes' => _("Yes"),
                        'no' => _("No"));
        $desc = _("Really delete this category?");

        $this->addHidden('', 'actionID', 'text', false, false, null, array('delete_category'));
        $this->addHidden('', 'category_id', 'text', false, false, null);
        $this->addVariable(_("Confirm"), 'confirm', 'enum', true, false, $desc, array($params));
    }

}
