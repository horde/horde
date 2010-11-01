<?php
/**
 * Trean external API interface.
 *
 * This file defines Trean's external API interface. Other
 * applications can interact with Trean through this API.
 *
 * $Horde: trean/lib/Api.php,v 1.2 2009-11-29 15:52:17 chuck Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */
class Trean_Api extends Horde_Registry_Api
{
    /**
     * Gets all of the folders that are a subfolder of the given folder
     * (or the root.)
     *
     * @param integer $folderId     the ID of the folder, or -1 for the root
     * @return array    Array of associative arrays (XMLRPC structs) with
     *                  'id' as the folder's ID, and 'name' as its name.
     */
    public function getFolders($folderId)
    {
        if ($folderId == -1) {
            $folder = null;
        } else {
            $folder = $GLOBALS['trean_shares']->getFolder($folderId);
        }
        if ($folder && is_a($folder, 'PEAR_Error')) {
            return $folder;
        }
        $folderObs = Trean::listFolders(Horde_Perms::SHOW,
                                        $folder ? $folder->getName() : null, false);
        if (is_a($folderObs, 'PEAR_Error')) {
            return $folderObs;
        }

        $folders = array();
        foreach ($folderObs as $folder) {
            $folders[] = array('id' => $folder->getId(),
                            'name' => $folder->get('name'));
        }
        return $folders;
    }

    /**
     * Adds a bookmark folder
     *
     * @param array $data   Object data
     */
    public function addObjects($data)
    {
        $return_map = array();

        foreach ($data as $props) {
            $children = null;

            if (!isset($props['folder_id']) || !isset($props['return_key'])) {
                return new PEAR_Error('must specify folder_id and return_key properties');
            }

            $return_key = $props['return_key'];
            unset($props['return_key']);

            if (isset($props['name'])) {
                $parentFolder = $GLOBALS['trean_shares']->getFolder($props['folder_id']);
                unset($props['folder_id']);
                if (isset($props['child_objects'])) {
                    $children = $props['child_objects'];
                    unset($props['child_objects']);
                }
                $ret = $parentFolder->addFolder($props);
            } else {
                if (!isset($props['bookmark_description'])) {
                    $props['bookmark_description'] = '';
                }
                $bookmark = new Trean_Bookmark($props);
                $ret = $bookmark->save();
            }

            if (is_a($ret, 'PEAR_Error')) {
                return $ret;
            }

            $return_map[$return_key] = $ret;

            if ($children) {
                $id = $ret; // $ret = folder ID
                foreach ($children as $key => $child) {
                    $children[$key]['folder_id'] = $id;
                }
                $ret = addObjects($children);
                if (is_a($ret, 'PEAR_Error')) {
                    return $ret;
                }
                $return_map = array_merge($return_map, $ret);
            }
        }

        return $return_map;
    }

    /**
     * Updates a bookmark folder
     *
     * @param array $data   Object data
     */
    public function updateObjects($data)
    {
        foreach ($data as $props) {
            if (isset($props['bookmark_id'])) {
                $obj = $GLOBALS['trean_shares']->getBookmark($props['bookmark_id']);
            } else if (isset($props['folder_id'])) {
                $obj = $GLOBALS['trean_shares']->getFolder($props['folder_id']);
            } else {
                $obj = new PEAR_Error("each inner associative array must have a (bookmark|folder)id key");
            }
            if (is_a($obj, 'PEAR_Error')) {
                return $obj;
            }
            foreach ($props as $name => $value) {
                if ($name == 'id') {
                    continue;
                }
                if (is_a($obj, 'Trean_Bookmark')) {
                    $obj->$name = $value;
                } else {
                    if ($name == 'folder') {
                        $ret = $GLOBALS['trean_shares']->move($obj,
                        $GLOBALS['trean_shares']->getFolder($value));
                    } else {
                        $ret = $obj->set($name, $value);
                    }
                    if (is_a($ret, 'PEAR_Error')) {
                        return $ret;
                    }
                }
            }
            $obj->save();
        }

        return true;
    }

