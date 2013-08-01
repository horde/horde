<?php
/**
 * Trean external API interface.
 *
 * This file defines Trean's external API interface. Other
 * applications can interact with Trean through this API.
 *
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */
class Trean_Api extends Horde_Registry_Api
{
    /**
     * Delete a given bookmark.
     *
     * @param integer $bookmarkId  The ID of the bookmark to delete
     */
    public function deleteBookmark($bookmarkId)
    {
        $bookmark = $GLOBALS['trean_gateway']->getBookmark($bookmarkId);
        $GLOBALS['trean_gateway']->removeBookmark($bookmark);
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
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags   An optional array of tag_ids. If omitted, all tags
     *                      will be included.
     * @param string $user  Restrict result to those tagged by $user.
     *
     * @return array  An array containing tag_name, and total
     */
    public function listTagInfo($tags = null, $user = null)
    {
        return $GLOBALS['injector']
            ->getInstance('Trean_Tagger')->getTagInfo($tags, 500, null, $user);
    }

    /**
     * SearchTags API:
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications)
     *
     * The 'raw' results array can be returned instead by setting $raw = true.
     *
     * @param array $names           An array of tag_names to search for.
     * @param integer $max           The maximum number of resources to return.
     * @param integer $from          The number of the resource to start with.
     * @param string $resource_type  The resource type [bookmark, '']
     * @param string $user           Restrict results to resources owned by $user.
     * @param boolean $raw           Return the raw data?
     *
     * @return array An array of results:
     * <pre>
     *  'title'    - The title for this resource.
     *  'desc'     - A terse description of this resource.
     *  'view_url' - The URL to view this resource.
     *  'app'      - The Horde application this resource belongs to.
     * </pre>
     */
    public function searchTags($names, $max = 10, $from = 0,
                               $resource_type = '', $user = null, $raw = false)
    {
        // TODO: $max, $from, $resource_type not honored

        $results = $GLOBALS['injector']
            ->getInstance('Trean_Tagger')
            ->search(
                $names,
                array('type' => 'bookmark', 'user' => $user));

        // Check for error or if we requested the raw data array.
        if ($raw) {
            return $results;
        }

        $return = array();
        $redirectUrl = Horde::url('redirect.php');
        foreach ($results as $bookmark_id) {
            try {
                $bookmark = $GLOBALS['trean_gateway']->getBookmark($bookmark_id);
                $return[] = array(
                    'title' => $bookmark->title,
                    'desc' => $bookmark->description,
                    'view_url' => $redirectUrl->add('b', $bookmark->id),
                    'app' => 'trean',
                );
            } catch (Exception $e) {
            }
        }

        return $return;
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
