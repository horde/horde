<?php
/**
 * Classes for dealing with tags within Ansel
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */

/**
 * Static helper class for writing/reading tag values
 *
 * @TODO: Move tag storage to Content_Tagger
 * @static
 */
class Ansel_Tags
{
    /**
     * Write out the tags for a specific resource.
     *
     * @param int    $resource_id    The resource we are tagging.
     * @param array  $tags           An array of tags.
     * @param string $resource_type  The type of resource (image or gallery)
     *
     * @return boolean True on success
     * @throws InvalidArgumentException
     * @throws Ansel_Exception
     */
    static public function writeTags($resource_id, $tags, $resource_type = 'image')
    {
        // First, make sure all tag names exist in the DB.
        $tagkeys = array();
        $insert = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_tags (tag_id, tag_name) VALUES(?, ?)');
        foreach ($tags as $tag) {
            if (!empty($tag)) {
                if (!preg_match("/^[a-zA-Z0-9%_+.!*',()~-]*$/", $tag)) {
                    throw new InvalidArgumentException('Invalid characters in tag.');
                }
                $tag = Horde_String::lower(trim($tag));
                $sql = $GLOBALS['ansel_db']->prepare('SELECT tag_id FROM ansel_tags WHERE tag_name = ?');
                $result = $sql->execute(Horde_String::convertCharset($tag, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset']));
                $results = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
                $result->free();

                if (empty($results)) {
                    $id = $GLOBALS['ansel_db']->nextId('ansel_tags');
                    $result = $insert->execute(array($id, Horde_String::convertCharset($tag, $GLOBALS['registry']->getCharset(), $GLOBALS['conf']['sql']['charset'])));
                    $tagkeys[] = $id;
                } elseif ($results instanceof PEAR_Error) {
                    Horde::logMessage($results->getMessage(), 'ERR');
                    throw new Ansel_Exception($results);
                } else {
                    $tagkeys[] = $results['tag_id'];
                }
            }
        }
        $insert->free();

        if ($resource_type == 'image') {
            $delete = $GLOBALS['ansel_db']->prepare('DELETE FROM ansel_images_tags WHERE image_id = ?');
            $query = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_images_tags (image_id, tag_id) VALUES(?, ?)');
        } else {
            $delete =  $GLOBALS['ansel_db']->prepare('DELETE FROM ansel_galleries_tags WHERE gallery_id = ?');
            $query = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_galleries_tags (gallery_id, tag_id) VALUES(?, ?)');
        }
        Horde::logMessage('SQL query by Ansel_Tags::writeTags: ' . $query->query, 'DEBUG');
        $delete->execute(array($resource_id));
        foreach ($tagkeys as $key) {
            $query->execute(array($resource_id, $key));
        }

        $delete->free();
        $query->free();

        /* We should clear at least any of our cached counts */
        Ansel_Tags::clearCache();
        return true;
    }

    /**
     * Retrieve the tags for a specified resource.
     *
     * @param integer $resource_id    The resource to get tags for.
     * @param string  $resource_type  The type of resource (gallery or image)
     *
     * @return mixed  An array of tags
     */
    static public function readTags($resource_id, $resource_type = 'image')
    {
        if ($resource_type == 'image') {
            $stmt = $GLOBALS['ansel_db']->prepare('SELECT  ansel_tags.tag_id, tag_name FROM ansel_tags INNER JOIN ansel_images_tags ON ansel_images_tags.tag_id = ansel_tags.tag_id WHERE ansel_images_tags.image_id = ?');
        } else {
            $stmt = $GLOBALS['ansel_db']->prepare('SELECT  ansel_tags.tag_id, tag_name FROM ansel_tags INNER JOIN ansel_galleries_tags ON ansel_galleries_tags.tag_id = ansel_tags.tag_id WHERE ansel_galleries_tags.gallery_id = ?');
        }
        if ($stmt instanceof PEAR_Error) {
            throw new Ansel_Exception($stmt);
        }
        Horde::logMessage('SQL query by Ansel_Tags::readTags ' . $stmt->query, 'DEBUG');
        $result = $stmt->execute((int)$resource_id);
        $tags = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true);
        foreach ($tags as $id => $tag) {
            $tags[$id] = Horde_String::convertCharset(
                $tag, $GLOBALS['conf']['sql']['charset']);
        }
        $stmt->free();
        $result->free();

        return $tags;
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags     An optional array of tag_ids. If omitted, all tags
     *                        will be included.
     * @param integer $limit  Limit the number of tags returned to this value.
     *
     * @return Array  An array containing tag_name, and total
     */
    static public function listTagInfo($tags = null, $limit = 500)
    {
        global $conf;
        // Only return the full list if $tags is omitted, not if
        // an empty array is passed
        if (is_array($tags) && count($tags) == 0) {
            return array();
        }
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $cache_key = 'ansel_taginfo_' . (!is_null($tags) ? md5(serialize($tags) . $limit) : $limit);
            $cvalue = $GLOBALS['injector']->getInstance('Horde_Cache')->get($cache_key, $conf['cache']['default_lifetime']);
            if ($cvalue) {
                return unserialize($cvalue);
            }
        }

        $sql = 'SELECT tn.tag_id, tag_name, COUNT(tag_name) as total FROM ansel_tags as tn INNER JOIN (SELECT tag_id FROM ansel_galleries_tags UNION ALL SELECT tag_id FROM ansel_images_tags) as t ON t.tag_id = tn.tag_id ';
        if (!is_null($tags) && is_array($tags)) {
            $sql .= 'WHERE tn.tag_id IN (' . implode(',', $tags) . ') ';
        }
        $sql .= 'GROUP BY tn.tag_id, tag_name ORDER BY total DESC';
        if ($limit > 0) {
            $GLOBALS['ansel_db']->setLimit((int)$limit);
        }

        $results = $GLOBALS['ansel_db']->queryAll($sql, null, MDB2_FETCHMODE_ASSOC, true);
        foreach ($results as $id => $taginfo) {
            $results[$id]['tag_name'] = Horde_String::convertCharset(
                $taginfo['tag_name'], $GLOBALS['conf']['sql']['charset']);
        }
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->set($cache_key, serialize($results));
        }

        return $results;
    }

