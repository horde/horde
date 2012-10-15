<?php
/**
 * Trean external API interface.
 *
 * This file defines Trean's external API interface. Other
 * applications can interact with Trean through this API.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */
class Trean_Api extends Horde_Registry_Api
{
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
     */
    public function listBookmarks($folderId, $sortby = 'title', $sortdir = 0, $from = 0, $count = 0)
    {
        $folder = $GLOBALS['trean_shares']->getFolder($folderId);
        return $folder->listBookmarks($sortby, $sortdir, $from, $count);
    }

    /**
     * Delete a given bookmark.
     *
     * @param integer $bookmarkId  The ID of the bookmark to delete
     */
    public function deleteBookmark($bookmarkId)
    {
        $GLOBALS['trean_shares']->removeBookmark($bookmarkId);
    }

    /**
     * Delete multiple bookmarks.
     *
     * @param array $bookmarkIds  The IDs of the bookmarks to delete
     */
    public function deleteBookmarks($bookmarkIds)
    {
        foreach ($bookmarkIds as $bookmarkId) {
            $this->deleteBookmark($bookmarkId);
        }
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
                $addurl = Horde::url('add.php', true, -1)->add('iframe', 1);
                $url = "javascript:(function(){o=document.createElement('div');o.id='overlay';o.style.background='#000';o.style.position='absolute';o.style.top=0;o.style.left=0;o.style.width='100%';o.style.height='100%';o.style.zIndex=5000;o.style.opacity=.8;document.body.appendChild(o);i=document.createElement('iframe');i.id='frame';i.style.zIndex=5001;i.style.border='thin solid #000';i.src='$addurl'+'&title=' + encodeURIComponent(document.title) + '&url=' + encodeURIComponent(location.href);i.style.position='absolute';i.style.width='350px';i.style.height='150px';i.style.left='100px';i.style.top='100px';document.body.appendChild(i);l=document.createElement('a');l.style.position='absolute';l.style.background='#ccc';l.style.color='#000';l.style.border='thin solid #000';l.style.display='block';l.style.top='250px';l.style.left='100px';l.style.zIndex=5001;l.style.padding='5px';l.appendChild(document.createTextNode('" . _("Close") . "'));l.onclick=function(){var o=document.getElementById('overlay');o.parentNode.removeChild(o);var i=document.getElementById('frame');i.parentNode.removeChild(i);this.parentNode.removeChild(this);};document.body.appendChild(l);})()";
            } else {
                $addurl = Horde::url('add.php', true, -1)->add('popup', 1);
                $url = "javascript:d = new Date(); w = window.open('$addurl' + '&amp;title=' + encodeURIComponent(document.title) + '&amp;url=' + encodeURIComponent(location.href) + '&amp;d=' + d.getTime(), d.getTime(), 'height=200,width=400'); w.focus();";
            }
        } else {
            // Fallback to a regular URL
            $url = Horde::url('add.php', true)->add($params);
        }

        return $url;
    }
}
