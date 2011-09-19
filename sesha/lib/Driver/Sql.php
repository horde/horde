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
 * Copyright 2003-2011 Horde LLC (http://www.horde.org/)
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
     * @access private
     */
    protected $_db;

    /**
     * Flag for the SQL server connection.
     * @var boolean
     * @access private
     */
    protected $_connected;

    /**
     * This is the basic constructor for the sql driver.
     *
     * @param array $params  Hash containing the connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_db = $params['db'];
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
    public function searchStock($what, $where = SESHA_SEARCH_NAME, $property_ids = array())
    {
        if (is_null($what) || is_null($where)) {
            throw new Sesha_Exception("Invalid search parameters");
        }


        // Start the query
        if ($property_ids) {
            $sql = 'SELECT DISTINCT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note, p.property_id AS property_id, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, a.txt_datavalue AS txt_datavalue
  FROM sesha_inventory i
  LEFT JOIN sesha_inventory_properties a ON a.stock_id = i.stock_id AND a.property_id IN (?' . str_repeat(', ?', count($property_ids) - 1) . ')
  LEFT JOIN sesha_properties p ON a.property_id = p.property_id';
            $values = $property_ids;
        } else {
            $sql = 'SELECT DISTINCT i.stock_id AS stock_id, i.stock_name AS stock_name, i.note AS note FROM sesha_inventory i';
            $values = array();
        }

        // Create where clause
        $what = $this->_db->quote(sprintf('%%%s%%', $what));
        $whereClause = array();
        if ($where & SESHA_SEARCH_ID) {
            $whereClause[] = 'i.stock_id LIKE ' . $what;
        }
        if ($where & SESHA_SEARCH_NAME) {
            $whereClause[] = 'i.stock_name LIKE ' . $what;
        }
        if ($where & SESHA_SEARCH_NOTE) {
            $whereClause[] = 'i.note like ' . $what;
        }
        if ($where & SESHA_SEARCH_PROPERTY) {
            $sql .= ', sesha_inventory_properties p';
            $whereClause[] = '(p.txt_datavalue LIKE ' . $what .
                ' AND i.stock_id = p.stock_id)';
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
            $sql .= ', p.priority DESC';
        }

        try {
            $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
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
            return $this->_db->execute($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }
        // Give the user what we found
        return $result->fetchRow(DB_FETCHMODE_ASSOC);
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

        $values = array($stock['stock_name'],$stock['note'], $stockid);
        // Perform the queries
        try { 
            $result = $this->_db->update($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        return true;
    }

    /**
     * This will return the first category found matching a specific id.
     *
     * @param integer $category_id  The integer ID of the category to find.
     *
     * @return array  The category on success
     */
    public function getCategory($category_id)
    {
        $categories = $this->getCategories(null, $category_id);
        return $categories[$category_id];
    }

    /**
     * This function return all the categories matching an id.
     *
     * @param integer $stock_id     The stock ID of categories to fetch.
     *                              Returns all categories if null.
     * @param integer $category_id  The numeric ID of the categories to find.
     *
     * @return array  The array of matching categories on success, an empty
     *                array otherwise.
     */
    public function getCategories($stock_id = null, $category_id = null)
    {
        $where = ' WHERE 1 = 1 ';
        $sql = 'SELECT c.category_id AS id, c.category_id AS category_id, c.category AS category, c.description AS description, c.priority AS priority FROM sesha_categories c';
        if (!empty($stock_id)) {
            $sql .= ', sesha_inventory_categories dc ';
            $where .= sprintf('AND c.category_id = dc.category_id AND ' .
                'dc.stock_id = %d', $stock_id);
        }
        if (!empty($category_id)) {
            $where .= sprintf(' AND category_id = %d', $category_id);
        }
        $sql .= $where . ' ORDER BY c.priority DESC, c.category';

        try {
            $rows = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        return $rows;
    }

    /**
     * This will find all the available properties matching a specified ID.
     *
     * @param integer $property_id  The numeric ID of properties to find.
     *                              Matches all properties when null.
     *
     * @return array  matching properties on success
     * @throws Sesha_Exception
     */
    public function getProperties($property_id = array())
    {
        $sql = 'SELECT * FROM sesha_properties';
        if (!empty($property_id)) {
            $sql .= ' WHERE property_id IN (';
            foreach ($property_id as $id) {
                $sql .= (int)$id . ',';
            }
            $sql = substr($sql, 0, -1) . ')';
        }

        try {
            $properties = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Sesha_Exception($e);
        }

        array_walk($properties, array($this, '_unserializeParameters'));

        return $properties;
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
        $result = $this->getProperties($property_id);
        return $result[$property_id];
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
        $this->_connect();
        $sql = 'DELETE FROM sesha_properties WHERE property_id = ?';
        $values = array($property_id);
        try {
            return $this->_db->query($sql, $values);
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
     * Removes all categories from a particular stock item.
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
     * @param array $category    The array of categories to change.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    public function updateCategoriesForStock($stock_id, $category = array())
    {
        if (!is_array($category)) {
            $category = array($category);
        }
        /* First clear any categories that might be set for this item. */
        $sql = 'DELETE FROM sesha_inventory_categories ' .
                       'WHERE stock_id = ? ';

        try {
            $result = $this->_db->execute($sql, array($stock_id));
        } catch (Sesha_Exception $e) {
            throw new Sesha_Exception($e);
        }
        for ($i = 0; $i < count($category); $i++) {
            $category_id = $category[$i];
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

}