    /**
     * Search for resources matching the specified criteria
     *
     * @param array  $ids            An array of tag_ids to search for.
     * @param int    $max            The maximum number of resources to return.
     * @param int    $from           The number to start from
     * @param string $resource_type  Either 'images', 'galleries', or 'all'.
     * @param string $user           Limit the result set to resources
     *                               owned by this user.
     *
     * @return array An array of image_ids and gallery_ids
     */
    static public function searchTagsById($ids, $max = 0, $from = 0,
                                          $resource_type = 'all', $user = null)
    {
        if (!is_array($ids) || !count($ids)) {
            return array('galleries' => array(), 'images' => array());
        }

        $skey = md5(serialize($ids) . $from . $resource_type . $max . $user);

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
           $key = $GLOBALS['registry']->getAuth() . '__anseltagsearches';
           $cvalue = $GLOBALS['injector']->getInstance('Horde_Cache')->get($key, 300);
           $cvalue = @unserialize($cvalue);
           if (!$cvalue) {
               $cvalue = array();
           }
           if (!empty($cvalue[$skey])) {
               return $cvalue[$skey];
           }
        }

        $ids = array_values($ids);
        $results = array();
        /* Retrieve any images that match */
        if ($resource_type != 'galleries') {
            $sql = 'SELECT image_id, count(tag_id) FROM ansel_images_tags '
                . 'WHERE tag_id IN (' . implode(',', $ids) . ') GROUP BY '
                . 'image_id HAVING count(tag_id) = ' . count($ids);

            Horde::logMessage('SQL query by Ansel_Tags::searchTags: ' . $sql, 'DEBUG');
            $GLOBALS['ansel_db']->setLimit($max, $from);
            $images = $GLOBALS['ansel_db']->queryCol($sql);
            if ($images instanceof PEAR_Error) {
                Horde::logMessage($images, 'ERR');
                $results['images'] = array();
            } else {
                /* Check permissions and filter on $user if required */
                $imgs = array();
                foreach ($images as $id) {
                    try {
                        $img = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($id);
                        $gal = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($img->gallery);
                        $owner = $gal->get('owner');
                        if ($gal->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW) &&
                            (!isset($user) || (isset($user) && $owner && $owner == $user))) {
                            $imgs[] = $id;
                        }
                    } catch (Ansel_Exception $e) {
                        Horde::logMessage($e->getMessage(), 'ERR');
                        break;
                    }
                }
                $results['images'] = $imgs;
            }
        }

        /* Now get the galleries that match */
        if ($resource_type != 'images') {
            $results['galleries'] = array();
            $sql = 'SELECT gallery_id, count(tag_id) FROM ansel_galleries_tags '
               . 'WHERE tag_id IN (' . implode(',', $ids) . ') GROUP BY '
               . 'gallery_id HAVING count(tag_id) = ' . count($ids);

            Horde::logMessage('SQL query by Ansel_Tags::searchTags: ' . $sql, 'DEBUG');
            $GLOBALS['ansel_db']->setLimit($max, $from);

            $galleries = $GLOBALS['ansel_db']->queryCol($sql);
            if ($galleries instanceof PEAR_Error) {
                Horde::logMessage($galleries, 'ERR');
            } else {
                /* Check perms */
                foreach ($galleries as $id) {
                    try {
                        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($id);
                    } catch (Ansel_Exception $e) {
                        Horde::logMessage($e->getMessage(), 'ERR');
                        continue;
                    }
                    if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)  && (!isset($user) || (isset($user) && $gallery->get('owner') && $gallery->get('owner') == $user))) {
                        $results['galleries'][] = $id;
                    }
                }
            }
        }

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $cvalue[$skey] = $results;
            $GLOBALS['injector']->getInstance('Horde_Cache')->set($key, serialize($cvalue));
        }

        return $results;
    }

    /**
     * Search for resources matching a specified set of tags
     * and optionally limit the result set to resources owned by
      * a specific user.
     *
     * @param array  $names          An array of tag strings to search for.
     * @param int    $max            The maximum number of resources to return.
     * @param int    $from           The resource to start at.
     * @param string $resource_type  Either 'images', 'galleries', or 'all'.
     * @param string $user           Limit the result set to resources owned by
     *                               specified user.
     *
     * @return mixed An array of image_ids and gallery_ids
     */
    static public function searchTags($names = array(), $max = 10, $from = 0,
                                      $resource_type = 'all', $user = null)
    {
        /* Get the tag_ids */
        $ids = Ansel_Tags::getTagIds($names);
        return Ansel_Tags::searchTagsbyId($ids, $max, $from, $resource_type,
                                          $user);
    }

    /**
     * Retrieve a set of tags with relationships to the specified set
     * of tags.
     *
     * @param array $tags  An array of tag_ids
     *
     * @return mixed A hash of tag_id -> tag_name | PEAR_Error
     */
    static public function getRelatedTags($ids)
    {
        if (!count($ids)) {
            return array();
        }

        /* Build the monster SQL statement.*/
        $sql = 'SELECT DISTINCT t.tag_id, t.tag_name FROM ansel_images_tags as r, ansel_images as i, ansel_tags as t';
        for ($i = 0; $i < count($ids); $i++) {
            $sql .= ',ansel_images_tags as r' . $i;
        }
        $sql .= ' WHERE r.tag_id = t.tag_id AND r.image_id = i.image_id';
        for ($i = 0; $i < count($ids); $i++) {
            $sql .= ' AND r' . $i . '.image_id = r.image_id AND r.tag_id != ' . (int)$ids[$i] . ' AND r' . $i . '.tag_id = ' . (int)$ids[$i];
        }

        /* Note that we don't convertCharset here, it's done in listTagInfo */
        $imgtags = $GLOBALS['ansel_db']->queryAll($sql, null, MDB2_FETCHMODE_ASSOC, true);

        /* Now get the galleries. */
        $table = 'ansel_shares';
        $sql = 'SELECT DISTINCT t.tag_id, t.tag_name FROM ansel_galleries_tags as r, ' . $table . ' AS i, ansel_tags as t';
        for ($i = 0; $i < count($ids); $i++) {
            $sql .= ', ansel_galleries_tags as r' . $i;
        }
        $sql .= ' WHERE r.tag_id = t.tag_id AND r.gallery_id = i.share_id';
        for ($i = 0; $i < count($ids); $i++) {
            for ($i = 0; $i < count($ids); $i++) {
                $sql .= ' AND r' . $i . '.gallery_id = r.gallery_id AND r.tag_id != ' . (int)$ids[$i] . ' AND r' . $i . '.tag_id = ' . (int)$ids[$i];
            }
        }
        $galtags = $GLOBALS['ansel_db']->queryAll($sql, null, MDB2_FETCHMODE_ASSOC, true);

        /* Can't use array_merge here since it would renumber the array keys */
        foreach ($galtags as $id => $name) {
            if (empty($imgtags[$id])) {
                $imgtags[$id] = $name;
            }
        }

        /* Get an array of tag info sorted by total */
        $tagids = array_keys($imgtags);
        if (count($tagids)) {
            $imgtags = Ansel_Tags::listTagInfo($tagids);
        }

        return $imgtags;
    }

    /**
     * Get the URL for a tag link
     *
     * @param array $tags      The tag ids to link to
     * @param string $action   The action we want to perform with this tag.
     * @param string $owner    The owner we want to filter the results by
     *
     * @return string  The URL for this tag and action
     */
    static public function getTagLinks($tags, $action = 'add', $owner = null)
    {
        $results = array();
        foreach ($tags as $id => $taginfo) {
            $params = array('view' => 'Results',
                            'tag' => $taginfo['tag_name']);
            if (!empty($owner)) {
                $params['owner'] = $owner;
            }
            if ($action != 'add') {
                $params['actionID'] = $action;
            }
            $link = Ansel::getUrlFor('view', $params, true);
            $results[$id] = $link;
        }

        return $results;
    }

    /**
      * Get a list of tag_ids from a list of tag_names
      *
      * @param array $tags An array of tag_names
      *
      * @return array  An array of tag_names => tag_ids
      */
    static public function getTagIds($tags)
    {
        if (!count($tags)) {
            return array();
        }
        $stmt = $GLOBALS['ansel_db']->prepare('SELECT ansel_tags.tag_name, ansel_tags.tag_id FROM ansel_tags WHERE ansel_tags.tag_name IN (' . str_repeat('?, ', count($tags) - 1) . '?)');
        $result = $stmt->execute(array_values($tags));
        $ids = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true);
        $newIds = array();
        foreach ($ids as $tag => $id) {
            $newIds[Horde_String::convertCharset($tag, $GLOBALS['conf']['sql']['charset'])] = $id;
        }
        $result->free();
        $stmt->free();

        return $newIds;
    }

    /**
     * Get the tag names from ids
     *
     * @param array $ids  An array of tag ids
     *
     * @return array  A hash of tag_id => tag_names
     */
    static public function getTagNames($ids)
    {
        if (!count($ids)) {
            return array();
        }
        $stmt = $GLOBALS['ansel_db']->prepare('SELECT t.tag_id, t.tag_name FROM ansel_tags as t WHERE t.tag_id IN(' . str_repeat('?, ', count($ids) - 1) . '?)');
        $result = $stmt->execute(array_values($ids));
        $tags = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true);
        foreach ($tags as $id => $tag) {
            $tags[$id] = Horde_String::convertCharset(
                $tag, $GLOBALS['conf']['sql']['charset']);
        }
        $result->free();
        $stmt->free();

        return $tags;
    }

    /**
     * Retrieve an Ansel_Tags_Search object
     *
     * @return Ansel_Tags_Search
     * @TODO: refactor into Ansel_Search
     */
    static public function getSearch($tags = null, $owner = null)
    {
        return new Ansel_Tags_Search($tags, $owner);
    }

    /**
     * Clear the session cache
     */
    static public function clearSearch()
    {
        unset($_SESSION['ansel_tags_search']);
    }

    static public function clearCache()
    {
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire($GLOBALS['registry']->getAuth() . '__anseltagsearches');
        }
    }
}

