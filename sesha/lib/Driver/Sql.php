<?php
/**
 * This is the sql implementation of the Sesha Driver.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'database'      The name of the database.
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
class Sesha_Driver_Sql extends Sesha_Driver
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
     * This is the basic constructor for the sql driver.
     *
     * @param array $params  Hash containing the connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_db = $params['db'];
        $this->_mappers = new Horde_Rdo_Factory($this->_db);
    }

    /**
     * This will retrieve all of the stock items from the database, or just a
     * particular category of items.
     *
     * @param integer $category_id  The category ID you want to fetch.
     * @param array $property_ids   The ids of any properties to include in the list.
     *
     * @return array  Array of results on success;
     */
    public function listStock($category_id = null, $property_ids = array())
    {
        if (!$property_ids) {
            $sql = 'SELECT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note FROM sesha_inventory i';
            if ($category_id) {
                $sql .= ', sesha_inventory_categories c WHERE c.category_id = ' .
                    (int)$category_id . ' AND i.stock_id = c.stock_id';
            }
            $values = array();
        } else {
            // More complicated join to include property values
            if ($category_id) {
                $sql = '
SELECT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note, p.property_id AS property_id, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, a.txt_datavalue AS txt_datavalue
  FROM sesha_inventory i
  JOIN sesha_inventory_categories c ON c.category_id = ? AND i.stock_id = c.stock_id
  LEFT JOIN sesha_inventory_properties a ON a.stock_id = i.stock_id AND a.property_id IN (?' . str_repeat(', ?', count($property_ids) - 1) . ')
  LEFT JOIN sesha_properties p ON a.property_id = p.property_id ORDER BY a.stock_id, p.priority DESC';
                $values = array_merge(array($category_id), $property_ids);
            } else {
                $sql = '
SELECT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note, p.property_id AS property_id, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, a.txt_datavalue AS txt_datavalue
  FROM sesha_inventory i
  LEFT JOIN sesha_inventory_properties a ON a.stock_id = i.stock_id AND a.property_id IN (?' . str_repeat(', ?', count($property_ids) - 1) . ')
  LEFT JOIN sesha_properties p ON a.property_id = p.property_id ORDER BY a.stock_id, p.priority DESC';
                $values = $property_ids;
            }
        }

        try {
            $result = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        if ($property_ids) {
            return $this->_normalizeStockProperties($result, $property_ids);
        } else {
            return $result;
        }
    }

    /**
     * This will retrieve all matching items from the database.
     *
     * @param string $what     What to find.
     * @param constant $where  Where to find the information (bitmask).
     * @param array $property_ids   The ids of any properties to include in the list.
     *
     * @return array  Array of results on success
     */
    public function searchStock($what, $where = Sesha::SEARCH_NAME, $property_ids = array())
    {
        if (is_null($what) || is_null($where)) {
            throw new Sesha_Exception("Invalid search parameters");
        }

        // Start the query
        if ($property_ids) {
            $sql = 'SELECT DISTINCT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note, p1.property_id AS property_id, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, a.txt_datavalue AS txt_datavalue
  FROM sesha_inventory i
  LEFT JOIN sesha_inventory_properties a ON a.stock_id = i.stock_id AND a.property_id IN (?' . str_repeat(', ?', count($property_ids) - 1) . ')
  LEFT JOIN sesha_properties p1 ON a.property_id = p1.property_id';
            $values = $property_ids;
        } else {
            $sql = 'SELECT DISTINCT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note FROM sesha_inventory i';
            $values = array();
        }

        // Create where clause
        $what = $this->_db->quote(sprintf('%%%s%%', $what));
        $whereClause = array();
        if ($where & Sesha::SEARCH_ID) {
            $whereClause[] = 'i.stock_id LIKE ' . $what;
        }
        if ($where & Sesha::SEARCH_NAME) {
            $whereClause[] = 'i.stock_name LIKE ' . $what;
        }
        if ($where & Sesha::SEARCH_NOTE) {
            $whereClause[] = 'i.note like ' . $what;
        }
        if ($where & Sesha::SEARCH_PROPERTY) {
            $sql .= ', sesha_inventory_properties p2';
            $whereClause[] = '(p2.txt_datavalue LIKE ' . $what .
                ' AND i.stock_id = p2.stock_id)';
        }

        /*
SELECT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note, p.property_id AS property_id, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, a.txt_datavalue AS txt_datavalue
  FROM sesha_inventory i
  LEFT JOIN sesha_inventory_properties a ON a.stock_id = i.stock_id AND a.property_id IN (?' . str_repeat(', ?', count($property_ids) - 1) . ')
  LEFT JOIN sesha_properties p ON a.property_id = p.property_id ORDER BY a.stock_id, p.priority DESC
        */

        if (count($whereClause)) {
            $sql .= ' WHERE ' . implode(' OR ', $whereClause);
        }
        $sql .= ' ORDER BY i.stock_id';
        if ($property_ids) {
            $sql .= ', p1.priority DESC';
        }

        try {
            $result = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        if ($property_ids) {
            return $this->_normalizeStockProperties($result, $property_ids);
        } else {
            return $result;
        }
    }

    /**
     * This function retrieves a single stock item from the database.
     *
     * @param integer $stock_id  The numeric ID of the stock item to fetch.
     *
     * @return array  a stock item
     * @throws Sesha_Exception
     */
    public function fetch($stock_id)
    {

        // Build the query
        $sql = 'SELECT * FROM sesha_inventory WHERE stock_id = ?';
        $values = array((int)$stock_id);

        // Perform the search
        try {
            return $this->_db->selectOne($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
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

        // Build, log, and issue the query
        $sql = 'DELETE FROM sesha_inventory WHERE stock_id = ?';
        $values = array((int)$stock_id);
        try {
            $result = $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        $this->clearPropertiesForStock($stock_id);
        $this->updateCategoriesForStock($stock_id, array());
        return true;
    }

    /**
     * This will add a new item to the inventory.
     *
     * @param array $stock  A hash of values for the stock item.
     *
     * @return integer  The numeric ID of the newly added item or false.
     * @throws Sesha_Exception
     */
    public function add($stock)
    {
        // Create the queries
        $sql = 'INSERT INTO sesha_inventory(stock_name,note) VALUES(?,?)';

        $values = array($stock['stock_name'], $stock['note']);

        // Perform the queries
        try {
            $result = $this->_db->insert($sql,$values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        return $result;
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
        $sql = 'UPDATE sesha_inventory SET stock_name = ?, note = ? WHERE stock_id = ?';

        $values = array($stock['stock_name'],$stock['note'], $stock_id);
        // Perform the queries
        try { 
            $result = $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        return true;
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
     * @return Horde_Rdo_List  The kist of matching categories
     */
    public function getCategories($stock_id = null, array $category_ids = null)
    {
        $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
        if (is_int($stock_id)) {
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
     * @return integer The number of affected rows
     * @throws Sesha_Exception
     */
    public function updateCategory($info)
    {
        $sql = 'UPDATE sesha_categories' .
               ' SET category = ?, description = ?, priority = ?' .
               ' WHERE category_id = ?';
        $values = array($info['category'], $info['description'], $info['priority'], $info['category_id']);
        try {
            return $this->_db->execute($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

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

        $sql = 'INSERT INTO sesha_categories' .
               ' (category, description, priority)' .
               ' VALUES (?, ?, ?)';
        $values = array($info['category'], $info['description'], $info['priority']);

        try {
            $result = $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
        return $result;
    }

    /**
     * Deletes a category.
     *
     * @param integer $category_id  The numeric ID of the category to delete.
     *
     * @return integer The number of rows deleted
     */
    public function deleteCategory($category_id)
    {
        $sql = 'DELETE FROM sesha_categories WHERE category_id = ?';
        $values = array($category_id);
        try {
            return $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

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
        $sql = 'SELECT * FROM sesha_categories WHERE category = ?';
        $values = array($category);

        $result = $this->_db->selectOne($sql, $values);
        if (count($result)) {
            return true;
        }
        return false;
    }

    /**
     * Updates a property with new attributes.
     *
     * @param array $info Array with updated property values.
     *
     * @return object  The PEAR DB_Result object from the query.
     */
    public function updateProperty($info)
    {
        $sql = 'UPDATE sesha_properties SET property = ?, datatype = ?, parameters = ?, unit = ?, description = ?, priority = ?, WHERE property_id = ?';
        $values = array(
            $info['property'],
            $info['datatype'],
            serialize($info['parameters']),
            $info['unit'],
            $info['description'],
            $info['priority'],
            $info['property_id'],
        );
        try {
            return $this->_db->query($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
    }

    /**
     * Adds a new property to the storage backend.
     *
     * @param array $info Array with new property values.
     *
     * @return object  The PEAR DB_Result from the sql query.
     */
    public function addProperty($info)
    {
        $sql = 'INSERT INTO sesha_properties (property, datatype, parameters, unit, description, priority) VALUES (?, ?, ?, ?, ?, ?)';
        $values = array(
            $info['property'],
            $info['datatype'],
            serialize($info['parameters']),
            $info['unit'],
            $info['description'],
            $info['priority'],
        );

        try {
            return $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
    }

    /**
     * Deletes a property from the storage backend.
     *
     * @param integer $property_id  The numeric ID of the property to delete.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    public function deleteProperty($property_id)
    {
        $sql = 'DELETE FROM sesha_properties WHERE property_id = ?';
        $values = array($property_id);
        try {
            return $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
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
        if (!is_array($categories)) {
            $categories = array($categories);
        }

        $in = '';
        foreach ($categories as $category) {
            $in .= !empty($in) ? ', ' . $category : $category;
        }
        $sql = sprintf('SELECT c.category_id AS category_id, c.category AS category, p.property_id AS property_id, p.property AS property, p.unit AS unit, p.description AS description, p.datatype AS datatype, p.parameters AS parameters FROM sesha_categories c, sesha_properties p, sesha_relations cp WHERE c.category_id = cp.category_id AND cp.property_id = p.property_id AND c.category_id IN (%s) ORDER BY p.priority DESC',
                       empty($in) ? $this->_db->quote($in) : $in);
        try {
            $properties = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        array_walk($properties, array($this, '_unserializeParameters'));

        return $properties;
    }

    /**
     * Updates a category with a set of properties.
     *
     * @param integer   $category_id    The numeric ID of the category to update.
     * @param array     $properties     An array of property ID's to add.
     *
     * @return integer  number of inserted row
     * @throws Sesha_Exception
     */
    public function setPropertiesForCategory($category_id, $properties = array())
    {
        $this->clearPropertiesForCategory($category_id);
        foreach ($properties as $property) {
            $sql = 'INSERT INTO sesha_relations
                (category_id, property_id) VALUES (?, ?)';
            try {
                $result = $this->_db->execute($sql, array($category_id, $property));
            } catch (Horde_Db_Exception $e) {
                throw new Sesha_Exception($e);
            }
        }
        return $result;
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
        $sql = 'DELETE FROM sesha_relations WHERE category_id = ?';
        try {
            return $this->_db->delete($sql, array($category_id));
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
    }

    /**
     * Returns a set of properties for a particular stock ID number.
     *
     * @param integer $stock_id  The numeric ID of the stock to find the
     *                           properties for.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     * @throws Sesha_Exception
     */
    public function getPropertiesForStock($stock_id)
    {
        $sql = 'SELECT p.property_id AS property_id, p.property AS property, p.datatype AS datatype, ' .
            'p.unit AS unit, p.description AS description, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, ' .
            'a.txt_datavalue AS txt_datavalue FROM sesha_properties p, ' .
            'sesha_inventory_properties a WHERE p.property_id = ' .
            'a.property_id AND a.stock_id = ? ORDER BY p.priority DESC';
        try {
            $properties = $this->_db->selectAll($sql, array($stock_id));
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        for ($i = 0; $i < count($properties); $i++) {
            $value = @unserialize($properties[$i]['txt_datavalue']);
            if ($value !== false) {
                $properties[$i]['txt_datavalue'] = $value;
            }
        }

        return $properties;
    }

    /**
     * Removes categories from a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock item to update.
     * @param array $categories  The array of categories to remove.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     * @throws Sesha_Exception
     */
    public function clearPropertiesForStock($stock_id, $categories = array())
    {
        if (!is_array($categories)) {
            $categories = array(0 => array('category_id' => $categories));
        }
        /* Get list of properties for this set of categories. */
        try {
            $properties = $this->getPropertiesForCategories($categories);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        $propertylist = '';
        for ($i = 0;$i < count($properties); $i++) {
            if (!empty($propertylist)) {
                $propertylist .= ', ';
            }
            $propertylist .= $properties[$i]['property_id'];
        }
        $sql = sprintf('DELETE FROM sesha_inventory_properties
                        WHERE stock_id = %d
                        AND property_id IN (%s)',
                            $stock_id,
                            $propertylist);
        try {
            return $this->_db->execute($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
    }

    /**
     * Updates the set of properties for a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock to update.
     * @param array $properties  The hash of properties to update.
     *
     * @return mixed  The DB_Result object on success
     * @throws Sesha_Exception
     */
    public function updatePropertiesForStock($stock_id, $properties = array())
    {
        $result = false;
        foreach ($properties as $property_id => $property_value) {
            // Now clear any existing attribute values for this property_id
            // and stock_id.
            $sql = 'DELETE FROM sesha_inventory_properties ' .
                   'WHERE stock_id = ? AND property_id = ?';

            try {
                $result = $this->_db->execute($sql, array($stock_id, $property_id));
            } catch (Horde_Db_Exception $e) {
                throw new Sesha_Exception($e);
            }
            if (!is_null($result) && !empty($property_value)) {
                $sql = 'INSERT INTO sesha_inventory_properties' .
                       ' (property_id, stock_id, txt_datavalue)' .
                       ' VALUES (?, ?, ?)';
                $values = array($property_id, $stock_id, is_string($property_value) ? $property_value : serialize($property_value));
                try {
                    $result = $this->_db->insert($sql, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Sesha_Exception($e);
                }
            }
        }
        return $result;
    }

    /**
     * Updates the set of categories for a specified stock item.
     *
     * @param integer $stock_id  The numeric stock ID to update.
     * @param array $categories    The array of categories to change.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    public function updateCategoriesForStock($stock_id, $categories = array())
    {
        if (!is_array($categories)) {
            $categories = array($categories);
        }
        /* First clear any categories that might be set for this item. */
        $sql = 'DELETE FROM sesha_inventory_categories ' .
                       'WHERE stock_id = ? ';

        try {
            $result = $this->_db->execute($sql, array($stock_id));
        } catch (Sesha_Exception $e) {
            throw new Sesha_Exception($e);
        }
        foreach ($categories as $category_id) {
            $sql = sprintf('INSERT INTO sesha_inventory_categories ' .
                '(stock_id, category_id) VALUES (%d, %d)',
                $stock_id, $category_id);

            $result = $this->_db->insert($sql);
        }

        return $result;
    }

    /**
     */
    public function _unserializeParameters(&$val, $key)
    {
        $val['parameters'] = @unserialize($val['parameters']);
    }

    public function _normalizeStockProperties($rows, $property_ids)
    {
        $stock = array();
        foreach ($rows as $row) {
            if (!isset($stock[$row['stock_id']])) {
                $stock[$row['stock_id']] = array(
                    'stock_id' => $row['stock_id'],
                    'stock_name' => $row['stock_name'],
                    'note' => $row['note'],
                );
                foreach ($property_ids as $property_id) {
                    $stock[$row['stock_id']]['p' . $property_id] = '';
                }
            }

            $stock[$row['stock_id']]['p' . $row['property_id']] = strlen($row['txt_datavalue']) ? $row['txt_datavalue'] : $row['int_datavalue'];
        }

        return $stock;
    }

    /**
     * Rdo based inventory search
     * @return array List of Stock items
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
                $query->addTest($test);
                break;
                case 'categories':
                    $cm = $this->_mappers->create('Sesha_Entity_CategoryMapper');
                    $categories = is_array($filter['value']) ? $filter['value'] : array($filter['value']);
                    $items = array();
                    foreach ($categories as $category) {
                        if ($category instanceof Insysgui_Entity_Category) {
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
                    $query->addTest(
                        array(
                            'field' => 'stock_id',
                            'test' => $filter['test'] ? $filter['test'] : 'IN',
                            'value' => array_keys($items)
                        )
                    );
                break;
            }
        }
        return iterator_to_array($sm->find($query));
    }
}
