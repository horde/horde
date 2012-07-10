<?php
/**
 * This is the Rdo ORM implementation of the Sesha Driver.
 *
 * Required values for $params:<pre>
 *      'db'       The Horde_Db adapter
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 * Based on the original Sql driver
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Sesha
 */
class Sesha_Driver_Rdo extends Sesha_Driver
{
    /**
     * Handle for the database connection.
     * @var DB
     * @access protected
     */
    protected $_db;

    /**
     * The mapper factory
     * @var Horde_Rdo_Factory
     * @access protected
     */
    protected $_mappers;
    /**
     * This is the basic constructor for the Rdo driver.
     *
     * @param array $params  Hash containing the connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_db = $params['db'];
        $this->_mappers = new Horde_Rdo_Factory($this->_db);
    }

    /**
     * This function retrieves a single stock item from the database.
     *
     * @param integer $stock_id  The numeric ID of the stock item to fetch.
     *
     * @return Sesha_Entity_Stock  a stock item
     * @throws Sesha_Exception
     */
    public function fetch($stock_id)
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        return $sm->findOne($stock_id);
    }

    /**
     * Removes a stock entry from the database. Also removes all related
     * category and property information.
     *
     * @param integer $stock_id  The ID of the item to delete.
     *
     * @return boolean  True on success
     * @throws Sesha_Exception
     *
     */
    public function delete($stock_id)
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        return $sm->delete($stock_id);
    }

    /**
     * This will add a new item to the inventory.
     *
     * @param array $stock  A hash of values for the stock item.
     *
     * @return Sesha_Entity_Stock  The newly added item or false.
     * @throws Sesha_Exception
     */
    public function add($stock)
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        return $sm->create($stock);
    }

    /**
     * This function will modify a pre-existing stock entry with new values.
     *
     * @param array $stock  The hash of values for the inventory item.
     *
     * @return boolean  True on success.
     * @throws Sesha_Exception
     */
    public function modify($stock_id, $stock)
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        return $sm->update($stock_id, $stock);
    }

    /**
     * This will return the category found matching a specific id.
     *
     * @param integer|array $category_id  The integer ID or key => value hash of the category to find.
     *
     * @return Sesha_Entity_Category  The category on success
     */
    public function getCategory($category_id)
    {
        return $this->_mappers->create('Sesha_Entity_CategoryMapper')->findOne($category_id);
    }

    /**
     * This function returns all the categories matching an id or category list.
     *
     * @param integer $stock_id      The stock ID of categories to fetch.
     *                               Overrides category_ids
     * @param integer $category_ids  The numeric IDs of the categories to find.
     *                               If both $stock_id and $category_ids are null,
     *                               all categories are returned
     * @return array  The list of matching categories
     */
    public function getCategories($stock_id = null, array $category_ids = null)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if ((int)$stock_id > 0) {
            $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
            $stock = $sm->findOne($stock_id);
            return $stock->categories;
        } elseif (is_int($category_ids)) {
            return $cm->find($category_ids);
        }
        elseif (is_array($category_ids)) {
            $query = new Horde_Rdo_Query($cm);
            $query->addTest('category_id', 'IN', $category_ids);
            return $cm->find($query);
        } else {
            return iterator_to_array($cm->find(), true);
        }
    }

    /**
     * This will find all the available properties matching a specified IDs.
     *
     * @param array $property_ids  The numeric ID of properties to find.
     *                              Matches all properties when null.
     *
     * @return array  matching properties on success
     * @throws Sesha_Exception
     */
    public function getProperties($property_ids = array())
    {
        $pm = $this->_mappers->create('Sesha_Entity_PropertyMapper');
        if (empty($property_ids)) {
            return iterator_to_array($pm->find());
        }
        $query = new Horde_Rdo_Query($pm);
        $query->addTest('property_id', 'IN', $property_ids);
        return iterator_to_array($pm->find($query));
    }

    /**
     * Finds the first matching property for a specified property ID.
     *
     * @param integer $property_id  The numeric ID of properties to find.
     *
     * @return mixed  The specified property on success
     * @throws Sesha_Exception
     */
    public function getProperty($property_id)
    {
        $result = $this->getProperties(array($property_id));
        return array_shift($result);
    }

    /**
     * Updates the attributes stored by a category.
     *
     * @param array $info  Updated category attributes.
     *
     * @return integer Number of objects updated.
     * @throws Sesha_Exception
     */
    public function updateCategory($info)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        return $cm->update($info['category_id'], $info);
    }

    /**
     * Adds a new category for classifying inventory.
     *
     * @param array $info  The new category's attributes.
     *
     * @return integer  The ID of the new of the category on success
     * @throws Sesha_Exception
     */
    public function addCategory($info)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        return $cm->create($info);
    }

    /**
     * Deletes a category.
     *
     * @param integer $category_id  The numeric ID of the category to delete. Also accepts Sesha_Entity_Category
     *
     * @return integer The number of categories deleted
     */
    public function deleteCategory($category_id)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if ($category_id instanceof Sesha_Inventory_Category) {
            $category = $category_id;
            $category_id = $category->category_id;
        } else {
            $category = $cm->findOne($category_id);
        }
        if (empty($category)) throw new Sesha_Exception(sprintf(_('The category %d could not be found', $category_id)));
        return $category->delete();
    }

    /**
     * Determines if a category exists in the storage backend.
     *
     * @param string $category  The string representation of the category to
     *                          find.
     *
     * @return boolean  True on success; false otherwise.
     */
    public function categoryExists($category)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if ($category instanceof Sesha_Inventory_Category) {
            $category = $category->category;
        }
        return (boolean) $cm->findOne(array('category' => $category));
    }

    /**
     * Updates a property with new attributes.
     *
     * @param array $info Array with updated property values.
     *
     * @return Sesha_Inventory_Property  The changed Sesha_Inventory_Property object.
     */
    public function updateProperty(array $info)
    {
        $pm = $this->_mappers->create('Sesha_Entity_PropertyMapper');
        $property = $pm->findOne($info['property_id']);
        if (empty($property)) throw new Sesha_Exception(sprintf(_('The property %d could not be loaded', $info['property_id'])));
        $property->property = $info['property'];
        $property->datatype = $info['datatype'];
        $property->parameters = $info['parameters'];
        $property->unit = $info['unit'];
        $property->description = $info['description'];
        $property->priority = $info['priority'];
        $property->save();
        return $property;
    }

    /**
     * Adds a new property to the storage backend.
     *
     * @param array $info Array with new property values.
     *
     * @return Sesha_Entity_Property  
     */
    public function addProperty($info)
    {
        $pm = $this->_mappers->create('Sesha_Entity_PropertyMapper');
        $property = $pm->create($info);
        return $property;
    }

    /**
     * Deletes a property from the storage backend.
     *
     * @param integer $property_id  The numeric ID of the property to delete. Also accepts a Sesha_Inventory_Property object
     *
     * @return integer Number of objects deleted.
     */
    public function deleteProperty($property_id)
    {
        $pm = $this->_mappers->create('Sesha_Entity_PropertyMapper');
        if ($property_id instanceof Sesha_Inventory_Property) {
            $property = $property_id;
            $property_id = $property->property_id;
        } else {
            $property = $pm->findOne($property_id);
        }
        if (empty($property)) throw new Sesha_Exception(sprintf(_('The property %d could not be found', $property_id)));
        return $property->delete();
    }

    /**
     * This will return a set of properties for a set of specified categories.
     *
     * @param array $categories  The set of categories to fetch properties.
     *
     * @return mixed  An array of properties on success
     * @throws Sesha_Exception
     */
    public function getPropertiesForCategories($categories = array())
    {
        $properties = array();
        foreach ($categories as $category) {
            if (!($category instanceof Sesha_Entity_Category)) {
                $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
                $category = $cm->findOne($category);
            }
            foreach ($category->properties as $property) {
                $properties[$property->property_id] = $property;
            }
        }
        return $properties;
    }

    /**
     * Updates a category with a set of properties.
     *
     * @param integer   $category_id    The numeric ID of the category to update.
     * @param array     $properties     An array of property ID's to add.
     *
     * @throws Sesha_Exception
     */
    public function setPropertiesForCategory($category_id, $properties = array())
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if ($category_id instanceof Sesha_Entity_Category) {
            $category = $category_id;
        } else {
            $category = $cm->findOne($category_id);
        }
        $pm = $this->_mappers->create('Sesha_Entity_PropertyMapper');
        $this->clearPropertiesForCategory($category);
        foreach ($properties as $property) {
            if (!($property instanceof Sesha_Entity_Property)) {
                $property = $pm->findOne($property);
                $category->addRelation('properties', $property);
            }
        }
    }

    /**
     * Removes all properties for a specified category.
     *
     * @param integer $category_id  The numeric ID of the category to update.
     *
     * @return integer The number of deleted properties
     * @throws Sesha_Exception
     */
    public function clearPropertiesForCategory($category_id)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if ($category_id instanceof Sesha_Entity_Category) {
            $category = $category_id;
        } else {
            $category = $cm->findOne($category_id);
        }
        return $category->removeRelation('properties');
    }

    /**
     * Returns a set of properties for a particular stock ID number.
     *
     * @param integer $stock_id  The numeric ID of the stock to find the
     *                           properties for.
     *
     * @return array of Sesha_Inventory_Property objects
     * @throws Sesha_Exception
     */
    public function getPropertiesForStock($stock_id)
    {
        if (($stock_id instanceof Sesha_Entity_Stock)) {
            $stock = $stock_id;
            $stock_id = $stock->stock_id;
        } else {
            $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
            $stock = $sm->findOne($stock_id);
        }
        return $this->getPropertiesForCategories($stock->categories);
    }

    /**
     * Returns a set of Value Objects for a particular stock ID number.
     *
     * @param integer $stock_id  The numeric ID of the stock to find the
     *                           properties for.
     *                           You can also pass a Sesha_Entity_Stock item
     *
     * @return array  the list of Sesha_Entity_Value objects
     * @throws Sesha_Exception
     */
    public function getValuesForStock($stock_id)
    {
        if (($stock_id instanceof Sesha_Entity_Stock)) {
            $stock = $stock_id;
            $stock_id = $stock->stock_id;
        } else {
            $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
            $stock = $sm->findOne($stock_id);
        }

        return iterator_to_array($stock->values);
    }


    /**
     * Removes categories from a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock item to update.
     * @param array $categories  The array of categories to remove.
     *
     * @return integer  the number of categories removed
     * @throws Sesha_Exception
     */
    public function clearPropertiesForStock($stock_id, $categories = array())
    {
        if ($stock_id instanceof Sesha_Entity_Stock) {
            $stock_id = $stock_id->stock_id;
        }

        if (!is_array($categories)) {
            $categories = array(0 => array('category_id' => $categories));
        }
        /* Get list of properties for this set of categories. */
        try {
            $properties = $this->getPropertiesForCategories($categories);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
        $vm = $this->_mappers->create('Sesha_Entity_ValueMapper');
        $query = Horde_Rdo_Query::create(array('stock_id' => $stock_id), $vm);
        $query->addTest(array(
                'field' => 'property_id',
                'test' => 'IN',
                'value' => array_keys($properties)
            )
        );
        $count = 0;
        foreach ($vm->find($query) as $value) {
            $value->delete();
            $count++;
        }
        return $count;
    }

    /**
     * Updates the set of properties for a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock to update.
     * @param array $properties  The hash of properties to update.
     *
     * @throws Sesha_Exception
     */
    public function updatePropertiesForStock($stock_id, $properties = array())
    {
        if ($stock_id instanceof Sesha_Entity_Stock) {
            $stock_id = $stock_id->stock_id;
        }
        $vm = $this->_mappers->create('Sesha_Entity_ValueMapper');
        foreach ($properties as $property_id => $property_value) {
            $value = $vm->findOne(array('stock_id' => $stock_id, 'property_id' => $property_id));
            if (!$value) {
                $value = $vm->create(array('stock_id' => $stock_id, 'property_id' => $property_id));
            }
            $value->setDataValue($property_value);
            $value->save();
        }
    }

    /**
     * Updates the set of categories for a specified stock item.
     *
     * @param integer $stock_id  The numeric stock ID to update.
     * @param array $categories  The array of categories to change.
     *
     */
    public function updateCategoriesForStock($stock_id, $categories = array())
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if (!is_array($categories)) {
            $categories = array($categories);
        }
        if (($stock_id instanceof Sesha_Entity_Stock)) {
            $stock = $stock_id;
            $stock_id = $stock->stock_id;
        } else {
            $stock = $sm->findOne($stock_id);
        }
        /* First clear any categories that might be set for this item. */
        $stock->removeRelation('categories');
        foreach ($categories as $category) {
            if (!($category instanceof Sesha_Entity_Category)) {
                $category = $cm->findOne($category);
            }
            $stock->addRelation('categories', $category);
        }
    }

    /**
     * Inventory search
     * @param array filters  a list of filter hashes, each having keys
     *                  string type ('note', 'stock_name', 'stock_id', 'categories', 'values')
     *                  string test
     *                  mixed  value (string for note, stock_name)
     *                  For the 'values' structure, value, value is a map of [values] and optional [property]}
     * @return array  List of Stock items
     */
    public function findStock($filters = array())
    {
        $sm = $this->_mappers->create('Sesha_Entity_StockMapper');
        if (empty($filters)) {
            return iterator_to_array($sm->find());
        }
        $query = new Horde_Rdo_Query($sm);
        foreach ($filters as $filter) {
            switch ($filter['type']) {
                case 'note':
                case 'stock_name':
                case 'stock_id':
                $test = array(
                            'field' => $filter['type'],
                            'test' => $filter['test'] ? $filter['test'] : 'IN',
                            'value' => is_array($filter['value']) ? $filter['value'] : array($filter['value'])
                        );
                $query->addTest($test['field'], $test['test'], $test['value']);
                break;
                case 'categories':
                    $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
                    $categories = is_array($filter['value']) ? $filter['value'] : array($filter['value']);
                    $items = array();
                    foreach ($categories as $category) {
                        if ($category instanceof Sesha_Entity_Category) {
                            $category_id = $category->category_id;
                        } else {
                            $category_id = $category;
                            $category = $cm->findOne($category_id);
                        }
                        foreach ($category->stock as $item) {
                            /* prevent duplicates when an item has several categories */
                            $items[$item->stock_id] = $item;
                        }
                    }
                    if (count($filters == 1)) {
                        return $items;
                    }
                    $query->addTest('stock_id',$filter['test'] ? $filter['test'] : 'IN', array_keys($items));
                break;
                case 'values':
                    $vm = $this->_mappers->create('Sesha_Entity_ValueMapper');
                    $items = array();
                    foreach ($filter['value'] as $propTest) {
                        $values = is_array($propTest['values']) ? $propTest['values'] : array($propTest['values']);
                        $valueQuery = new Horde_Rdo_Query($vm);
                        if ($propTest['property']) {
                            $valueQuery->addTest('property_id', '=', $propTest['property']);
                        }
                        $valueQuery->addTest('txt_datavalue', 'IN', $values);
                        foreach ($vm->find($valueQuery) as $value) {
                            $items[$value->stock_id] = $value->stock;
                        }
                    }
                    if (count($filters == 1)) {
                        return $items;
                    }
                    $query->addTest('stock_id',$filter['test'] ? $filter['test'] : 'IN', array_keys($items));
                break;
            }
        }
        return iterator_to_array($sm->find($query));
    }
}
