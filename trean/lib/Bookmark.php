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
    public $favicon;
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
            if (!empty($bookmark['bookmark_tags'])) {
                $this->tags = $bookmark['bookmark_tags'];
            }
        }
    }

    /**
     * Save bookmark.
     */
    public function save()
    {
        if ($this->id) {
            // Update an existing bookmark.
            $GLOBALS['trean_db']->update('
                UPDATE trean_bookmarks
                SET user_id = ?,
                    bookmark_url = ?,
                    bookmark_title = ?,
                    bookmark_description = ?,
                    bookmark_clicks = ?
                WHERE bookmark_id = ?',
                array(
                    $this->userId,
                    Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    $this->clicks,
                    $this->id,
            ));

            $GLOBALS['injector']->getInstance('Trean_Tagger')->replaceTags((string)$this->id, $this->tags, $GLOBALS['registry']->getAuth(), 'bookmark');
            return;
        }

        if (!strlen($this->url)) {
            throw new Trean_Exception('Incomplete bookmark');
        }

        // Saving a new bookmark.
        $bookmark_id = $GLOBALS['trean_db']->insert('
            INSERT INTO trean_bookmarks
                (user_id, bookmark_url, bookmark_title, bookmark_description, bookmark_clicks)
            VALUES (?, ?, ?, ?, ?)',
            array(
                $this->userId,
                Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                $this->clicks,
        ));

        $this->id = (int)$bookmark_id;
        $GLOBALS['injector']->getInstance('Trean_Tagger')->tag((string)$this->id, $this->tags, $GLOBALS['registry']->getAuth(), 'bookmark');
        return $this->id;
    }
}
