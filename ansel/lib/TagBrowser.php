<?php
/**
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * Ansel_TagBrowser:: class provides logic for dealing with tag browsing.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_TagBrowser extends Horde_Core_TagBrowser
{
    protected $_app = 'ansel';

    /**
     * Get breadcrumb style navigation html for choosen tags
     *
     * @return string  HTML necessary for displaying the tag trail.
     */
    public function getTagTrail()
    {
        global $registry;

        $html = '<ul class="horde-tags">';

        // Use the local cache to preserve the order
        $count = 0;
        foreach ($this->_tags as $tagname => $tagid) {
            $remove_url = Horde::url('view.php', true)->add(
                    array('view' => 'Results',
                          'tag' => $tagname,
                          'actionID' => 'remove'));
            if (!empty($this->_owner)) {
                $remove_url->add('owner', $this->_owner);
            }
            $delete_label = sprintf(_("Remove %s from search"), htmlspecialchars($tagname));
            $html .= '<li>' . htmlspecialchars($tagname) . $remove_url->link(array('title' => $delete_label)) . Horde::img('delete-small.png', $delete_label) . '</a></li>';
        }

        return $html . '</ul>';
    }

    /**
     * Override parent method to allow counts for both galleries and images.
     *
     * @return array
     */
    public function count()
    {
        if (!is_array($this->_tags) || !count($this->_tags)) {
            return 0;
        }

        $count = array(
            'galleries' => count($this->_results['galleries']),
            'images' => count($this->_results['images']));

        $this->_totalCount = $count;

        return $count;
    }

    /**
     * Fetch the matching resources that should appear on the current page
     *
     * @return Array  An array of Trean_Bookmark objects.
     */
    public function getSlice($page = 0, $perpage = null)
    {
        global $injector, $conf;

        // Refresh the search
        $this->runSearch();
        $totals = $this->count();

        // Galleries first.
        $gstart = $page * $perpage;
        $gresults = array_slice($this->_results['galleries'], $gstart, $perpage);

        $galleries = array();
        foreach ($gresults as $gid) {
            try {
                $galleries[] = $injector
                    ->getInstance('Ansel_Storage')
                    ->getGallery($gid);
            } catch (Ansel_Exception $e) {
                Horde::logMessage('Gallery not found: ' . $gid, 'ERR');
            }
        }

        // Images.
        $istart = max(0, $page * $perpage - $totals['galleries']);
        $count = $perpage - count($galleries);
        if ($count > 0) {
            $iresults = array_slice($this->_results['images'], $istart, $count);
            try {
                $images = count($iresults) ? array_values($injector->getInstance('Ansel_Storage')->getImages(array('ids' => $iresults))) : array();
            } catch (Horde_Exception_NotFound $e) {
                throw new Ansel_Exception($e);
            }
            if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
                $registry->hasMethod('forums/numMessagesBatch')) {

                $ids = array_keys($images);
                try {
                    $ccounts = $GLOBALS['registry']->forums->numMessagesBatch($ids, 'ansel');
                    foreach ($images as $image) {
                        $image->commentCount = (!empty($ccounts[$image->id]) ? $ccounts[$image->id] : 0);
                    }
                } catch (Horde_Exception $e) {}
            }
        } else {
            $images = array();
        }

        return array_merge($galleries, $images);
    }
}
