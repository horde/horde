<?php
/**
 * @author  Ben Chavet <ben@horde.org>
 * @package Trean
 */
class Trean_Bookmark
{
    var $id = null;
    var $url = null;
    var $title = '';
    var $description = '';
    var $clicks = 0;
    var $rating = 0;
    var $http_status = null;
    var $folder;
    var $favicon;

    function Trean_Bookmark($bookmark = array())
    {
        if ($bookmark) {
            $this->url = $bookmark['bookmark_url'];
            $this->title = $bookmark['bookmark_title'];
            $this->description = $bookmark['bookmark_description'];
            $this->folder = $bookmark['folder_id'];

            if (!empty($bookmark['bookmark_id'])) {
                $this->id = (int)$bookmark['bookmark_id'];
            }
            if (!empty($bookmark['bookmark_clicks'])) {
                $this->clicks = (int)$bookmark['bookmark_clicks'];
            }
            if (!empty($bookmark['bookmark_rating'])) {
                $this->rating = (int)$bookmark['bookmark_rating'];
            }
            if (!empty($bookmark['bookmark_http_status'])) {
                $this->http_status = $bookmark['bookmark_http_status'];
            }
        }
    }

    /**
     * Save bookmark.
     */
    function save()
    {
        if ($this->id) {
            // Update an existing bookmark.
            return $GLOBALS['trean_db']->update('
                UPDATE trean_bookmarks
                SET folder_id = ?,
                    bookmark_url = ?,
                    bookmark_title = ?,
                    bookmark_description = ?,
                    bookmark_clicks = ?,
                    bookmark_rating = ?
                WHERE bookmark_id = ?',
                array(
                    $this->folder,
                    Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                    $this->clicks,
                    $this->rating,
                    $this->id,
            ));
        }

        if (!$this->folder || !strlen($this->url)) {
            return PEAR::raiseError('Incomplete bookmark');
        }

        // Saving a new bookmark.
        $bookmark_id = $GLOBALS['trean_db']->insert('
            INSERT INTO trean_bookmarks
                (folder_id, bookmark_url, bookmark_title, bookmark_description,
                 bookmark_clicks, bookmark_rating)
            VALUES (?, ?, ?, ?, ?, ?)',
            array(
                $this->folder,
                Horde_String::convertCharset($this->url, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->title, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                Horde_String::convertCharset($this->description, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                $this->clicks,
                $this->rating,
        ));

        $this->id = (int)$bookmark_id;
        return $this->id;
    }
}
