<?php
/**
 * News Category Class.
 *
 * $Id: Categories.php 1261 2009-02-01 23:20:07Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_Categories {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    private $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    private $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $db if a separate write database is not required.
     *
     * @var DB
     */
    private $_write_db;

    /**
     * An array containing all the tree nodes.
     *
     * @var array
     */
    private $_nodes = array();

    /**
     * The top-level nodes in the tree.
     *
     * @var array
     */
    private $_root_nodes = array();

    /**
     * An enumeratic array
     *
     * @var array
     */
    private $_enum = array();

    /**
     * Has for view url link
     *
     * @var array
     */
    private $_view_url;

    /**
     * Handle for the tables prefix.
     *
     * @var prefix
     */
    private $prefix = 'news';

    /**
     * Contruct the News object
     */
    public function __construct($categoreis = null)
    {
        $this->_nodes = $this->getCategories(false);
        if ($this->_nodes instanceof PEAR_Error) {
            return $this->_nodes;
        }

        foreach ($this->_nodes as $id => $category) {
            if (empty($category['category_parentid'])) {
                if (!in_array($id, $this->_root_nodes)) {
                    $this->_root_nodes[] = $id;
                }
            } else {
                if (empty($this->_nodes[$category['category_parentid']]['children'])) {
                    $this->_nodes[$category['category_parentid']]['children'] = array();
                }
                if (!in_array($id, $this->_nodes[$category['category_parentid']]['children'])) {
                    $this->_nodes[$category['category_parentid']]['children'][] = $id;
                }
            }
        }
        $this->_buildIndents($this->_root_nodes);
    }

    /**
     * Returns the string for category selection
     *
     * @return string
     */
    public function getSelect()
    {
        $output = '';
        foreach ($this->_root_nodes as $node_id) {
            $output .= $this->_buildSelect($node_id);
        }

        return $output;
    }

    /**
    *
    */
    private function _buildSelect($node_id)
    {
        $output = '<option value="' . $node_id . '">'
                . str_repeat(' - ', $this->_nodes[$node_id]['indent'])
                . $this->_nodes[$node_id]['category_name'] . '</option>';

        if (isset($this->_nodes[$node_id]['children'])) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $output .= $this->_buildSelect($child_node_id);
            }
        }

        return $output;
    }

    /**
     * Returns the enumeratic array for select form pameter.
     *
     * @return array  The category array (cat => name)
     */
    public function getEnum()
    {
        if (empty($this->_enum)) {
            foreach ($this->_root_nodes as $node_id) {
                $this->_buildEnum($node_id);
            }
        }

        return $this->_enum;
    }

    private function _buildEnum($node_id)
    {
        $this->_enum[$node_id] = str_repeat(' - ', $this->_nodes[$node_id]['indent'])
                                . $this->_nodes[$node_id]['category_name'];

        if (isset($this->_nodes[$node_id]['children'])) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $this->_buildEnum($child_node_id);
            }
        }
    }

    /**
     * Returns html for category selection
     *
     * @return string
     */
    public function getHtml()
    {
        $this->_view_url = News::getUrlFor('category', '');

        $output = '';
        foreach ($this->_root_nodes as $node_id) {
            $output .= $this->_buildHtml($node_id);
        }

        return $output;
    }

    private function _buildHtml($node_id)
    {
        $output = '';
        if ($this->_nodes[$node_id]['indent'] == 0) {
            $output .= '<strong>';
        }
        $url = $this->_view_url . $node_id;
        $output .= Horde::link($url) . $this->_nodes[$node_id]['category_name'] . '</a>, ';
        if ($this->_nodes[$node_id]['indent'] == 0) {
            $output .= '</strong><br />';
        }

        if (isset($this->_nodes[$node_id]['children'])) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $output .= $this->_buildHtml($child_node_id);
            }
        }

        return $output;
    }

    /**
     * Set the indent level for each node in the tree.
     */
    private function _buildIndents($nodes, $indent = 0)
    {
        foreach ($nodes as $id) {
            $this->_nodes[$id]['indent'] = $indent;
            if (!empty($this->_nodes[$id]['children'])) {
                $this->_buildIndents($this->_nodes[$id]['children'], $indent + 1);
            }
        }
    }

    /**
     * Returns array of cateogriy children id
     *
     * @return array
     */
    public function getChildren($id)
    {
        if (!empty($this->_nodes[$id]['children'])) {
            return $this->_nodes[$id]['children'];
        } else {
            return array();
        }
    }

    /**
     * Returns the current language
     *
     * @return string  The current language.
     */
    public function getAllowed($perm = Horde_Perms::SHOW)
    {
        $cats = $this->getCategories();
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($GLOBALS['registry']->isAdmin(array('permission' => 'news:admin')) ||
            $perms->hasPermission('news', $GLOBALS['registry']->getAuth(), $perm)) {
            return $cats;
        }

        foreach ($cats as $key => $value) {
            // user has access?
            if (!$perms->hasPermission('news:categories', $GLOBALS['registry']->getAuth(), $perm)  && // master
                !$perms->hasPermission('news:categories:' . $key, $GLOBALS['registry']->getAuth(), $perm) && // child
                !$perms->hasPermission('news:categories:' . $this->_nodes[$key]['category_parentid'], $GLOBALS['registry']->getAuth(), $perm) // father
                ) {
                unset($cats[$key]);
            }
        }

        return $cats;
    }

    /**
     * Returns the name for category
     *
     * @return string
     */
    public function getName($id)
    {
        $cats = $this->getCategories();
        return $cats[$id];
    }

    /**
     * Returns the full name of a category
     *
     * @return string
     */
    public function getFullName($id)
    {
        static $names;

        if (isset($names[$id])) {
            return $names[$id];
        } elseif (empty($id)) {
            return $GLOBALS['registry']->get('name');
        }

        $cats = $this->getCategories(false);
        $names[$id] = '';
        $parent = $cats[$id]['category_parentid'];

        while ($parent) {
            $names[$id] .= $cats[$parent]['category_name'] . ': ';
            $parent = $cats[$parent]['category_parentid'];
        }

        $names[$id] .= $cats[$id]['category_name'];

        return $names[$id];
    }

    /**
     * Save a category data into the backend from edit form.
     *
     * @param array $info  The category data to save.
     *
     * @return mixed  PEAR error.
     */
    public function saveCategory($info)
    {
        $this->_connect();

        /* Update/Insert category. */
        if (!empty($info['category_id'])) {
            $result = $this->_updateCategory($info['category_id'], $info);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        } else {
            $info['category_id'] = $this->_insertCategory($info);
            if ($info['category_id'] instanceof PEAR_Error) {
                return $info['category_id'];
            }
        }

        /* If image uploaded save to backend. */
        if (!empty($info['category_image']['name'])) {
            $image = News::saveImage($info['category_image']['file'], $info['category_id'], 'categories', $info['image_resize']);
            if ($image instanceof PEAR_Error) {
                return $image;
            }

            $sql = 'UPDATE ' . $this->prefix . '_categories SET category_image = ? WHERE category_id = ?';
            $this->_write_db->query($sql, array(1, $info['category_id']));
        }

        // Clean cache
        $this->_expireCache();

        return $info['category_id'];
    }

    /**
     * Insert category data.
     *
     * @param mixed $data  The category data to insert.
     *
     * @return array  Inserted ID or PEAR error.
     */
    private function _insertCategory($data)
    {
        $new_id = $this->_write_db->nextId('news_categories');
        if ($new_id instanceof PEAR_Error) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $sql = 'INSERT INTO ' . $this->prefix . '_categories' .
               ' (category_id, category_parentid) VALUES (?, ?)';
        $values = array($new_id, (int)$data['category_parentid']);

        $category = $this->_write_db->query($sql, $values);
        if ($category instanceof PEAR_Error) {
            Horde::logMessage($category, 'ERR');
            return $category;
        }

        $sql = 'INSERT INTO ' . $this->prefix . '_categories_nls VALUES (?, ?, ?, ?)';
        foreach ($GLOBALS['conf']['attributes']['languages'] as $lang) {

            $values = array($new_id,
                            $lang,
                            $data['category_name_' . $lang],
                            $data['category_description_' . $lang]);
            $result = $this->_write_db->query($sql, $values);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return $new_id;
    }

    /**
     * Update category data.
     *
     * @param integer $category_id  The category id to update.
     * @param array   $data       The category data to update.
     *
     * @return array  NULL or PEAR error.
     */
    private function _updateCategory($category_id, $data)
    {
        $sql = 'UPDATE ' . $this->prefix . '_categories' .
               ' SET category_parentid = ? ' .
               ' WHERE category_id = ?';
        $values = array((int)$data['category_parentid'], $category_id);

        $category = $this->_write_db->query($sql, $values);
        if ($category instanceof PEAR_Error) {
            Horde::logMessage($category, 'ERR');
            return $category;
        }

        $sql = 'UPDATE ' . $this->prefix . '_categories_nls SET '
            . ' category_name = ?, category_description = ? WHERE '
            . ' category_id = ? AND category_nls = ?';
        foreach ($GLOBALS['conf']['attributes']['languages'] as $lang) {
            $values = array($data['category_name_' . $lang],
                            $data['category_description_' . $lang],
                            $category_id,
                            $lang);
            $result = $this->_write_db->query($sql, $values);
        }

        return $result;
    }

    /**
     * Delete a category
     *
     * @return booelan
     */
    public function deleteCategory($id)
    {
        // Clean cache
        $this->_expireCache();

        // Delete image
        News::deleteImage($id, 'categories');

        // Delete record
        $this->_connect();
        $this->_write_db->query('DELETE FROM ' . $this->prefix . '_categories WHERE category_id = ?', array($id));
        return $this->_write_db->query('DELETE FROM ' . $this->prefix . '_categories_nls WHERE category_id = ?', array($id));
    }

     /**
     * Return an array of data for edit form.
     *
     * @param  $cid  The category ID.
     *
     * @return mixed Array that hold data for edit form.
     */
    function getCatArray($cid)
    {
        $this->_connect();

        $sql = 'SELECT c.category_parentid, c.category_image, '
            . ' l.category_nls, l.category_name, l.category_description '
            . ' FROM news_categories c, news_categories_nls l '
            . ' WHERE c.category_id = ? AND c.category_id = l.category_id';

        $category = array('category_id' => $cid);
        $result = $this->_db->getAll($sql, array($cid), DB_FETCHMODE_ASSOC);

        foreach ($result as $row) {
            $category['category_parentid'] = $row['category_parentid'];
            $category['category_image'] = $row['category_image'];
            $category['category_name_' . $row['category_nls']] = $row['category_name'];
            $category['category_description_' . $row['category_nls']] = $row['category_description'];
        }

        return $category;
    }

    /**
     * Return a Horde_Tree representation of the News_Categories tree.
     *
     * @return string  The html showing the categories as a Horde_Tree.
     */
    function renderTree($current = null, $click_url = null, $browse_only = false, $have_add_item = false)
    {
        $cats = $this->getCategories(false);

        $params = array('icon' => Horde_Themes::img('folder_open.png'));

        // Set up the tree
        $tree = $GLOBALS['injector']->getInstance('Horde_Tree')->getTree('news_cats', 'Javascript', array(
            'alternate' => true,
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'class' => 'item',
            'width' => '100%'
        ));

        // prepare add link
        if ($have_add_item) {
            $add_img = Horde::img('mkdir.png', _("Add New Item"));
            $add_item = Horde::url('items/edit.php');
        }

        foreach ($cats as $cid => $category) {

            if ($click_url !== null) {
                $name = Horde::link(Horde_Util::addParameter($click_url, 'cid', $cid), _("Select Category")) . $category['category_name'] . '</a>';
            } else {
                $name = $category['category_name'];
            }

            $links = array();
            if ($have_add_item) {
                $links[] = Horde::link(Horde_Util::addParameter($add_item, 'cid', $cid), _("Add New Item")) . $add_img . '</a>';
            }

            $parent_id = $category['category_parentid'] ? $category['category_parentid'] : null;
            $tree->addNode($cid, $parent_id, $name, $this->_nodes[$cid]['indent'], true, $params, $links);
        }


        return $tree->renderTree();
    }

    /**
    * Get category child list
    */
    public function getChildList($id)
    {
        return isset($this->_nodes[$id]['children']) ? $this->_nodes[$id]['children'] : array();
    }

    /**
     * Return a stored image for a category.
     *
     * @param integer $cid  The id of the category requested.
     *
     * @return string  The image name.
     */
    function getImage($cid)
    {
        /* Check if there is an image for requested category. */
        if (!isset($this->_nodes[$cid]['category_image'])) {
            return '';
        }

        /* return url */
        if ($GLOBALS['conf']['images']['direct']) {
            return Horde::img('/categories/' . $cid . '.' . $GLOBALS['conf']['images']['image_type'], $cid, '', $GLOBALS['conf']['images']['direct']);
        } else {
            $img_params = array('f' => $cid . '.' . $GLOBALS['conf']['images']['image_type'],
                                's' => 'vfs',
                                'p' => self::VFS_PATH . '/images/categories/',
                                'c' => 'news');
            return Horde_Util::addParameter(Horde::url('/services/images/view.php'), $img_params);
        }
    }

    /**
     * Get available categories
     *
     * @return array  An array containing caegories
     */
    public function getCategories($flat = true)
    {
        $lang = News::getLang();
        $cache_key = 'NewsCategories_' . $lang . '_' . (int)$flat;
        $categories = $GLOBALS['cache']->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($categories) {
            return unserialize($categories);
        }

        $this->_connect();

        $sql = 'SELECT c.category_id, l.category_name, c.category_parentid, l.category_description, c.category_image '
            . ' FROM ' . $this->prefix . '_categories c, ' . $this->prefix . '_categories_nls l '
            . ' WHERE c.category_id = l.category_id AND l.category_nls = ? ORDER BY category_name ASC';
        $result = $this->_db->getAssoc($sql, false, array($lang), DB_FETCHMODE_ASSOC);

        if ($result instanceof PEAR_Error) {
            return $result;
        }

        if (!$flat) {
            $GLOBALS['cache']->set($cache_key, serialize($result));
            return $result;
        }

        $categories = array();
        foreach ($result as $category_id => $row) {
            if (!empty($row['category_description']) && $row['category_name'] != $row['category_description']) {
                $row['category_name'] .=  ' [ ' . $row['category_description'] . ' ]';
            }
            $categories[$category_id] = $row['category_name'];
        }

        $GLOBALS['cache']->set($cache_key, serialize($categories));
        return $categories;
    }

    /**
     * Expire categories cache
     */
    private function _expireCache()
    {
        $langs = $GLOBALS['conf']['attributes']['languages'];
        $langs[] = News::getLang();

        foreach ($langs as $lang) {
            $GLOBALS['cache']->expire('NewsCategories_' . $lang . '_0');
            $GLOBALS['cache']->expire('NewsCategories_' . $lang . '_1');
        }
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     */
    private function _connect()
    {
        $this->_db = $GLOBALS['news']->db;
        $this->_write_db = $GLOBALS['news']->write_db;
    }
}
