<?php
/**
 * The Ansel_View_Image:: class wraps display of individual images.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_View_Image extends Ansel_View_Base
{

    protected $_slug;
    protected $_page;
    protected $_date;
    protected $_mode;
    protected $_style;
    protected $_geometry;
    protected $_imageList;
    protected $_revList;
    protected $_urls = array();

    /**
     * Const'r
     *
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        /* Get the Ansel_Image */
        $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($params['image_id']);

        /* Get the Ansel_Gallery */
        $this->gallery = $this->_getGallery();

        /* Save the image reference */
        $this->resource = $image;

        /* Check user age */
        if (!$this->gallery->isOldEnough()) {
            if (!empty($params['api'])) {
               throw new Horde_Exception('Locked galleries are not viewable via the api.');
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => isset($this->_params['day']) ? $this->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);

            header('Location: ' . Horde::applicationUrl('disclamer.php')->add($params)->setRaw(true));
            exit;
        }

        // Check password
        if ($this->gallery->hasPasswd()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                      'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                      'day' => isset($this->_params['day']) ? $this->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);

            header('Location: ' . Horde::applicationUrl('protect.php')->add($params)->setRaw(true));
            exit;
        }

        /* Any script files we may need if not calling via the api */
        if (empty($this->_params['api'])) {
            Horde::addScriptFile('effects.js', 'horde');
            Horde::addScriptFile('stripe.js', 'horde');
        }

    }

    public function getGalleryCrumbData()
    {
        return $this->gallery->getGalleryCrumbData();
    }

    /**
     * Get the title for this view.
     *
     * @return string  The title.
     */
    public function getTitle()
    {
        return $this->resource->filename;
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML.
     */
    public function html()
    {
        $this->_prepare();
        return $this->_html();
    }

    /**
     * Build variables needed to output the html
     *
     * @return void
     */
    protected function _prepare()
    {
        /* Gallery slug and the page this image is one, if specified */
        $this->_page = isset($this->_params['page']) ? $this->_params['page'] : 0;
        $this->_slug = $this->gallery->get('slug');

        /* Get any date info the gallery has */
        $this->_date = $this->gallery->getDate();

        $this->_style = (empty($this->_params['style']) ?
             $this->gallery->getStyle() :
             Ansel::getStyleDefinition($this->_params['style']));

        /* Make sure the screen view is loaded and get the geometry */
        try {
            $this->_geometry = $this->resource->getDimensions('screen');
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $this->_geometry = $conf['screen'];
        }

        /* Get the image lists */
        $this->_imageList = $this->gallery->listImages();
        $this->_revList = array_flip($this->_imageList);

        /* Not needed when being called via api */
        if (empty($this->_params['api'])) {
            $this->_urls['ecard'] = Horde::applicationUrl('img/ecard.php')->add(
                array_merge(array('gallery' => $this->gallery->id,
                                  'image' => $this->resource->id),
                            $this->_date));

            /* Build the various urls */
            $imageActionUrl = Horde::applicationUrl('image.php')->add(
                array_merge(array('gallery' => $this->gallery->id,
                                  'image' => $this->resource->id,
                                  'page' => $this->_page),
                            $this->_date));

            /* Create the popup code seperately to avoid encoding issues */
            $this->_urls['prop_popup'] = Horde::popupJs(
              $imageActionUrl,
              array('params' => array('actionID' => 'modify',
                                      'ret' => 'image',
                                      'gallery' => $this->gallery->id,
                                      'image' => $this->resource->id,
                                      'page' => $this->_page),
                    'urlencode' => true));

            $this->_urls['edit'] = $imageActionUrl->copy()->add('actionID', 'editimage');
            $this->_urls['delete'] = $imageActionUrl->copy()->add('actionID', 'delete');
            $this->_urls['download'] = Horde::applicationUrl('img/download.php', true)->add('image', $this->resource->id);
            $this->_urls['report'] = Horde::applicationUrl('report.php')->add(
                    array('gallery' =>  $this->gallery->id,
                          'image' => $this->resource->id));
        }

        /* Check for an explicit gallery view url to use */
        if (!empty($this->_params['gallery_view_url'])) {
            $this->_urls['gallery'] = new Horde_Url(str_replace(array('%g', '%s'), array($this->gallery->id, $this->_slug), urldecode($this->_params['gallery_view_url'])));
            $this->_urls['gallery']->add($this->_date);
        } else {
            $this->_urls['gallery'] = Ansel::getUrlFor('view', array_merge(
                                           array('gallery' => $this->gallery->id,
                                                 'slug' => $this->_slug,
                                                 'page' => $this->_page,
                                                 'view' => 'Gallery'),
                                           $this->_date),
                                           true);
        }

        /* Get the image src url */
        $this->_urls['imgsrc'] = Ansel::getImageUrl($this->resource->id, 'screen', true, $this->_style['name']);

        /* And a self url. Can't use Horde::selfUrl() since that would ignore
         * pretty urls. */
        $this->_urls['self'] = Ansel::getUrlFor('view', array_merge(
                                    array('gallery' => $this->gallery->id,
                                          'slug' => $this->_slug,
                                          'image' => $this->resource->id,
                                          'view' => 'Image',
                                          'page' => $this->_page),
                                    $this->_date));
    }

    /**
     * Image view specific HTML - done so we can extend View_Image for things
     * like the slideshow view etc...
     */
    protected function _html()
    {
        global $conf, $registry, $prefs;

        /* Starting image */
        $imageIndex = $this->_revList[$this->resource->id];

        /* Get comments before any output in sent. */
        if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
            $registry->hasMethod('forums/doComments')) {
            $hasComments = true;
            if (!empty($this->_params['comment_url'])) {
                $this->_params['comment_url'] = str_replace(
                    array('%i', '%g', '%s'),
                    array($imageId, $galleryId, $gallerySlug),
                    urldecode($this->_params['comment_url']));
            }
            $url = empty($this->_params['comment_url']) ? null : $this->_params['comment_url'];
            $comments = $registry->call('forums/doComments',
                                        array('ansel', $this->resource->id,
                                              'commentCallback', true, null,
                                              $url));
            if ($comments instanceof PEAR_Error) {
                Horde::logMessage($comments, 'DEBUG');
                $comments = array();
            }
        } else {
            $comments = array();
            $hasComments = false;
        }
        /* Get the next and previous image ids */
        if (isset($this->_imageList[$imageIndex + 1])) {
            $next = $this->_imageList[$imageIndex + 1];
        } else {
            $next = $this->_imageList[0];
        }
        if (isset($this->_imageList[$imageIndex - 1])) {
            $prev = $this->_imageList[$imageIndex - 1];
        } else {
            $prev = $this->_imageList[count($this->_imageList) - 1];
        }

        /* Calculate the page number of the next/prev images */
        $perpage = $prefs->getValue('tilesperpage');
        $pagestart = $this->_page * $perpage;
        $pageend = min(count($this->_imageList), $pagestart + $perpage - 1);
        $page_next = $this->_page;

        if ($this->_revList[$this->resource->id] + 1 > $pageend) {
            $page_next++;
        }
        $page_prev = $this->_page;
        if ($this->_revList[$this->resource->id] - 1 < $pagestart) {
            $page_prev--;
        }

        /* Previous image link */
        if (!empty($this->_params['image_view_url'])) {
            $prev_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $this->gallery->id, $this->_slug),
                urldecode($this->_params['image_view_url']));
        } else {
            $prev_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $this->gallery->id,
                      'slug' => $this->_slug,
                      'image' => $prev,
                      'view' => 'Image',
                      'page' => $page_prev),
                $this->_date));
        }
        $prvImgUrl = Ansel::getImageUrl($prev, 'screen', true, $this->_style['name']);

        /* Next image link */
        if (!empty($this->_params['image_view_url'])) {
            $next_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $this->gallery->id, $this->_slug),
                urldecode($this->_params['image_view_url']));
        } else {
            $next_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $this->gallery->id,
                      'slug' => $this->_slug,
                      'image' => $next,
                      'view' => 'Image',
                      'page' => $page_next),
                $this->_date));
        }
        $nextImgUrl = Ansel::getImageUrl($next, 'screen', true, $this->_style['name']);

        /* Slideshow link */
        if (!empty($this->_params['slideshow_link'])) {
            $this->_urls['slideshow'] = str_replace(array('%i', '%g'),
                                         array($this->resource->id, $this->gallery->id),
                                         urldecode($this->_params['slideshow_link']));
        } else {
            $this->_urls['slideshow'] = Horde::applicationUrl('view.php')->add(
                array_merge(array('gallery' => $this->gallery->id,
                                  'image' => $this->resource->id,
                                  'view' => 'Slideshow'),
                            $this->_date));
        }

        $commentHtml = '';
        if (isset($hasComments)) {
            if (!empty($comments['threads'])) {
                $commentHtml .= '<br />' . $comments['threads'];
            }
            if (!empty($comments['comments'])) {
                $commentHtml .= '<br />' . $comments['comments'];
            }
        }

        if ($prefs->getValue('showexif')) {
            $exifHtml = $this->_getExifHtml();
        } else {
            $exifHtml = '';
        }

        /* Buffer the template file and return the html */
        Horde::startBuffer();

        //@TODO: Refactor styles to allow dynamic inclusion/exclusion of widgets.
        /* These items currently don't work when viewing through the api */
        if (empty($this->_params['api'])) {
            /* Add the widgets */
            // Tag widget
            $this->addWidget(Ansel_Widget::factory('Tags', array('view' => 'image')));

            // Similar photos
            $this->addWidget(Ansel_Widget::factory('SimilarPhotos'));

           // Geolocation
           $this->addWidget(Ansel_Widget::factory('Geotag', array('images' => array($this->resource->id))));

            // Faces
            if ($conf['faces']['driver']) {
                $this->addWidget(Ansel_Widget::factory('ImageFaces', array('selfUrl' => $this->_urls['self'])));
            }

            // Links
            $this->addWidget(Ansel_Widget::factory('Links', array()));

            /* In line caption editing */
            if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $geometry = $this->resource->getDimensions();
                $GLOBALS['injector']->getInstance('Horde_Ajax_Imple')->getImple(array('ansel', 'EditCaption'), array(
                    'width' => $geometry['width'],
                    'domid' => "Caption",
                    'id' => $this->resource->id
                ));
            }
        }

        /* Output the js if we are calling via the api */
        if (!empty($this->_params['api'])) {
            $includes = $GLOBALS['injector']->createInstance('Horde_Script_Files');
            $includes->add('prototype.js', 'horde', true, true);
            $includes->add('effects.js', 'horde',true, true);
            $includes->add('stripe.js', 'horde', true, true);
            $includes->includeFiles();
        }

        require ANSEL_TEMPLATES . '/view/image.inc';
        return Horde::endBuffer();
    }

    /**
     * Helper function for generating the HTML for EXIF data.
     *
     * @return string  The HTML
     */
    private function _getExifHtml()
    {
        $data = $this->resource->getAttributes(true);

        $html = '';
        if (count($data)) {
            $data = array_chunk($data, 3);
            $html .= '<table class="box striped" cellspacing="0" style="width:100%; padding:4px">';
            $i = 0;
            foreach ($data as $elem) {
                $html .= '<tr class="' . (($i++ % 2 == 0) ? 'rowEven' : 'rowOdd')
                         . '">' . implode('', $elem) . '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    public function viewType()
    {
        return 'Image';
    }

}
