<?php
/**
 * Jonah storage implementation for PHP's database abstraction layer.
 *
 * The table structure can be created using Horde's db_migrate script.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Ian Roth <iron_hat@hotmail.com>
 * @package Jonah
 */
class Jonah_Driver_Sql extends Jonah_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->initialize();
    }

    /**
     * Saves a channel to the backend.
     *
     * @param array $info  The channel to add.
     *                     Must contain a combination of the following
     *                     entries:
     * <pre>
     * 'channel_id'       If empty a new channel is being added, otherwise one
     *                    is being edited.
     * 'channel_slug'     The channel slug.
     * 'channel_name'     The headline.
     * 'channel_desc'     A description of this channel.
     * 'channel_type'     Whether internal or external.
     * 'channel_interval' If external then interval at which to refresh.
     * 'channel_link'     The link to the source.
     * 'channel_url'      The url from where to fetch the story list.
     * 'channel_image'    A channel image.
     * </pre>
     *
     * @return integer The channel ID.
     * @throws Jonah_Exception
     */
    public function saveChannel(&$info)
    {
        $values = array(Horde_String::convertCharset($info['channel_slug'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($info['channel_name'], 'UTF-8', $this->_params['charset']),
                        (int)$info['channel_type'],
                        isset($info['channel_desc']) ? $info['channel_desc'] : null,
                        isset($info['channel_interval']) ? (int)$info['channel_interval'] : null,
                        isset($info['channel_url']) ? $info['channel_url'] : null,
                        isset($info['channel_link']) ? $info['channel_link'] : null,
                        isset($info['channel_page_link']) ? $info['channel_page_link'] : null,
                        isset($info['channel_story_url']) ? $info['channel_story_url'] : null,
                        isset($info['channel_img']) ? $info['channel_img'] : null);
        if (empty($info['channel_id'])) {
            $sql = 'INSERT INTO jonah_channels' .
                   ' (channel_slug, channel_name, channel_type, channel_desc, channel_interval, channel_url, channel_link, channel_page_link, channel_story_url, channel_img)' .
                   ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            Horde::logMessage('SQL Query by Jonah_Driver_sql::saveChannel(): ' . $sql, 'DEBUG');
            try {
                $info['channel_id'] = $this->_db->insert($sql, $values);
            } catch(Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        } else {
            $values[] = (int)$info['channel_id'];
            $sql = 'UPDATE jonah_channels' .
                   ' SET channel_slug = ?, channel_name = ?, channel_type = ?, channel_desc = ?, channel_interval = ?, channel_url = ?, channel_link = ?, channel_page_link = ?, channel_story_url = ?, channel_img = ?' .
                   ' WHERE channel_id = ?';
            Horde::logMessage('SQL Query by Jonah_Driver_sql::saveChannel(): ' . $sql, 'DEBUG');
            try {
                $results = $this->_db->update($sql, $values);
            } catch(Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        }

        return $info['channel_id'];
    }

    /**
     * Get a list of stored channels.
     *
     * @return mixed         An array of channels.
     * @throws Jonah_Exception
     */
    public function getChannels()
    {
        // @TODO: Remove channel_type filter when tables are updated.
        $sql = 'SELECT channel_id, channel_name, channel_type, channel_updated FROM jonah_channels WHERE channel_type = ' . Jonah::INTERNAL_CHANNEL . ' ORDER BY channel_name';
        Horde::logMessage('SQL Query by Jonah_Driver_sql::getChannels(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->selectAll($sql);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['channel_name'] = Horde_String::convertCharset($result[$i]['channel_name'], $this->_params['charset'], 'UTF-8');
        }

        return $result;
    }

    /**
     * Retrieve a single channel definition from storage.
     *
     * @return array  The channel definition array.
     * @throws Jonah_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _getChannel($channel_id)
    {
        $sql = 'SELECT * FROM jonah_channels WHERE channel_id = ' . (int)$channel_id;

        Horde::logMessage('SQL Query by Jonah_Driver_sql::_getChannel(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->selectOne($sql, DB_FETCHMODE_ASSOC);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        if (empty($result)) {
            throw new Horde_Exception_NotFound(sprintf(_("Channel id \"%s\" not found."), $channel_id));
        }

        return $result;
    }

    /**
     * Update the channel's timestamp
     *
     * @param integer $channel_id  The channel id.
     * @param integer $timestamp   The new timestamp.
     *
     * @return boolean
     * @throws Jonah_Exception
     */
    protected function _timestampChannel($channel_id, $timestamp)
    {
        $sql = sprintf('UPDATE jonah_channels SET channel_updated = %s WHERE channel_id = %s',
                       (int)$timestamp,
                       (int)$channel_id);
        Horde::logMessage('SQL Query by Jonah_Driver_sql::_timestampChannel(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->update($sql);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Increment the story's read count.
     *
     * @param integer $story_id  The story_id to increment.
     * @throws Jonah_Exception
     */
    protected function _readStory($story_id)
    {
        $sql = 'UPDATE jonah_stories SET story_read = story_read + 1 WHERE story_id = ' . (int)$story_id;
        Horde::logMessage('SQL Query by Jonah_Driver_sql::_readStory(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->update($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Remove a channel from storage.
     *
     * @param integer $channel_id  The channel to remove.
     *
     * @return boolean.
     * @throws Jonah_Exception
     *
     */
    protected function _deleteChannel($channel_id)
    {
        $sql = 'DELETE FROM jonah_channels WHERE channel_id = ?';
        $values = array($channel_id);

        Horde::logMessage('SQL Query by Jonah_Driver_sql::deleteChannel(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->delete($sql, $values);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Save a story to storage.
     *
     * @param array &$info  The story info array.
     * @throws Jonah_Exception
     * @return Integer      Id of story
     */
    protected function _saveStory(&$info)
    {
        if (empty($info['read'])) {
            $info['read'] = 0;
        }

        $values = array((int)$info['channel_id'],
                        Horde_String::convertCharset($info['author'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($info['title'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($info['description'], 'UTF-8', $this->_params['charset']),
                        $info['body_type'],
                        isset($info['body']) ? Horde_String::convertCharset($info['body'], 'UTF-8', $this->_params['charset']) : null,
                        isset($info['url']) ? $info['url'] : null,
                        isset($info['published']) ? (int)$info['published'] : null,
                        time(),
                        (int)$info['read']);
        if (empty($info['id'])) {
            $channel = $this->getChannel($info['channel_id']);
            $permalink = $this->getStoryLink($channel,$info);
            $values[] = $permalink;
            $sql = 'INSERT INTO jonah_stories (channel_id, story_author, story_title, story_desc, story_body_type, story_body, story_url, story_published, story_updated, story_read, story_permalink) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            Horde::logMessage('SQL Query by Jonah_Driver_sql::_saveStory(): ' . $sql, 'DEBUG');
            try {
                $info['id'] = $this->_db->insert($sql, $values);
            } catch(Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        } else {
            $values[] = (int)$info['id'];
            $sql = 'UPDATE jonah_stories SET channel_id = ?, story_author = ?, story_title = ?, story_desc = ?, story_body_type = ?, story_body = ?, story_url = ?, story_published = ?, story_updated = ?, story_read = ? WHERE story_id = ?';
            Horde::logMessage('SQL Query by Jonah_Driver_sql::_saveStory(): ' . $sql, 'DEBUG');
            try {
                $result = $this->_db->update($sql, $values);
            } catch(Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        }

        $this->_timestampChannel($info['channel_id'], time());

        return $info['id'];
    }

    /**
     * Converts the text fields of a story from the backend charset to the
     * output charset.
     *
     * @param array $story  A story hash.
     *
     * @return array  The converted hash.
     */
    protected function _convertFromBackend($story)
    {
        $story['title'] = Horde_String::convertCharset($story['title'], $this->_params['charset'], 'UTF-8');
        $story['description'] = Horde_String::convertCharset($story['description'], $this->_params['charset'], 'UTF-8');
        if (isset($story['body'])) {
            $story['body'] = Horde_String::convertCharset($story['body'], $this->_params['charset'], 'UTF-8');
        }

        return $story;
    }

    /**
     * Look up a channel ID by its name
     *
     * @param string $channel
     *
     * @return int Channel ID
     */
    public function getChannelId($channel)
    {
        $sql = 'SELECT channel_id FROM jonah_channels WHERE channel_slug = ?';
        $values = array($channel);
        try {
            $result = $this->_db->selectOne($sql, $values);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Returns the total number of stories in the specified channel.
     *
     * @param integer $channel_id  The Channel Id
     *
     * @return integer  The count
     */
    public function getStoryCount($channel_id)
    {
        $sql = 'SELECT count(*) FROM jonah_stories WHERE channel_id = ?';
        try {
            $result = $this->_db->SelectOne($sql, $channel_id);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return (int)$result;
    }

    /**
     * Returns a list of stories from the storage backend filtered by
     * arbitrary criteria.
     * NOTE: $criteria['channel_id'] MUST be set for this method to work.
     *
     * @param array $criteria
     *
     * @return array
     *
     * @see Jonah_Driver#getStories
     */
    protected function _getStories($criteria, $order = Jonah::ORDER_PUBLISHED)
    {
        $sql = 'SELECT DISTINCT(stories.story_id) AS id, ' .
           'stories.channel_id, ' .
           'stories.story_author AS author, ' .
           'stories.story_title AS title, ' .
           'stories.story_desc AS description, ' .
           'stories.story_body_type AS body_type, ' .
           'stories.story_body AS body, ' .
           'stories.story_url AS url, ' .
           'stories.story_permalink AS permalink, ' .
           'stories.story_published AS published, ' .
           'stories.story_updated AS updated, ' .
           'stories.story_read AS readcount ' .
           'FROM jonah_stories AS stories ' .
           'WHERE stories.channel_id=?';

        $values = array($criteria['channel_id']);

        // Apply date filtering
        if (isset($criteria['updated-min'])) {
            $sql .= ' AND story_updated >= ?';
            $values[] = $criteria['updated-min']->timestamp();
        }
        if (isset($criteria['updated-max'])) {
            $sql .= ' AND story_updated <= ?';
            $values[] = $criteria['updated-max']->timestamp();
        }
        if (isset($criteria['published-min'])) {
            $sql .= ' AND story_published >= ?';
            $values[] = $criteria['published-min']->timestamp();
        }
        if (isset($criteria['published-max'])) {
            $sql .= ' AND story_published <= ?';
            $values[] = $criteria['published-max']->timestamp();
        }
        if (isset($criteria['published'])) {
            $sql .= ' AND story_published IS NOT NULL';
        }

        // Filter by story author
        if (isset($criteria['author'])) {
            $sql .= ' AND stories.story_author = ?';
            $values[] = $criteria['author'];
        }

        // Filter stories by keyword
        if (isset($criteria['keywords'])) {
            foreach ($criteria['keywords'] as $keyword) {
                $sql .= ' AND stories.story_body LIKE ?';
                $values[] = '%' . $keyword . '%';
            }
        }
        if (isset($criteria['notkeywords'])) {
            foreach ($criteria['notkeywords'] as $keyword) {
                $sql .= ' AND stories.story_body NOT LIKE ?';
                $values[] = '%' . $keyword . '%';
            }
        }

        switch ($order) {
        case Jonah::ORDER_PUBLISHED:
            $sql .= ' ORDER BY published DESC';
            break;
        case Jonah::ORDER_READ:
            $sql .= ' ORDER BY readcount DESC';
            break;
        case Jonah::ORDER_COMMENTS:
            //@TODO
            break;
        }
        $limit = 0;
        $start = 0;
        if (isset($criteria['limit'])) {
            $limit = $criteria['limit'];
        }
        if (isset($criteria['startnumber']) && isset($criteria['endnumber'])) {
            $limit = min($criteria['endnumber'] - $criteria['startnumber'], $criteria['limit']);
            $start = $criteria['startnumber'];
        }
        if ($limit || $start != 0) {
            $sql .= ' LIMIT ' . $start . ',' . $limit;
        } 

        Horde::logMessage('SQL Query by Jonah_Driver_sql::_getStories(): ' . $sql, 'DEBUG');
        try {
            $results = $this->_db->selectAll($sql, $values);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $results;
    }

    /**
     * Obtain a channel id from a slug
     *
     * @param string $slug  The slug to search for.
     *
     * @return integer  The channel id.
     */
    protected function _getIdBySlug($slug)
    {
        // @TODO
        throw new Jonah_Exception('Not implemented yet.');
    }

    /**
     * Retrieve a story from storage.
     *
     * @param integer $story_id  They story id.
     * @param boolean $read      Increment the read counter?
     *
     * @return The story array.
     * @throws Horde_Exception_NotFound
     * @throws Jonah_Exception
     *
     */
    protected function _getStory($story_id, $read = false)
    {
        $sql = 'SELECT stories.story_id as id, ' .
           'stories.channel_id, ' .
           'stories.story_author AS author, ' .
           'stories.story_title AS title, ' .
           'stories.story_desc AS description, ' .
           'stories.story_body_type AS body_type, ' .
           'stories.story_body AS body, ' .
           'stories.story_url AS url, ' .
           'stories.story_permalink AS permalink, ' .
           'stories.story_published AS published, ' .
           'stories.story_updated AS updated, ' .
           'stories.story_read AS readcount ' .
           'FROM jonah_stories AS stories WHERE stories.story_id=?';

        $values = array((int)$story_id);

        Horde::logMessage('SQL Query by Jonah_Driver_sql::_getStory(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->selectOne($sql, $values, DB_FETCHMODE_ASSOC);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        if (empty($result)) {
            throw new Horde_Exception_NotFound(sprintf(_("Story id \"%s\" not found."), $story_id));
        }
        $result = $this->_convertFromBackend($result);
        if ($read) {
            $this->_readStory($story_id);
        }

        return $result;
    }

    /**
     * Adds a missing permalink to a story.
     *
     * @param array $story  A story hash.
     * @throws Jonah_Exception
     */
    protected function _addPermalink(&$story)
    {
        $channel = $this->getChannel($story['channel_id']);
        $sql = 'UPDATE jonah_stories SET story_permalink = ? WHERE story_id = ?';
        $values = array($this->getStoryLink($channel, $story), $story['id']);
        Horde::logMessage('SQL Query by Jonah_Driver_sql::_addPermalink(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->update($sql, $values);
        } catch(Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        $story['permalink'] = $values[0];
    }

    /**
     * Gets the latest released story from a given internal channel
     *
     * @param int $channel_id  The channel id.
     *
     * @return int  The story id.
     * @throws Jonah_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getLatestStoryId($channel_id)
    {
        $sql = 'SELECT story_id FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_published <= ?' .
               ' ORDER BY story_updated DESC';
        $values = array((int)$channel_id, time());

        Horde::logMessage('SQL Query by Jonah_Driver_sql::getLatestStoryId(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->selectRow($sql, $values, DB_FETCHMODE_ASSOC);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        if (empty($result)) {
            return Horde_Exception_NotFound(sprintf(_("Channel \"%s\" not found."), $channel_id));
        }

        return $result['story_id'];
    }

    /**
     */
    public function deleteStory($channel_id, $story_id)
    {
        $sql = 'DELETE FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_id = ?';
        $values = array((int)$channel_id, (int)$story_id);

        Horde::logMessage('SQL Query by Jonah_Driver_sql::deleteStory(): ' . $sql, 'DEBUG');
        try {
            $result = $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {}

        return true;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean    True on success.
     */
    protected function initialize()
    {
        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('jonah', 'storage');

        return true;
    }

}
