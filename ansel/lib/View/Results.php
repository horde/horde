<?php
/**
 * The Ansel_View_Results:: class wraps display of images/galleries from
 * multiple parent sources..
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_View_Results extends Ansel_View_Base
{
    /**
     * Instance of our tag search
     *
     * @var Ansel_Tag_Search
     */
    protected $_search;

    /**
     * Gallery owner id
     *
     * @var string
     */
    protected $_owner;

    /**
     * The current page
     *
     * @var integer
     */
    private $_page;

    /**
     * Number of resources per page.
     *
     * @var integer
     */
    private $_perPage;

    /**
     * Contructor.
     *
     * Also handles any actions from the view.
     *
     * @return Ansel_View_Results
     */
    public function __construct()
    {
        global $prefs, $conf;

        $notification = $GLOBALS['injector']->getInstance('Horde_Notification');
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');

        $this->_owner = Horde_Util::getFormData('owner', '');
        //@TODO: Inject the search object when we have more then just a tag search
        $this->_search = new Ansel_Search_Tag($GLOBALS['injector']->getInstance('Ansel_Tagger'), null, $this->_owner);
        $this->_page = Horde_Util::getFormData('page', 0);
        $action = Horde_Util::getFormData('actionID', '');
        $image_id = Horde_Util::getFormData('image');
        $vars = Horde_Variables::getDefaultVariables();

        /* Number perpage from prefs or config. */
        $this->_perPage = min($prefs->getValue('tilesperpage'), $conf['thumbnail']['perpage']);

        switch ($action) {
        /*  Image related actions */
        case 'delete':
             if (is_array($image_id)) {
                 $images = array_keys($image_id);
             } else {
                 $images = array($image_id);
             }

             foreach ($images as $image) {
                 $img = $ansel_storage->getImage($image);
                 $gallery = $ansel_storage->getgallery($img->gallery);
                 if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                     $notification->push(
                        sprintf(_("Access denied deleting photos from \"%s\"."), $image), 'horde.error');
                 } else {
                     try {
                         $result = $gallery->removeImage($image);
                         $notification->push(_("Deleted the photo."), 'horde.success');
                     } catch (Ansel_Exception $e) {
                        $notification->push(
                            sprintf(_("There was a problem deleting photos: %s"), $e->getMessage()), 'horde.error');
                     }
                 }
             }

             Ansel::getUrlFor('view', array('view' => 'Results'), true)->redirect();
             exit;

        case 'move':
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }

            // Move the images if we're provided with at least one
            // valid image ID.
            $newGallery = Horde_Util::getFormData('new_gallery');
            if ($images && $newGallery) {
                try {
                    $newGallery = $ansel_storage->getGallery($newGallery);
                    // Group by gallery first, then process in bulk by gallery.
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->moveImagesTo($images, $newGallery);
                            $notification->push(
                                sprintf(ngettext("Moved %d photo from \"%s\" to \"%s\"",
                                                 "Moved %d photos from \"%s\" to \"%s\"",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }

            Ansel::getUrlFor('view', array('view' => 'Results'), true)->redirect();
            exit;

        case 'copy':
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }

            $newGallery = Horde_Util::getFormData('new_gallery');
            if ($images && $newGallery) {
                try {
                    /* Group by gallery first, then process in bulk by gallery. */
                    $newGallery = $ansel_storage->getGallery($newGallery);
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->copyImagesTo($images, $newGallery);
                            $notification->push(
                                sprintf(ngettext("Copied %d photo from %s to %s",
                                                 "Copied %d photos from %s to %s",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }
            Ansel::getUrlFor('view', array('view' => 'Results'), true)->redirect();
            exit;

        /* Tag related actions */
        case 'remove':
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_search->removeTag($tag);
                $this->_search->save();
            }
            break;

        case 'add':
        default:
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_search->addTag($tag);
                $this->_search->save();
            }
            break;
        }

        /* Check for empty tag search and redirect if empty */
        if ($this->_search->tagCount() < 1) {
            Horde::url('browse.php', true)->redirect();
            exit;
        }
    }

    /**
     * Return the title for this view.
     *
     * @return string The title for this view.
     */
    public function getTitle()
    {
        return (!empty($this->_owner))
                ? sprintf(_("Searching %s's photos tagged: "), $this->_owner)
                : _("Searching all photos tagged: ");
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML
     */
    public function html()
    {
        global $conf, $prefs;

        /* Ansel Storage*/
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');

        // Get the slice of galleries/images to view on this page.
        try {
            $results = $this->_search->getSlice($this->_page, $this->_perPage);
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            return _("An error has occured retrieving the image. Details have been logged.");
        }
        $total = $this->_search->count();
        $total = $total['galleries'] + $total['images'];

        // The number of resources to display on this page.
        $numimages = count($results);
        $tilesperrow = $prefs->getValue('tilesperrow');

        // Get any related tags to display.
        if ($conf['tags']['relatedtags']) {
            $rtags = $this->_search->getRelatedTags();
            $rtaghtml = '<ul>';

            $links = Ansel::getTagLinks($rtags, 'add');
            foreach ($rtags as $id => $taginfo) {
                if (!empty($this->_owner)) {
                    $links[$id]->add('owner', $this->_owner);
                }
                $rtaghtml .= '<li>' . $links[$id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos",$taginfo['total']),$taginfo['total']))) . $taginfo['tag_name'] . '</a></li>';
            }
            $rtaghtml .= '</ul>';
        }
        $style = Ansel::getStyleDefinition($GLOBALS['prefs']->getValue('default_gallerystyle'));
        $viewurl = Horde::url('view.php')->add(array('view' => 'Results',
                                                     'actionID' => 'add'));

        $vars = Horde_Variables::getDefaultVariables();
        $option_move = $option_copy = $ansel_storage->countGalleries(Horde_Perms::EDIT);

        $this->_pagestart = ($this->_page * $this->_perPage) + 1;
        $this->_pageend = min($this->_pagestart + $numimages - 1, $this->_pagestart + $this->_perPage - 1);
        $this->_pager = new Horde_Core_Ui_Pager('page', $vars, array('num' => $total,
                                                         'url' => $viewurl,
                                                         'perpage' => $this->_perPage));
        Horde::startBuffer();
        include ANSEL_TEMPLATES . '/view/results.inc';
        return Horde::endBuffer();
    }

    public function viewType()
    {
        return 'Results';
    }

    public function getGalleryCrumbData()
    {
        return array();
    }

}
