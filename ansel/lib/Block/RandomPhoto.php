<?php
/**
 * Display a random photo in a block.
 *
 * Copyright 2003-2007 Duck <duck@obla.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <Duck@obla.net>
 * @author  Ben Chavet <ben@horde.org>
 */
class Ansel_Block_RandomPhoto extends Horde_Block
{
    /**
     *
     * @var boolean
     */
    public $updateable = true;

    /**
     */
    public function getName()
    {
        return _("Random photo");
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getRandomGallery();
        if (!$gallery) {
            return _("There are no photo galleries available.");
        }
        $imagelist = $gallery->listImages(rand(0, $gallery->countImages() - 1), 1);
        if (empty($imagelist)) {
            return '';
        }
        $imageId = $imagelist[0];
        $viewurl = Ansel::getUrlFor('view', array('gallery' => $gallery->id,
                                                  'slug' => $gallery->get('slug'),
                                                  'image' => $imageId,
                                                  'view' => 'Image'), true);

        if ($gallery->isOldEnough() && !$gallery->hasPasswd()) {
            $img = '<img src="' . Ansel::getImageUrl($imageId, 'thumb', true, Ansel::getStyleDefinition('ansel_default')) . '" alt="[random photo]" />';
        } else {
            $img = Horde::img('thumb-error.png');
        }

        return $viewurl->link(array('title' => _("View Photo"))) . $img . '</a>';
    }

}