/**
 * Class that represents a slice of a tag search
 *
 * @TODO: Move this to Ansel_Search_Tags
 */
class Ansel_Tags_Search {

    var $_tags = array();
    var $_totalCount = null;
    var $_owner = null;
    var $_dirty = false;

    /**
     * Constructor
     *
     * @param array $tags  An array of tag_ids to match. If null is passed then
     *                     the tags will be loaded from the session.
     */
    function Ansel_Tags_Search($tags = null, $owner = null)
    {
        if (!empty($tags)) {
            $this->_tags = $tags;
        } else {
            $this->_tags = (!empty($_SESSION['ansel_tags_search']) ? $_SESSION['ansel_tags_search'] : array());
        }

        $this->_owner = $owner;
    }

    /**
     * Save the current search to the session
     */
    function save()
    {
        $_SESSION['ansel_tags_search'] = $this->_tags;
        $this->_dirty = false;
    }

    /**
     * Fetch the matching resources that should appear on the current page
     *
     * @return Array of Ansel_Images and Ansel_Galleries
     */
    function getSlice($page, $perpage)
    {
        global $conf, $registry;

        $results = array();
        $totals = $this->count();

        /* First, the galleries */
        $gstart = $page * $perpage;
        $gresults = Ansel_Tags::searchTagsById($this->_tags,
                                               $perpage,
                                               $gstart,
                                               'galleries',
                                               $this->_owner);
        $galleries = array();
        foreach ($gresults['galleries'] as $gallery) {
            $galleries[] = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery);
        }