    /**
     * Returns all the bookmarks in a given folder, sorted and "paginated."
     *
     * @param integer $folderId   the ID of a folder, or -1 for root
     * @param string  $sortby     field to sort by
     * @param integer $sortdir    direction to sort by (non-0 for descending)
     * @param integer $from       bookmark to start from
     * @param integer $count      how many bookmarks to return
     * @return array  An array of associative arrays (XMLRPC structs) representing
     * the bookmarks.
     * @see DataTreeObject_Folder->listBookmarks()
     */
    public function listBookmarks($folderId, $sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        $folder = $GLOBALS['trean_shares']->getFolder($folderId);
        if (is_a($folder, 'PEAR_Error')) {
            return $folder;
        }
        return $folder->listBookmarks($sortby, $sortdir, $from, $count);
    }

    /**
     * Delete a given folder.
     *
     * @param integer $folderId   the ID of the folder
     * @param boolean $force      Force-remove child objects? (currently ignored)
     *
     * @return boolean  True for success.
     */
    public function deleteFolder($folderId, $force)
    {
        $folder = $GLOBALS['trean_shares']->getFolder($folderId);
        if (is_a($folder, 'PEAR_Error')) {
            return $folder;
        }
        $result = $folder->delete();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        return true;
    }

    /**
     * Delete multiple folders.
     *
     * @param array $Ids   The IDs of the folders to delete
     *
     * @return boolean  True for success.
     */
    public function deleteFolders($Ids)
    {
        $Ids = array_reverse($Ids);
        foreach ($Ids as $Id) {
            $ret = deleteFolder($Id, true);
            if (is_a($ret, 'PEAR_Error')) {
                return $ret;
            }
        }

        return true;
    }

    /**
     * Delete a given bookmark.
     *
     * @param integer   $bookmarkId the ID of the bookmark to delete
     *
     * @return boolean  True for success.
     */
    public function deleteBookmark($bookmarkId)
    {
        $result = $GLOBALS['trean_shares']->removeBookmark($bookmarkId);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        return true;
    }

    /**
     * Delete multiple bookmarks.
     *
     * @param array $Ids   the IDs of the bookmarks to delete
     *
     * @return boolean  True for success.
     */
    public function deleteBookmarks($Ids)
    {
        foreach ($Ids as $Id) {
            $ret = deleteBookmark($Id);
            if (is_a($ret, 'PEAR_Error')) {
                return $ret;
            }
        }

        return true;
    }

    /**
     * Synchronize bookmarks in a folder.  Send a list of IDs of bookmarks you
     * know about, get an array of new bookmarks and placeholders for bookmarks
     * you have that are now deleted.
     *
     * @param integer folderId     the ID of the folder, or -1 for root
     * @param array   bookmarkIds  integer array of the bookmark IDs to sync against
     * @return array  An array of associative arrays (XMLRPC structs) of all the
     * newly created bookmarks' data, or for deleted bookmarks, a placeholder with
     * 'id' as the ID of the bookmark and 'sync_deleted' as true.
     * @see listBookmarks()
     */
    public function syncBookmarks($folderId, $bookmarkIds)
    {
        $rawbookmarks = listBookmarks($folderId);
        if (is_a($rawbookmarks, 'PEAR_Error')) {
            return $rawbookmarks;
        }
        $bookmarks = array();
        foreach ($rawbookmarks as $bookmark) {
            $bookmarks[$bookmark->id] = $bookmark;
        }
        // We're authoritative, so we prune out any bookmarks the client
        // already knows about by ID, and let them know of the deletion of any we
        // don't know about
        foreach ($bookmarkIds as $id) {
            if (isset($bookmarks[$id])) {
                unset($bookmarks[$id]);
            } else {
                $bookmarks[$id] = array('id' => $id, 'sync_deleted' => true);
            }
        }

        // We should be left with a list of new bookmarks with their full details,
        // and deleted bookmarks with only an id and 'sync_deleted' boolean
        return array_values($bookmarks);
    }

