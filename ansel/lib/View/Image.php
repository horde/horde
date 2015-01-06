<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC (http://www.horde.org)
 * @license   http://www.horde.org/licenses/gpl GPL
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
/**
 * The Ansel_View_Image:: class wraps display of individual images.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC (http://www.horde.org)
 * @license   http://www.horde.org/licenses/gpl GPL
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_Image extends Ansel_View_Ansel
{
    /**
     * The parent gallery's slug name, if available.
     *
     * @var string
     */
    protected $_slug;

    /**
     * The page this image appears on in the current gallery view, if available.
     *
     * @var integer
     */
    protected $_page;

    /**
     * The image's date for use in date browsing.
     *
     * @var array
     */
    protected $_date;

    /**
     * The gallery mode for this images's current gallery view.
     *
     * @var string
     */
    protected $_mode;

    /**
     * The parent gallery's current style.
     *
     * @var Ansel_Style
     */
    protected $_style;

    /**
     * The image geometry.
     *
     * @var array
     */
    protected $_geometry;

    /**
     * List of image ids in the parent gallery. Used for next/prev fetching.
     *
     * @var array
     */
    protected $_imageList;

    /**
     * Cache {@link self::_imageList} in reverse.
     *
     * @var array
     */
    protected $_revList;

    /**
     * An array of various URLs that are displayed in the view.
     *
     * @var array
     */
    protected $_urls = array();

    /**
     * Const'r
     *
     * @param array  Parameters for the view.
     * @throws Ansel_Exception
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);

        // Get the Ansel_Image
        $this->resource = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getImage($params['image_id']);

        // Get the Ansel_Gallery
        $this->gallery = $this->_getGallery();

        // Check user age
        if (!$this->gallery->isOldEnough()) {
            if (!empty($params['api'])) {
               throw new Ansel_Exception('Locked galleries are not viewable via the api.');
            }
            $date = Ansel::getDateParameter(array(
                'year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                'day' => isset($this->_params['day']) ? $this->_params['day'] : 0)
            );

            $url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $this->resource->id),
                    $date),
                true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);
            Horde::url('disclamer.php')->add($params)->setRaw(true)->redirect();
            exit;
        }

        // Check password
        if ($this->gallery->hasPasswd()) {
            if (!empty($params['api'])) {
                throw new Ansel_Exception(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(array(
                'year' => isset($this->_params['year']) ? $this->_params['year'] : 0,
                'month' => isset($this->_params['month']) ? $this->_params['month'] : 0,
                'day' => isset($this->_params['day']) ? $this->_params['day'] : 0)
            );

            $url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array('gallery' => $this->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $this->resource->id),
                    $date),
                true);

            $params = array('gallery' => $this->gallery->id, 'url' => $url);
            Horde::url('protect.php')->add($params)->setRaw(true)->redirect();
            exit;
        }

        // Any script files we may need if not calling via the api
        if (empty($this->_params['api'])) {
            $GLOBALS['page_output']->addScriptFile('scriptaculous/effects.js', 'horde');
            $GLOBALS['page_output']->addScriptFile('stripe.js', 'horde');
        }
        $this->_includeViewSpecificScripts();
    }

    /**
     * Return the gallery's breadcrumb data.
     *
     * @return array
     */
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
     * Build variables needed to output the html. Extracted to this method so
     * child classes can use this as well.
     */
    protected function _prepare()
    {
        global $conf;

        $this->_page = isset($this->_params['page']) ? $this->_params['page'] : 0;
        $this->_slug = $this->gallery->get('slug');
        $this->_date = $this->gallery->getDate();
        $this->_style = (empty($this->_params['style']) ?
             $this->gallery->getStyle() :
             Ansel::getStyleDefinition($this->_params['style']));

        // Make sure the screen view is loaded and get the geometry
        try {
            $this->_geometry = $this->resource->getDimensions('screen');
        } catch (Ansel_Exception $e) {
            Horde::log($e, 'ERR');
            $this->_geometry = $GLOBALS['conf']['screen'];
        }

        // Get the image lists
        $this->_imageList = $this->gallery->listImages();
        $this->_revList = array_flip($this->_imageList);

        // Not needed when being called via api
        if (empty($this->_params['api'])) {
            // Build the various urls
            $imageActionUrl = Horde::url('image.php')->add(
                array_merge(array('gallery' => $this->gallery->id,
                                  'image' => $this->resource->id,
                                  'page' => $this->_page),
                            $this->_date)
            );

            if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $this->_urls['prop_popup'] = Horde::popupJs(
                    $imageActionUrl,
                    array('urlencode' => true,
                          'height' => 360,
                          'width' => 500,
                          'params' => array(
                              'actionID' => 'modify',
                              'ret' => 'image',
                              'gallery' => $this->gallery->id,
                              'image' => $this->resource->id,
                              'page' => $this->_page))
                );
                $this->_urls['edit'] = $imageActionUrl->copy()->add('actionID', 'editimage');
            }
            if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $this->_urls['delete'] = $imageActionUrl->copy()->add('actionID', 'delete');
            }

            if (!empty($conf['ecard']['enable'])) {
                $this->_urls['ecard'] = Horde::url('img/ecard.php')->add(
                    array_merge(array('gallery' => $this->gallery->id,
                                      'image' => $this->resource->id),
                                $this->_date)
                );
            }

            if ($this->gallery->canDownload()) {
                $this->_urls['download'] = Horde::url('img/download.php', true)->add('image', $this->resource->id);
            }
            if ((!$GLOBALS['registry']->getAuth() || $this->gallery->get('owner') != $GLOBALS['registry']->getAuth()) &&
                !empty($GLOBALS['conf']['report_content']['driver']) &&
                (($conf['report_content']['allow'] == 'authenticated' && $GLOBALS['registry']->isAuthenticated()) ||
                 $conf['report_content']['allow'] == 'all')) {

                $this->_urls['report'] = Horde::url('report.php')->add(
                    array('gallery' =>  $this->gallery->id,
                          'image' => $this->resource->id));
            }
        }

        // Check for an explicit gallery view url to use
        if (!empty($this->_params['gallery_view_url'])) {
            $this->_urls['gallery'] = new Horde_Url(str_replace(
                array('%g', '%s'),
                array($this->gallery->id, $this->_slug),
                urldecode($this->_params['gallery_view_url'])));
            $this->_urls['gallery']->add($this->_date);
        } else {
            $this->_urls['gallery'] = Ansel::getUrlFor(
                'view',
                array_merge(
                    array(
                        'gallery' => $this->gallery->id,
                        'slug' => $this->_slug,
                        'page' => $this->_page,
                        'view' => 'Gallery'),
                    $this->_date),
                true);
        }

        // Get the image src url
        $this->_urls['imgsrc'] = Ansel::getImageUrl(
            $this->resource->id, 'screen', true, $this->_style);

        // A self url. Can't use Horde::selfUrl() since that would ignore
        // pretty urls.
        $this->_urls['self'] = Ansel::getUrlFor(
            'view',
            array_merge(
                array(
                    'gallery' => $this->gallery->id,
                    'slug' => $this->_slug,
                    'image' => $this->resource->id,
                    'view' => 'Image',
                    'page' => $this->_page),
                $this->_date));
    }

    /**
     * Generate the Horde_View and populate with basic/common properties.
     *
     * @return Horde_View
     */
    protected function _getView()
    {
        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/view');
        $view->filename = $this->resource->filename;
        $view->caption = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_TextFilter')
            ->filter($this->resource->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        $view->view = $this;
        $view->geometry = $this->_geometry;
        $view->background = $this->_style->background;

        return $view;
    }

    /**
     * Image view specific HTML - done so we can extend View_Image for things
     * like the slideshow view etc...
     */
    protected function _html()
    {
        global $conf, $registry, $prefs, $page_output;

        // Build initial view properties
        $view = $this->_getView();
        $view->hide_slideshow = !empty($this->_params['hide_slideshow']);

        // Starting image
        $imageIndex = $this->_revList[$this->resource->id];

        // Get the next and previous image ids
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

        // Calculate the page number of the next/prev images
        $perpage = $prefs->getValue('tilesperpage');
        $pagestart = $this->_page * $perpage;
        $pageend = min(count($this->_imageList), $pagestart + $perpage - 1);
        $page_next = $this->_page;
        if ($this->_revList[$this->resource->id] + 1 > $pageend) {
            ++$page_next;
        }
        $page_prev = $this->_page;
        if ($this->_revList[$this->resource->id] - 1 < $pagestart) {
            --$page_prev;
        }

        // Previous image link
        if (!empty($this->_params['image_view_url'])) {
            $view->prev_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $this->gallery->id, $this->_slug),
                urldecode($this->_params['image_view_url']));
        } else {
            $view->prev_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $this->gallery->id,
                      'slug' => $this->_slug,
                      'image' => $prev,
                      'view' => 'Image',
                      'page' => $page_prev),
                $this->_date));
        }
        $prevImgSrc = Ansel::getImageUrl($prev, 'screen', true, $this->_style);

        // Next image link
        if (!empty($this->_params['image_view_url'])) {
            $view->next_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $this->gallery->id, $this->_slug),
                urldecode($this->_params['image_view_url']));
        } else {
            $view->next_url = Ansel::getUrlFor(
                'view',
                array_merge(
                    array(
                        'gallery' => $this->gallery->id,
                        'slug' => $this->_slug,
                        'image' => $next,
                        'view' => 'Image',
                        'page' => $page_next),
                    $this->_date));
        }
        $nextImgSrc = Ansel::getImageUrl($next, 'screen', true, $this->_style);

        // Slideshow link
        if (!empty($this->_params['slideshow_link'])) {
            $this->_urls['slideshow'] = str_replace(
                array('%i', '%g'),
                array($this->resource->id, $this->gallery->id),
                urldecode($this->_params['slideshow_link']));
        } else {
            $this->_urls['slideshow'] = Horde::url('view.php')->add(
                array_merge(array('gallery' => $this->gallery->id,
                                  'image' => $this->resource->id,
                                  'view' => 'Slideshow'),
                            $this->_date));
        }

        // These items don't work when viewing through the api
        if (empty($this->_params['api'])) {
            $this->addWidget(Ansel_Widget::factory('Tags', array('view' => 'image')));
            $this->addWidget(Ansel_Widget::factory('SimilarPhotos'));
            $this->addWidget(Ansel_Widget::factory('Geotag', array('images' => array($this->resource->id))));
            if ($conf['faces']['driver']) {
                $this->addWidget(Ansel_Widget::factory('ImageFaces', array('selfUrl' => $this->_urls['self'])));
            }
            $this->addWidget(Ansel_Widget::factory('Links', array()));

            // In line caption editing
            if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(
                    'Ansel_Ajax_Imple_EditCaption',
                    array(
                        'width' => $this->_geometry['width'],
                        'id' => 'anselcaption',
                        'dataid' => $this->resource->id
                    )
                );
            }
        }

        // Output the js if we are calling via the api
        if (!empty($this->_params['api'])) {
            foreach (array('prototype.js', 'stripe.js', 'scriptaculous/effects.js') as $val) {
                $tmp = new Horde_Script_File_JsDir($val, 'horde');
                Horde::startBuffer();
                echo $tmp->tag_full;
                $html = Horde::endBuffer();
            }
        } else {
          $html = '';
        }

        $js = array();
        if (empty($this->_params['hide_slideshow'])) {
            $js[] = '$$(\'.ssPlay\').each(function(n) { n.show(); });';
        }
        $js = array_merge($js, array(
          'AnselImageView.nextImgSrc = "' . $nextImgSrc . '"',
          'AnselImageView.prevImgSrc = "' . $prevImgSrc . '"',
          'AnselImageView.urls = { "imgsrc": "' . $this->_urls['imgsrc'] . '" }',
          'AnselImageView.onload()'
        ));
        $page_output->addInlineScript($js);

        // Pass the urls now that we have them all.
        $view->urls = $this->_urls;

        // Get the exif data if needed.
        if ($prefs->getValue('showexif')) {
            $view->exif = array_chunk($this->resource->getAttributes(), 3, true);
        }

        // Comments
        if ($comments = $this->_getCommentData()) {
            if (!empty($comments['threads'])) {
                $this->view->commentHtml = '<br>' . $comments['threads'];
            }
            if (!empty($comments['comments'])) {
                $this->view->commentHtml .= '<br>' . $comments['comments'];
            }
        }

        return $html . $view->render('image');
    }

    /**
     * Helper for comments
     *
     * @return boolean|array  Either an array of comment data or false on failure
     */
    protected function _getCommentData()
    {
        global $conf, $registry;

        if (($conf['comments']['allow'] == 'all' ||
            ($conf['comments']['allow'] == 'authenticated' && $registry->getAuth())) &&
            $registry->hasMethod('forums/doComments')) {

            if (!empty($this->_params['comment_url'])) {
                $this->_params['comment_url'] = str_replace(
                    array('%i', '%g', '%s'),
                    array($imageId, $galleryId, $gallerySlug),
                    urldecode($this->_params['comment_url']));
            }
            $url = empty($this->_params['comment_url']) ? null : $this->_params['comment_url'];
            try {
                return $registry->forums->doComments('ansel', $this->resource->id, 'commentCallback', true, null, $url);
            } catch (Horde_Exception $e) {
                Horde::log($e, 'DEBUG');
                return false;
            }
        } else {
            return false;
        }
    }

    protected function _includeViewSpecificScripts()
    {
      $GLOBALS['page_output']->addScriptFile('views/common.js');
      $GLOBALS['page_output']->addScriptFile('views/image.js');
    }

    public function viewType()
    {
        return 'Image';
    }

}
