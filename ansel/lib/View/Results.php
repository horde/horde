<?php
/**
 * The Ansel_View_Results:: class wraps display of images/galleries from
 * multiple parent sources..
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Ansel
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
     * Contructor - just set some instance variables.
     *
     * @return Ansel_View_Results
     */
    public function __construct()
    {
        $this->_owner = Horde_Util::getFormData('owner', null);
        $this->_search = Ansel_Tags::getSearch(null, $this->_owner);
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
        global $conf, $prefs, $registry, $ansel_storage;

        $page = Horde_Util::getFormData('page', 0);
        $action = Horde_Util::getFormData('actionID', '');
        $image_id = Horde_Util::getFormData('image');

        $vars = Horde_Variables::getDefaultVariables();

        // Number perpage from prefs or config.
        $perpage = min($prefs->getValue('tilesperpage'),
                       $conf['thumbnail']['perpage']);

        switch ($action) {
        // Image related actions
        case 'delete':
             if (is_array($image_id)) {
                 $images = array_keys($image_id);
             } else {
                 $images = array($image_id);
             }

             foreach ($images as $image) {
                 // Need a gallery object to delete the image, but need
                 // the image object to get the gallery.
                 $img = $ansel_storage->getImage($image);
                 $gallery = $ansel_storage->getgallery($img->gallery);
                 if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE)) {
                     $GLOBALS['notification']->push(
                        sprintf(_("Access denied deleting photos from \"%s\"."), $image),
                                'horde.error');
                 } else {
                     $result = $gallery->removeImage($image);
                    if (is_a($result, 'PEAR_Error')) {
                        $GLOBALS['notification']->push(
                            sprintf(_("There was a problem deleting photos: %s"),
                                    $result->getMessage()), 'horde.error');
                    } else {
                        $GLOBALS['notification']->push(_("Deleted the photo."),
                                                       'horde.success');
                        Ansel_Tags::clearCache();
                    }
                 }
             }

             // Reload the browse view again to get notifications.
             header('Location: ' . Ansel::getUrlFor('view',
                                                    array('view' => 'Results'),
                                                    true));
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
                $newGallery = $ansel_storage->getGallery($newGallery);
                if (is_a($newGallery, 'PEAR_Error')) {
                    $GLOBALS['notification']->push(_("Bad input."),
                                                   'horde.error');
                } else {
                    // Group by gallery first, then process in bulk by gallery.
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        $result = $gallery->moveImagesTo($images, $newGallery);
                        if (is_a($result, 'PEAR_Error')) {
                            $GLOBALS['notification']->push($result, 'horde.error');
                        } else {
                            $GLOBALS['notification']->push(
                                sprintf(ngettext("Moved %d photo from \"%s\" to \"%s\"",
                                                 "Moved %d photos from \"%s\" to \"%s\"",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        }
                    }
                }
            }

            // Return to the image list.
            $imageurl = Horde_Util::addParameter('view.php',
                                           array('view' => 'Results'));
            header('Location: ' . Ansel::getUrlFor('view',
                                                   array('view' => 'Results'),
                                                   true));
            exit;

        case 'copy':
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }

            // Move the images if we're provided with at least one
            // valid image ID.
            $newGallery = Horde_Util::getFormData('new_gallery');
            if ($images && $newGallery) {
                $newGallery = $ansel_storage->getGallery($newGallery);
                if (is_a($newGallery, 'PEAR_Error')) {
                    $GLOBALS['notification']->push(_("Bad input."),
                                                   'horde.error');
                } else {
                    // Group by gallery first, then process in bulk by gallery.
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        $result = $gallery->copyImagesTo($images, $newGallery);
                        if (is_a($result, 'PEAR_Error')) {
                            $GLOBALS['notification']->push($result,
                                                           'horde.error');
                        } else {
                            $GLOBALS['notification']->push(
                                sprintf(ngettext("Copied %d photo from %s to %s",
                                                 "Copied %d photos from %s to %s",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        }
                    }
                }
            }

            // Return to the image list.
            $imageurl = Horde_Util::addParameter('view.php',
                                           array('view' => 'Results'));
            header('Location: ' . Horde::applicationUrl($imageurl, true));
            exit;

        // Tag related actions
        case 'remove':
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $tag = Ansel_Tags::getTagIds(array($tag));
                $tag = array_pop($tag);
                $this->_search->removeTag($tag);
                $this->_search->save();
            }
            break;

        case 'add':
        default:
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $tag = Ansel_Tags::getTagIds(array($tag));
                $tag = array_pop($tag);
                $this->_search->addTag($tag);
                $this->_search->save();
            }
            break;
        }

        // Check for empty tag search and redirect if empty
        if ($this->_search->tagCount() < 1) {
            header('Location: ' . Horde::applicationUrl('browse.php', true));
            exit;
        }

        // Get the slice of galleries/images to view on this page.
        $results = $this->_search->getSlice($page, $perpage);
        $total = $this->_search->count();
        $total = $total['galleries'] + $total['images'];

        // The number of resources to display on this page.
        $numimages = count($results);

        // Get any related tags to display.
        if ($conf['tags']['relatedtags']) {
            $rtags = $this->_search->getRelatedTags();
            $rtaghtml = '<ul>';
            $links = Ansel_Tags::getTagLinks($rtags, 'add');
            foreach ($rtags as $id => $taginfo) {
                if (!empty($this->_owner)) {
                    $links[$id] = Horde_Util::addParameter($links[$id], 'owner',
                                                     $this->_owner);
                }
                $rtaghtml .= '<li>' . Horde::link($links[$id],
                                                  sprintf(ngettext(
                                                    "%d photo", "%d photos",
                                                    $taginfo['total']),
                                                  $taginfo['total']))
                             . $taginfo['tag_name'] . '</a></li>';
            }
            $rtaghtml .= '</ul>';
        }
        $styleDef = Ansel::getStyleDefinition(
            $GLOBALS['prefs']->getValue('default_gallerystyle'));
        $style = $styleDef['name'];
        $viewurl = Horde_Util::addParameter('view.php', array('view' => 'Results',
                                                        'actionID' => 'add'));

        $vars = Horde_Variables::getDefaultVariables();
        $option_move = $option_copy = $ansel_storage->countGalleries(PERMS_EDIT);


        $pagestart = ($page * $perpage) + 1;
        $pageend = min($pagestart + $numimages - 1, $pagestart + $perpage - 1);
        $pager = new Horde_UI_Pager('page', $vars, array('num' => $total,
                                                         'url' => $viewurl,
                                                         'perpage' => $perpage));
        ob_start();
        include ANSEL_TEMPLATES . '/view/results.inc';
        return ob_get_clean();
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
