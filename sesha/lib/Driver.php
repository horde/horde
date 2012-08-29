<?php
/**
 * This is the base Driver class for the Sesha application.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Sesha
 */
abstract class Sesha_Driver
{
    protected $_params;

    /**
     * Variable holding the items in the inventory.
     *
     * @var array
     */
    protected $_stock;

    public function __construct($params = array())
    {
        $this->_params = $params;
    }
    /**
     * This function retrieves a single stock item from the backend.
     *
     * @param integer $stock_id  The numeric ID of the stock item to fetch.
     *
     * @return Sesha_Entity_Stock  a stock item
     * @throws Sesha_Exception
     */
    abstract public function fetch($stock_id);

    /**
     * Removes a stock entry from the backend. Also removes all related
     * category and property information.
     *
     * @param integer $stock_id  The ID of the item to delete.
     *
     * @return boolean  True on success
     * @throws Sesha_Exception
     *
     */
    abstract public function delete($stock_id);

    /**
     * This will add a new item to the inventory.
     *
     * @param array $stock  A hash of values for the stock item.
     *
     * @return Sesha_Entity_Stock  The newly added item or false.
     * @throws Sesha_Exception
     */
    abstract public function add($stock);

    /**
     * This function will modify a pre-existing stock entry with new values.
     *
     * @param array $stock  The hash of values for the inventory item.
     *
     * @return boolean  True on success.
     * @throws Sesha_Exception
     */
    abstract public function modify($stock_id, $stock);

    /**
     * This will return the category found matching a specific id.
     *
     * @param integer|array $category_id  The integer ID or key => value hash of the category to find.
     *
     * @return Sesha_Entity_Category  The category on success
     */
    abstract public function getCategory($category_id);

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
    abstract public function getCategories($stock_id = null, array $category_ids = null);

    /**
     * This will find all the available properties matching a specified IDs.
     *
     * @param array $property_ids  The numeric ID of properties to find.
     *                              Matches all properties when null.
     *
     * @return array  matching properties on success
     * @throws Sesha_Exception
     */
    abstract public function getProperties($property_ids = array());

    /**
     * Finds the first matching property for a specified property ID.
     *
     * @param integer $property_id  The numeric ID of properties to find.
     *
     * @return mixed  The specified property on success
     * @throws Sesha_Exception
     */
    abstract public function getProperty($property_id);

    /**
     * Updates the attributes stored by a category.
     *
     * @param array $info  Updated category attributes.
     *
     * @return integer Number of objects updated.
     * @throws Sesha_Exception
     */
    abstract public function updateCategory($info);

    /**
     * Adds a new category for classifying inventory.
     *
     * @param array $info  The new category's attributes.
     *
     * @return integer  The ID of the new of the category on success
     * @throws Sesha_Exception
     */
    abstract public function addCategory($info);

    /**
     * Deletes a category.
     *
     * @param integer $category_id  The numeric ID of the category to delete. Also accepts Sesha_Entity_Category
     *
     * @return integer The number of categories deleted
     */
    abstract public function deleteCategory($category_id);

    /**
     * Determines if a category exists in the storage backend.
     *
     * @param string $category  The string representation of the category to
     *                          find.
     *
     * @return boolean  True on success; false otherwise.
     */
    abstract public function categoryExists($category);

    /**
     * Updates a property with new attributes.
     *
     * @param array $info Array with updated property values.
     *
     * @return Sesha_Inventory_Property  The changed Sesha_Inventory_Property object.
     */
    abstract public function updateProperty(array $info);

    /**
     * Adds a new property to the storage backend.
     *
     * @param array $info Array with new property values.
     *
     * @return Sesha_Entity_Property
     */
    abstract public function addProperty($info);

    /**
     * Deletes a property from the storage backend.
     *
     * @param integer $property_id  The numeric ID of the property to delete. Also accepts a Sesha_Inventory_Property object
     *
     * @return integer Number of objects deleted.
     */
    abstract public function deleteProperty($property_id);

    /**
     * This will return a set of properties for a set of specified categories.
     *
     * @param array $categories  The set of categories to fetch properties.
     *
     * @return mixed  An array of properties on success
     * @throws Sesha_Exception
     */
    abstract public function getPropertiesForCategories($categories = array());

    /**
     * Updates a category with a set of properties.
     *
     * @param integer   $category_id    The numeric ID of the category to update.
     * @param array     $properties     An array of property ID's to add.
     *
     * @throws Sesha_Exception
     */
    abstract public function setPropertiesForCategory($category_id, $properties = array());

    /**
     * Removes all properties for a specified category.
     *
     * @param integer $category_id  The numeric ID of the category to update.
     *
     * @return integer The number of deleted properties
     * @throws Sesha_Exception
     */
    abstract public function clearPropertiesForCategory($category_id);

    /**
     * Returns a set of properties for a particular stock ID number.
     *
     * @param integer $stock_id  The numeric ID of the stock to find the
     *                           properties for.
     *
     * @return array of Sesha_Inventory_Property objects
     * @throws Sesha_Exception
     */
    abstract public function getPropertiesForStock($stock_id);

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
    abstract public function getValuesForStock($stock_id);

    /**
     * Removes categories from a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock item to update.
     * @param array $categories  The array of categories to remove.
     *
     * @return integer  the number of categories removed
     * @throws Sesha_Exception
     */
    abstract public function clearPropertiesForStock($stock_id, $categories = array());

    /**
     * Updates the set of properties for a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock to update.
     * @param array $properties  The hash of properties to update.
     *
     * @throws Sesha_Exception
     */
    abstract public function updatePropertiesForStock($stock_id, $properties = array());

    /**
     * Updates the set of categories for a specified stock item.
     *
     * @param integer $stock_id  The numeric stock ID to update.
     * @param array $categories  The array of categories to change.
     *
     */
    abstract public function updateCategoriesForStock($stock_id, $categories = array());


    /**
     * Inventory search
     * @param array filters  a list of filter hashes, each having keys
     *                  string type ('note', 'stock_name', 'stock_id', 'categories', 'properties')
     *                  string test
     *                  mixed  value (string fore note, stock_name)
     * @return array  List of Stock items
     */
    abstract public function findStock($filters = array());

}
