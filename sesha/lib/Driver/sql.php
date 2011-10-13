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
 * The table structure can be created by the scripts/drivers/sesha_tables.sql
 * script.
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
class Sesha_Driver_sql extends Sesha_Driver
{
    /**
     * Handle for the database connection.
     * @var DB
     * @access private
     */
    var $_db;

    /**
     * Flag for the SQL server connection.
     * @var boolean
     * @access private
     */
    var $_connected;

    /**
     * This is the basic constructor for the sql driver.
     *
     * @param array $params  Hash containing the connection parameters.
     */
    function Sesha_Driver_sql($params = null)
    {
        parent::Sesha_Driver($params);
        $this->_db = null;
        $this->_connected = false;
    }

    /**
     * This will retrieve all of the stock items from the database, or just a
     * particular category of items.
     *
     * @param integer $category_id  The category ID you want to fetch.
     * @param array $property_ids   The ids of any properties to include in the list.
     *
     * @return mixed  Array of results on success; PEAR_Error on failure.
     */
    function listStock($category_id = null, $property_ids = array())
    {
        $this->_connect();

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

        Horde::logMessage('Sesha_Driver_sql::retrieve: ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
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
     * @return mixed  Array of results on success; PEAR_Error on failure.
     */
    function searchStock($what, $where = SESHA_SEARCH_NAME, $property_ids = array())
    {
        if (is_null($what) || is_null($where)) {
            return PEAR::raiseError(_("Invalid search parameters"));
        }

        $this->_connect();

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

        Horde::logMessage('Sesha_Driver_sql::search: ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
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
     * @return mixed  True on success; PEAR_Error on failure.
     */
    function fetch($stock_id)
    {
        $this->_connect();

        // Build the query
        $sql = 'SELECT * FROM sesha_inventory WHERE stock_id = ?';
        $values = array((int)$stock_id);

        Horde::logMessage('Sesha_Driver_sql::fetch: ' . $sql,
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        // Perform the search
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
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
     * @return mixed  True on success; PEAR_Error otherwise.
     */
    function delete($stock_id)
    {
        $this->_connect();

        // Build, log, and issue the query
        $sql = 'DELETE FROM sesha_inventory WHERE stock_id = ?';
        $values = array((int)$stock_id);
        Horde::logMessage('Sesha_Driver_sql::delete: ' . $sql, __FILE__,
            __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
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
     * @return mixed  The numeric ID of the newly added item; PEAR_Error on
     *                failure.
     */
    function add($stock)
    {
        $this->_connect();

        // Make sure we have a proper stock ID
        if (empty($stock['stock_id']) || $stock['stock_id'] < 1) {
            $stock['stock_id'] = $this->_db->nextId('sesha_inventory');
            if (is_a($stock['stock_id'], 'PEAR_Error')) {
                return $stock['stock_id'];
            }
        }

        require_once 'Horde/SQL.php';
        // Create the queries
        $sql = sprintf('INSERT INTO sesha_inventory %s',
            Horde_SQL::insertValues($this->_db, $stock));

        Horde::logMessage(sprintf('Sesha_Driver_sql::add %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        // Perform the queries
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $stock['stock_id'];
    }

    /**
     * This function will modify a pre-existing stock entry with new values.
     *
     * @param array $stock  The hash of values for the inventory item.
     *
     * @return mixed  True on success; PEAR_Error on failure.
     */
    function modify($stock_id, $stock)
    {
        $this->_connect();

        // Can't change the stock id. ever.
        if (isset($stock['stock_id'])) {
            unset($stock['stock_id']);
        }

        require_once 'Horde/SQL.php';
        $sql = sprintf('UPDATE sesha_inventory SET %s WHERE stock_id = %d',
            Horde_SQL::updateValues($this->_db, $stock), $stock_id);

        Horde::logMessage(sprintf('Sesha_Driver_sql::modify %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        // Perform the queries
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * This will return the first category found matching a specific id.
     *
     * @param integer $category_id  The integer ID of the category to find.
     *
     * @return mixed  The category on success; PEAR_Error otherwise.
     */
    function getCategory($category_id)
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
    function getCategories($stock_id = null, $category_id = null)
    {
        $this->_connect();

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

        Horde::logMessage(sprintf('Sesha_Driver_sql::getCategories %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->getAssoc($sql, true, null, DB_FETCHMODE_ASSOC);
    }

    /**
     * This will find all the available properties matching a specified ID.
     *
     * @param integer $property_id  The numeric ID of properties to find.
     *                              Matches all properties when null.
     *
     * @return mixed  An array of matching properties on success; PEAR_Error on
     *                failure.
     */
    function getProperties($property_id = null)
    {
        $this->_connect();

        $sql = 'SELECT * FROM sesha_properties';
        if (is_array($property_id)) {
            $sql .= ' WHERE property_id IN (';
            foreach ($property_id as $id) {
                $sql .= (int)$id . ',';
            }
            $sql = substr($sql, 0, -1) . ')';
        } elseif ($property_id) {
            $sql .= sprintf(' WHERE property_id = %d', $property_id);
        }

        Horde::logMessage(sprintf('Sesha_Driver_sql::getProperties %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $properties = $this->_db->getAssoc($sql, true, null, DB_FETCHMODE_ASSOC);
        if (is_a($properties, 'PEAR_Error')) {
            Horde::logMessage($properties, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $properties;
        }

        array_walk($properties, array($this, '_unserializeParameters'));

        return $properties;
    }

    /**
     * Finds the first matching property for a specified property ID.
     *
     * @param integer $property_id  The numeric ID of properties to find.
     *
     * @return mixed  The specified property on success; PEAR_Error on failure.
     */
    function getProperty($property_id)
    {
        $result = $this->getProperties($property_id);
        return $result[$property_id];
    }

    /**
     * Updates the attributes stored by a category.
     *
     * @param array $info  Updated category attributes.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    function updateCategory($info)
    {
        $this->_connect();

        $sql = 'UPDATE sesha_categories' .
               ' SET category = ?, description = ?, priority = ?' .
               ' WHERE category_id = ?';
        $values = array($info['category'], $info['description'], $info['priority'], $info['category_id']);

        Horde::logMessage(sprintf('Sesha_Driver_sql::updateCategory %s',
            $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql, $values);
    }

    /**
     * Adds a new category for classifying inventory.
     *
     * @param array $info  The new category's attributes.
     *
     * @return mixed  The ID of the new of the category on success; PEAR_Error
     *                otherwise.
     */
    function addCategory($info)
    {
        $this->_connect();

        $category_id = $this->_db->nextId('sesha_categories');
        if (is_a($category_id, 'PEAR_Error')) {
            return $category_id;
        }

        $sql = 'INSERT INTO sesha_categories' .
               ' (category_id, category, description, priority)' .
               ' VALUES (?, ?, ?, ?)';
        $values = array($category_id, $info['category'], $info['description'], $info['priority']);

        Horde::logMessage(sprintf('Sesha_Driver_sql::addCategory %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        return $category_id;
    }

    /**
     * Deletes a category.
     *
     * @param integer $category_id  The numeric ID of the category to delete.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    function deleteCategory($category_id)
    {
        $this->_connect();
        $sql = 'DELETE FROM sesha_categories WHERE category_id = ?';
        $values = array($category_id);

        Horde::logMessage(sprintf('Sesha_Driver_sql::deleteCategory %s',
            $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql, $values);
    }

    /**
     * Determines if a category exists in the storage backend.
     *
     * @param string $category  The string representation of the category to
     *                          find.
     *
     * @return boolean  True on success; false otherwise.
     */
    function categoryExists($category)
    {
        $this->_connect();
        $sql = 'SELECT * FROM sesha_categories WHERE category = ?';
        $values = array($category);

        Horde::logMessage(sprintf('Sesha_Driver_sql::categoryExists %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
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
    function updateProperty($info)
    {
        $this->_connect();

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

        Horde::logMessage(sprintf('Sesha_Driver_sql::updateProperty %s', $sql),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql, $values);
    }

    /**
     * Adds a new property to the storage backend.
     *
     * @param array $info Array with new property values.
     *
     * @return object  The PEAR DB_Result from the sql query.
     */
    function addProperty($info)
    {
        $this->_connect();
        $property_id = $this->_db->nextId('sesha_properties');
        if (is_a($property_id, 'PEAR_Error')) {
            return $property_id;
        }

        $sql = 'INSERT INTO sesha_properties (property_id, property, datatype, parameters, unit, description, priority) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $values = array(
            $property_id,
            $info['property'],
            $info['datatype'],
            serialize($info['parameters']),
            $info['unit'],
            $info['description'],
            $info['priority'],
        );

        Horde::logMessage(sprintf('Sesha_Driver_sql::addProperty %s', $sql),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql, $values);
    }

    /**
     * Deletes a property from the storage backend.
     *
     * @param integer $property_id  The numeric ID of the property to delete.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    function deleteProperty($property_id)
    {
        $this->_connect();
        $sql = 'DELETE FROM sesha_properties WHERE property_id = ?';
        $values = array($property_id);

        Horde::logMessage(sprintf('Sesha_Driver_sql::deleteProperty %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql, $values);
    }

    /**
     * This will return a set of properties for a set of specified categories.
     *
     * @param array $categories  The set of categories to fetch properties.
     *
     * @return mixed  An array of properties on success; PEAR_Error on failure.
     */
    function getPropertiesForCategories($categories = array())
    {
        $this->_connect();

        if (!is_array($categories)) {
            $categories = array($categories);
        }

        $in = '';
        foreach ($categories as $category) {
            $in .= !empty($in) ? ', ' . $category : $category;
        }
        $sql = sprintf('SELECT c.category_id AS category_id, c.category AS category, p.property_id AS property_id, p.property AS property, p.unit AS unit, p.description AS description, p.datatype AS datatype, p.parameters AS parameters FROM sesha_categories c, sesha_properties p, sesha_relations cp WHERE c.category_id = cp.category_id AND cp.property_id = p.property_id AND c.category_id IN (%s) ORDER BY p.priority DESC',
                       empty($in) ? $this->_db->quote($in) : $in);

        Horde::logMessage(sprintf('Sesha_Driver_sql::getPropertiesForCategories %s', $sql),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $properties = $this->_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
        if (is_a($properties, 'PEAR_Error')) {
            Horde::logMessage($properties, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $properties;
        }
        array_walk($properties, array($this, '_unserializeParameters'));

        return $properties;
    }

    /**
     * Updates a category with a set of properties.
     *
     * @param integer $category_id  The numeric ID of the category to update.
     * @param array $properties     An array of property ID's to add.
     *
     * @return object  PEAR DB_Result object from the sql query.
     */
    function setPropertiesForCategory($category_id, $properties = array())
    {
        $this->_connect();
        $this->clearPropertiesForCategory($category_id);
        foreach ($properties as $property) {
            $sql = sprintf('INSERT INTO sesha_relations
                (category_id, property_id) VALUES (%d, %d)',
                $category_id, $property);

            Horde::logMessage(sprintf(
                'Sesha_Driver_sql::setPropertiesForCategory %s', $sql),
                __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_db->query($sql);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * Removes all properties for a specified category.
     *
     * @param integer $category_id  The numeric ID of the category to update.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    function clearPropertiesForCategory($category_id)
    {
        $this->_connect();
        $sql = sprintf('DELETE FROM sesha_relations WHERE category_id = %d',
            $category_id);

        Horde::logMessage(sprintf(
            'Sesha_Driver_sql::clearPropertiesForCategory %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql);
    }

    /**
     * Returns a set of properties for a particular stock ID number.
     *
     * @param integer $stock_id  The numeric ID of the stock to find the
     *                           properties for.
     *
     * @return object  The PEAR DB_Result object from the sql query.
     */
    function getPropertiesForStock($stock_id)
    {
        $this->_connect();

        $sql = sprintf('SELECT p.property_id AS property_id, p.property AS property, p.datatype AS datatype, ' .
            'p.unit AS unit, p.description AS description, a.attribute_id AS attribute_id, a.int_datavalue AS int_datavalue, ' .
            'a.txt_datavalue AS txt_datavalue FROM sesha_properties p, ' .
            'sesha_inventory_properties a WHERE p.property_id = ' .
            'a.property_id AND a.stock_id = %d ORDER BY p.priority DESC',
            $stock_id);

        Horde::logMessage(sprintf('Sesha_Driver_sql::getPropertiesForStock %s',
            $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $properties = $this->_db->getAll($sql, null, DB_FETCHMODE_ASSOC);

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
     */
    function clearPropertiesForStock($stock_id, $categories = array())
    {
        $this->_connect();

        if (!is_array($categories)) {
            $categories = array(0 => array('category_id' => $categories));
        }
        /* Get list of properties for this set of categories. */
        $properties = $this->getPropertiesForCategories($categories);
        if (is_a($properties, 'PEAR_Error')) {
            return $properties;
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

        Horde::logMessage(sprintf(
            'Sesha_Driver_sql::clearPropertiesForStock %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_db->query($sql);
    }

    /**
     * Updates the set of properties for a particular stock item.
     *
     * @param integer $stock_id  The numeric ID of the stock to update.
     * @param array $properties  The hash of properties to update.
     *
     * @return mixed  The DB_Result object on success; PEAR_Error otherwise.
     */
    function updatePropertiesForStock($stock_id, $properties = array())
    {
        $this->_connect();

        $result = false;
        foreach ($properties as $property_id => $property_value) {
            // Now clear any existing attribute values for this property_id
            // and stock_id.
            $sql = sprintf('DELETE FROM sesha_inventory_properties ' .
                           'WHERE stock_id = %d AND property_id = %d',
                           $stock_id, $property_id);

            Horde::logMessage(sprintf('Sesha_Driver_sql::updatePropertiesForStock %s', $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_db->query($sql);
            if (!is_a($result, 'PEAR_Error') && !empty($property_value)) {
                $new_id = $this->_db->nextId('sesha_inventory_properties');
                if (is_a($new_id, 'PEAR_Error')) {
                    return $new_id;
                }
                $sql = 'INSERT INTO sesha_inventory_properties' .
                       ' (attribute_id, property_id, stock_id, txt_datavalue)' .
                       ' VALUES (?, ?, ?, ?)';
                $values = array($new_id, $property_id, $stock_id, is_string($property_value) ? $property_value : serialize($property_value));
                Horde::logMessage(sprintf('Sesha_Driver_sql::updatePropertiesForStock %s', $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $result = $this->_db->query($sql, $values);
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
    function updateCategoriesForStock($stock_id, $category = array())
    {
        $this->_connect();

        if (!is_array($category)) {
            $category = array($category);
        }
        /* First clear any categories that might be set for this item. */
        $sql = sprintf('DELETE FROM sesha_inventory_categories ' .
                       'WHERE stock_id = %d ', $stock_id);

        Horde::logMessage(sprintf(
            'Sesha_Driver_sql::updateCategoriesForStock %s', $sql),
            __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($sql);
        if (!is_a($result, 'PEAR_Error')) {
            for ($i = 0; $i < count($category); $i++) {
                $category_id = $category[$i];
                $sql = sprintf('INSERT INTO sesha_inventory_categories ' .
                    '(stock_id, category_id) VALUES (%d, %d)',
                    $stock_id, $category_id);

                Horde::logMessage(sprintf(
                    'Sesha_Driver_sql::updateCategoriesForStock %s',
                    $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $result = $this->_db->query($sql);
            }
        }

        return $result;
    }

    /**
     */
    function _unserializeParameters(&$val, $key)
    {
        $val['parameters'] = @unserialize($val['parameters']);
    }

    /**
     * This function will connect to the sql database.
     *
     * @return boolean  True on success; exits (Horde::fatal) on error.
     * @access private
     */
    function _connect()
    {
        if (!$this->_connected) {
            // Get the driver parameters.
            Horde::assertDriverConfig($this->_params, 'storage',
                array('phptype', 'charset'));

            if (!isset($this->_params['database'])) {
                $this->_params['database'] = '';
            }
            if (!isset($this->_params['username'])) {
                $this->_params['username'] = '';
            }
            if (!isset($this->_params['hostspec'])) {
                $this->_params['hostspec'] = '';
            }

            // Connect to the server.
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, array('persistent' => !empty($this->_params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

            $this->_connected = true;
        }

        return true;
    }

    function _normalizeStockProperties($rows, $property_ids)
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
