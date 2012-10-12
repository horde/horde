<?php
/**
 * The Sesha_View_List class wraps the list view logic to keep the client pages
 * simple.
 *
 * Available fields:
 * - header: (string) The header text related to the listing table.
 * - selectedCategories: (array) The selected categories.
 * - columnHeaders: (array) The columns structure for the result table.
 * - shownProperties: (array) The list of property objects to use.
 * - shownStock: (array) The stock display matrix
 *
 * Copyright 2012-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Sesha
 * @license  http://www.horde.org/licenses/gpl GPL
 */
class Sesha_View_List extends Sesha_View_Base
{
    public function __construct(array $config)
    {
        if (!is_null($what) && !is_null($where)) {
            $title = _("Search Inventory");
            $this->header = _("Matching Inventory");
        } else {
            $this->header = $category_id
                ? sprintf(_("Available Inventory in %s"),
                          $selectedCategory->category)
                : _("Available Inventory");
        }

        $this->title = _("Inventory List");
        $this->selectedCategories = is_array($config['selectedCategories'])
            ? $config['selectedCategories']
            : array($config['selectedCategories']);
        if (empty($this->selectedCategories[0])) {
            array_shift($this->selectedCategories);
        }
        $this->shownProperties = $this->properties($config['propertyIds']);
        $this->columnHeaders = $this->columnHeaders($config['sortDir'],
                                                    $config['sortBy']);

        $filters = array();
        if (!empty($this->selectedCategories)) {
            $filters[] = array('type' => 'categories',
                               'value' => $this->selectedCategories);
        }
        if (in_array(Sesha::SEARCH_ID, $config['loc'])) {
            $filters[] = array('type' => 'stock_id',
                               'value' => $config['what']);
        }
        if (in_array(Sesha::SEARCH_NAME, $config['loc'])) {
            $filters[] = array('type' => 'stock_name',
                               'value' => $config['what']);
        }
        if (in_array(Sesha::SEARCH_NOTE, $config['loc'])) {
            $filters[] = array('type' => 'note',
                               'value' => $config['what']);
        }
        if (in_array(Sesha::SEARCH_PROPERTY, $config['loc'])) {
            $filters[] = array(
                'type' => 'values',
                'value' => array(array('values' => array($config['what']))));
        }
        $this->shownStock = $this->stock($filters);
        parent::__construct($config);
    }

    /**
     * Retrieves all categories from driver.
     *
     * @return array  List of Sesha_Entity_Category objects.
     */
    public function allCategories()
    {
        return Sesha::listCategories();
    }

    /**
     * Builds column header array out of the list of properties and default
     * attributes.
     */
    protected function columnHeaders($sortDir, $sortBy)
    {
        $prefs_url = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/prefs/', true);
        $sortdirclass = $sortDir ? 'sortup' : 'sortdown';
        $baseurl = Horde::url('list.php');
        $column_headers = array(
            array('id' => 's' . Sesha::SORT_STOCKID,
                'class' => $sortBy == Sesha::SORT_STOCKID ? ' class="' . $sortdirclass . '"' : '',
                'link' => Horde::link($baseurl->copy()->add('sortby', Sesha::SORT_STOCKID), _("Sort by stock ID"), 'sortlink') . _("Stock ID") . '</a>',
                'width' => ' width="5%"'),
            array('id' => 's' . Sesha::SORT_NAME,
                'class' => $sortBy == Sesha::SORT_NAME ? ' class="' . $sortdirclass . '"' : '',
                'link' => Horde::link($baseurl->copy()->add('sortby', Sesha::SORT_NAME), _("Sort by item name"), 'sortlink') . _("Item Name") . '</a>',
                'width' => '')
        );
        foreach ($this->shownProperties as $property) {
            $column_headers[] = array(
                'id' => 'sp' . $property->property_id,
                'class' => $sortBy == 'p' . $property->property_id ? ' class="' . $sortdirclass . '"' : '',
                'link' => Horde::link($baseurl->copy()->add('sortby', 'p' . $property->property_id), sprintf(_("Sort by %s"), htmlspecialchars($property->property)), 'sortlink') . htmlspecialchars($property->property) . '</a>',
                'width' => '',
            );
        }
        $column_headers[] = array(
            'id' => 's' . Sesha::SORT_NOTE,
            'class' => $sortby == Sesha::SORT_NOTE ? ' class="' . $sortdirclass . '"' : '',
            'link' => Horde::link($baseurl->copy()->add('sortby', Sesha::SORT_NOTE), _("Sort by note"), 'sortlink') . _("Note") . '</a>',
            'width' => '',
        );
        return $column_headers;
    }

    /**
     * Returns the list of property objects to display.
     */
    protected function properties($propertyIds = array())
    {
        if (empty($propertyIds)) {
            /* The driver understands an empty filter as "all" but if none are
             * selected, we want none. */
            return array();
        }
        try {
            return $GLOBALS['injector']
                ->getInstance('Sesha_Factory_Driver')
                ->create()
                ->getProperties($propertyIds);
        } catch (Sesha_Exception $e) {
            return array();
        }
    }

    /**
     * Returns the items which match the category or search criteria.
     */
    protected function stock($filters = array())
    {
        $driver = $GLOBALS['injector']
            ->getInstance('Sesha_Factory_Driver')
            ->create();

        // Get the inventory
        $stock = $driver->findStock($filters);
        $isAdminEdit = Sesha::isAdmin(Horde_Perms::EDIT);
        $itemEditImg = Horde::img('edit.png', _("Edit Item"));
        $isAdminDelete = Sesha::isAdmin(Horde_Perms::DELETE);
        $adminDeleteImg = Horde::img('delete.png', _("Delete Item"));
        $stock_url = Horde::url('stock.php');

        foreach ($stock as $item) {
            $url = $stock_url->add('stock_id', $item->stock_id);
            $columns = array();

            // icons
            $icons = '';
            if ($isAdminEdit) {
                $icons .= $url->copy()
                    ->add('actionId', 'update_stock')
                    ->link(array('title' => _("Edit Item")))
                    . $itemEditImg . '</a>';
            }
            if ($isAdminDelete) {
                $icons .= $url->copy()
                    ->add('actionId', 'remove_stock')
                    ->link(array('title' => _("Delete Item")))
                    . $adminDeleteImg . '</a>';
            }
            $columns[] = array('class' => ' class="nowrap"',
                               'column' => $icons);

            // stock_id
            $columns[] = array(
                'class' => '',
                'column' => $url->copy()
                    ->add('actionId', 'view_stock')
                    ->link(array('title' => _("View Item")))
                    . htmlspecialchars($item->stock_id) . '</a>');

            // name
            $columns[] = array(
                'class' => '',
                'column' => $url->copy()
                    ->add('actionId', 'view_stock')
                    ->link(array('title' => _("View Item")))
                    . htmlspecialchars($item->stock_name) . '</a>');

            // properties
            foreach ($this->shownProperties as $property) {
                $value = $item->getValue($property);
                $columns[] = array(
                    'class' => '',
                    'column' => $value
                        ? htmlspecialchars($value->getDataValue())
                        : '&nbsp;');
            }

            // note
            $columns[] = array(
                'class' => '',
                'column' => $item->note
                    ? htmlspecialchars($item->note)
                    : '&nbsp;');

            $items[] = array('columns' => $columns);
        }

        return $items;
    }
}