    /**
     * Synchronize folders in a folder.  Send a list of IDs, get a list of new
     * folders and placeholders for deleted ones.  See syncBookmarks()
     * for more details.
     *
     * @param integer folderID   the ID of the folder, or -1 for root
     * @param array   folderIds  integer array of folder IDs to sync against
     * @return array  An array of associate arrays (XMLRPC structs) of all
     * the newly created folders' data, or placeholders for deleted folders.
     * @see getFolders()
     * @see syncBookmarks()
     */
    public function syncFolders($folderId, $folderIds)
    {
        $rawfolders = getFolders($folderId);
        if (is_a($rawfolders, 'PEAR_Error')) {
            return $rawfolders;
        }
        $folders = array();
        foreach ($rawfolders as $folder) {
            $folders[$folder['id']] = $folder;
        }

        // This works like the sync for bookmarks
        foreach ($folderIds as $id) {
            if (isset($folders[$id])) {
                unset($folders[$id]);
            } else {
                $folders[$id] = array('id' => $id, 'sync_deleted' => true);
            }
        }

        return array_values($folders);
    }

    /**
     * Returns a URL that can be used in other applications to add the currently
     * displayed page as a bookmark.  If javascript and DOM is available, an overlay
     * is used, if javascript and no DOM, then a pop-up is used and if no javascript
     * is available a URL to Trean's add.php page is returned.
     *
     * @param array $params  A hash of 'url' and 'title' properties of the requested
     *                       bookmark.
     * @return string  The URL suitable for use in a <a> tag.
     */
    public function getAddUrl($params = array())
    {
        $GLOBALS['no_compress'] = true;

        $browser = $GLOBALS['injector']->getInstance('Horde_Browser');
        if ($browser->hasFeature('javascript')) {
            if ($browser->hasFeature('dom')) {
                $addurl = Horde_Util::addParameter(Horde::url('add.php', true, -1), 'iframe', 1);
                $url = "javascript:(function(){o=document.createElement('div');o.id='overlay';o.style.background='#000';o.style.position='absolute';o.style.top=0;o.style.left=0;o.style.width='100%';o.style.height='100%';o.style.zIndex=5000;o.style.opacity=.8;document.body.appendChild(o);i=document.createElement('iframe');i.id='frame';i.style.zIndex=5001;i.style.border='thin solid #000';i.src='$addurl'+'&title=' + encodeURIComponent(document.title) + '&url=' + encodeURIComponent(location.href);i.style.position='absolute';i.style.width='350px';i.style.height='150px';i.style.left='100px';i.style.top='100px';document.body.appendChild(i);l=document.createElement('a');l.style.position='absolute';l.style.background='#ccc';l.style.color='#000';l.style.border='thin solid #000';l.style.display='block';l.style.top='250px';l.style.left='100px';l.style.zIndex=5001;l.style.padding='5px';l.appendChild(document.createTextNode('" . _("Close") . "'));l.onclick=function(){var o=document.getElementById('overlay');o.parentNode.removeChild(o);var i=document.getElementById('frame');i.parentNode.removeChild(i);this.parentNode.removeChild(this);};document.body.appendChild(l);})()";
            } else {
                $addurl = Horde::url(Horde_Util::addParameter('add.php', 'popup', 1), true, -1);
                $url = "javascript:d = new Date(); w = window.open('$addurl' + '&amp;title=' + encodeURIComponent(document.title) + '&amp;url=' + encodeURIComponent(location.href) + '&amp;d=' + d.getTime(), d.getTime(), 'height=200,width=400'); w.focus();";
            }
        } else {
            // Fallback to a regular URL
            $url = Horde::url(Horde_Util::addParameter('add.php', $params), true);
        }

        return $url;
    }
}
