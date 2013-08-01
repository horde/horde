<?php
/**
 * @author  Ben Chavet <ben@horde.org>
 * @package Trean
 */
class Trean_Bookmark
{
    public $id = null;
    public $userId = null;
    public $url = null;
    public $title = '';
    public $description = '';
    public $clicks = 0;
    public $http_status = null;
    public $favicon_url;
    public $dt;
    public $tags = array();

    /**
     */
    public function __construct($bookmark = array())
    {
        if ($bookmark) {
            $this->userId = $bookmark['user_id'];
            $this->url = $bookmark['bookmark_url'];
            $this->title = $bookmark['bookmark_title'];
            $this->description = $bookmark['bookmark_description'];

            if (!empty($bookmark['bookmark_id'])) {
                $this->id = (int)$bookmark['bookmark_id'];
            }
            if (!empty($bookmark['bookmark_clicks'])) {
                $this->clicks = (int)$bookmark['bookmark_clicks'];
            }
            if (!empty($bookmark['bookmark_http_status'])) {
                $this->http_status = $bookmark['bookmark_http_status'];
            }
            if (!empty($bookmark['favicon_url'])) {
                $this->favicon_url = $bookmark['favicon_url'];
            }
            if (!empty($bookmark['bookmark_dt'])) {
                $this->dt = $bookmark['bookmark_dt'];
            }

            if (!empty($bookmark['bookmark_tags'])) {
                $this->tags = $bookmark['bookmark_tags'];
            }
        }
    }

    /**
     * Save bookmark.
     */
    public function save($crawl = true)
    {
        if (!strlen($this->url)) {
            throw new Trean_Exception('Incomplete bookmark');
        }

        $charset = $GLOBALS['trean_db']->getOption('charset');
        $c_url = Horde_String::convertCharset($this->url, 'UTF-8', $charset);
        $c_title = Horde_String::convertCharset($this->title, 'UTF-8', $charset);
        $c_description = Horde_String::convertCharset($this->description, 'UTF-8', $charset);
        $c_favicon_url = Horde_String::convertCharset($this->favicon_url, 'UTF-8', $charset);

        if ($this->id) {
            // Update an existing bookmark.
            $GLOBALS['trean_db']->update('
                UPDATE trean_bookmarks
                SET user_id = ?,
                    bookmark_url = ?,
                    bookmark_title = ?,
                    bookmark_description = ?,
                    bookmark_clicks = ?,
                    bookmark_http_status = ?,
                    favicon_url = ?
                WHERE bookmark_id = ?',
                array(
                    $this->userId,
                    $c_url,
                    $c_title,
                    $c_description,
                    $this->clicks,
                    $this->http_status,
                    $c_favicon_url,
                    $this->id,
            ));

            $GLOBALS['injector']->getInstance('Trean_Tagger')->replaceTags((string)$this->id, $this->tags, $GLOBALS['registry']->getAuth(), 'bookmark');
        } else {
            // Saving a new bookmark.
            $bookmark_id = $GLOBALS['trean_db']->insert('
                INSERT INTO trean_bookmarks (
                    user_id,
                    bookmark_url,
                    bookmark_title,
                    bookmark_description,
                    bookmark_clicks,
                    bookmark_http_status,
                    favicon_url,
                    bookmark_dt
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    $this->userId,
                    $c_url,
                    $c_title,
                    $c_description,
                    $this->clicks,
                    $this->http_status,
                    $c_favicon_url,
                    $this->dt,
            ));

            $this->id = (int)$bookmark_id;
            $GLOBALS['injector']->getInstance('Trean_Tagger')->tag((string)$this->id, $this->tags, $GLOBALS['registry']->getAuth(), 'bookmark');
        }

        if ($crawl) {
            try {
                $queue = $GLOBALS['injector']->getInstance('Horde_Queue_Storage');
                $queue->add(new Trean_Queue_Task_Crawl(
                    $this->url,
                    $this->title,
                    $this->description,
                    $this->id,
                    $this->userId
                ));
            } catch (Exception $e) {
                Horde::log($e, 'INFO');
            }
        }

        return $this->id;
    }
}
