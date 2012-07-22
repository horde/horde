<?php
/**
 * The Ansel_Tagger:: class wraps Ansel's interaction with the Content/Tagger
 * system.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Tagger extends Horde_Core_Tagger
{
    protected $_app = 'ansel';
    protected $_types = array('image', 'gallery');

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *                       - type (string) - 'gallery' or 'image'
     *                       - user (array) - only include objects owned by
     *                         these users.
     *
     * @return  A hash of 'gallery' and 'image' ids.
     * @throws Ansel_Exception
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* These filters are mutually exclusive */
        if (!empty($filter['user'])) {
            $args['userId'] = $filter['user'];
        } elseif (!empty($filter['gallery'])) {
            // Only events located in specific galleries
            if (!is_array($filter['gallery'])) {
                $filter['gallery'] = array($filter['gallery']);
            }
            $args['gallery'] = $filter['gallery'];
        }

        try {
            /* Add the tags to the search */
            $args['tagId'] = $this->_tagger->getTagIds($tags);

            /* Restrict to images or galleries */
            $gal_results = $image_results = array();
            if (empty($filter['type']) || $filter['type'] == 'gallery') {
                $args['typeId'] = $this->_type_ids['gallery'];
                $gal_results = $this->_tagger->getObjects($args);
            }

            if (empty($filter['type']) || $filter['type'] == 'image') {
                $args['typeId'] = $this->_type_ids['image'];
                $image_results = $this->_tagger->getObjects($args);
            }
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }

        /* TODO: Filter out images whose gallery has already matched? */
        $results = array('galleries' => array_values($gal_results),
                         'images' => array_values($image_results));

        return $results;
    }

    /**
     * List image ids of images related (via similar tags) to the specified
     * image
     *
     * @param Ansel_Image $image  The image to get related images for.
     * @param bolean $ownerOnly   Only return images owned by the specified
     *                            image's owner.
     *
     * @return array  An array of 'image' and 'rank' keys..
     */
    public function listRelatedImages(Ansel_Image $image, $ownerOnly = true)
    {
        $args = array('typeId' => 'image', 'limit' => 10);
        if ($ownerOnly) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
            $args['userId'] = $gallery->get('owner');
        }

        try {
            $ids = $GLOBALS['injector']->getInstance('Content_Tagger')->getSimilarObjects(array('object' => (string)$image->id, 'type' => 'image'), $args);
        } catch (Content_Exception $e) {
            throw new Ansel_Exception($e);
        }

        if (count($ids) == 0) {
            return array();
        }

        try {
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImages(array('ids' => array_keys($ids)));
        } catch (Horde_Exception_NotFound $e) {
            $images = array();
        }

        $results = array();
        foreach ($images as $key => $image) {
            $results[] = array('image' => $image, 'rank' => $ids[$key]);
        }
        return $results;
    }
}