        /* Do we need to get images? */
        $istart = max(0, $page * $perpage - $totals['galleries']);
        $count = $perpage - count($galleries);
        if ($count > 0) {
            $iresults = Ansel_Tags::searchTagsById($this->_tags,
                                                   $count,
                                                   $istart,
                                                  'images',
                                                   $this->_owner);

            $images = count($iresults['images']) ? array_values($GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImages(array('ids' => $iresults['images']))) : array();
            if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
                $registry->hasMethod('forums/numMessagesBatch')) {

                $ids = array_keys($images);
                $ccounts = $GLOBALS['registry']->call('forums/numMessagesBatch', array($ids, 'ansel'));
                if (!($ccounts instanceof PEAR_Error)) {
                    foreach ($images as $image) {
                        $image->commentCount = (!empty($ccounts[$image->id]) ? $ccounts[$image->id] : 0);
                    }
                }
            }
        } else {
            $images = array();
        }
        return array_merge($galleries, $images);
    }

    /**
     * Add a tag to the cumulative tag search
     */
    function addTag($tag_id)
    {
        if (array_search($tag_id, $this->_tags) === false) {
            $this->_tags[] = $tag_id;
            $this->_dirty = true;
        }
    }

    /**
     * Remove a tag from the cumulative search
     */
    function removeTag($tag_id)
    {
        $key = array_search($tag_id, $this->_tags);
        if ($tag_id === false) {
            return;
        } else {
            unset($this->_tags[$key]);
            $this->_tags = array_values($this->_tags);
            $this->_dirty = true;
        }
    }

    /**
     * Get the list of currently choosen tags
     */
    function getTags()
    {
        return $this->_tags;
    }

    /**
     * Get breadcrumb style navigation html for choosen tags
     *
     */
    function getTagTrail()
    {
        global $registry;

        $tags = Ansel_Tags::getTagNames($this->_tags);
        $html = '<ul class="tag-list">';

        /* Use the local cache to preserve the order */
        $count = 0;
        foreach ($this->_tags as $tagid) {
            $remove_url = Horde::applicationUrl('view.php', true)->add(
                    array('view' => 'Results',
                          'tag' => $tags[$tagid],
                          'actionID' => 'remove'));
            if (!empty($this->_owner)) {
                $remove_url->add('owner', $this->_owner);
            }
            $delete_label = sprintf(_("Remove %s from search"), htmlspecialchars($tags[$tagid]));
            $html .= '<li>' . htmlspecialchars($tags[$tagid]) . $remove_url->link(array('title' => $delete_label)) . Horde::img('delete-small.png', $delete_label) . '</a></li>';
        }

        return $html . '</ul>';
    }

    /**
     * Get the total number of tags included in this search.
     */
    function tagCount()
    {
        return count($this->_tags);
    }

    /**
     * Get the total number of resources that match.
     *
     * @return array  Hash containing totals for both 'galleries' and 'images'.
     */
    function count()
    {
        if (!is_array($this->_tags) || !count($this->_tags)) {
            return 0;
        }

        /* First see if we already calculated for the current page load */
        if ($this->_totalCount && !$this->_dirty) {
            return $this->_totalCount;
        }

        /* Can't perform a COUNT query since we have to check perms */
        $results = Ansel_Tags::searchTagsById($this->_tags, 0, 0, 'all',
                                              $this->_owner);
        $count = array('galleries' => count($results['galleries']), 'images' => count($results['images']));
        $this->_totalCount = $count;
        return $count;
    }

    /**
     * Get a list of tags related to this search
     */
    function getRelatedTags()
    {
        $tags = Ansel_Tags::getRelatedTags($this->getTags());
        /* Make sure that we have actual results for each tag since
         * some tags may exist on only images/galleries to which we
         * have no perms */
        $search = Ansel_Tags::getSearch(null, $this->_owner);
        $results = array();
        foreach ($tags as $id => $tag) {
            $search->addTag($id);
            $count = $search->count();
            if ($count['images'] + $count['galleries'] > 0) {
                $results[$id] = array('tag_name' => $tag['tag_name'], 'total' => $count['images'] + $count['galleries']);
            }
            $search->removeTag($id);
        }

        /* Get the results sorted by available totals for this user */
        uasort($results, array($this, '_sortTagInfo'));
        return $results;
    }

    /**
     */
    function _sortTagInfo($a, $b)
    {
        return $a['total']  <  $b['total'];
    }

}
