<?php
/**
 * The Ansel_View_Abstract:: Parent class for the various Ansel_View classes
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
abstract class Ansel_View_Base
{
    protected $_params = array();

    /**
     * The ansel resource this view is for.
     *
     * @var mixed  Either an Ansel_Gallery or Ansel_Image
     */
    public $resource;

    /**
     * The gallery object (will be eq to $resource in a gallery view
     *
     * @var Ansel_Gallery
     */
    public $gallery;

    /**
     * Collection of Ansel_Widgets to display in this view.
     *
     * @var array
     */
    protected $_widgets = array();


    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    public function __get($property)
    {
        if (isset($this->_params[$property])) {
            return $this->_params[$property];
        }

        return null;
    }

    public function __set($property, $value)
    {
        $this->_params[$property] = $value;
    }

    public function __isset($property)
    {
        if (empty($this->_params[$property])) {
            return false;
        }

        return true;
    }

    /**
     *
     *
     * @param integer $galleryId  The gallery id
     * @param string $slug        The gallery slug
     *
     * @return Ansel_Gallery  The requested Ansel_Gallery object
     * @throws Horde_Exception
     */
    protected function &_getGallery($galleryId = null, $slug = '')
    {
        if (is_null($galleryId) && empty($slug)) {
            $galleryId = !empty($this->_params['gallery_id']) ? $this->_params['gallery_id'] : null;
            $slug = !empty($this->_params['gallery_slug']) ? $this->_params['gallery_slug'] : null;
        }

        if (empty($galleryId) && empty($slug)) {
            throw new Horde_Exception(_("No gallery specified"));
        }

        // If we have a slug, use it.
        if (!empty($slug)) {
            $gallery = &$GLOBALS['ansel_storage']->getGalleryBySlug($slug);
        } else {
            $gallery = &$GLOBALS['ansel_storage']->getGallery($galleryId);
        }
        if (is_a($gallery, 'PEAR_Error')) {
            throw new Horde_Exception($gallery->getMessage());
        } elseif (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception(sprintf(_("Access denied to gallery \"%s\"."), $gallery->get('name')));
        }

        /* Set any date info we might have */
        if (!empty($this->_params['year'])) {
            $date = Ansel::getDateParameter(
                array('year' => $this->_params['year'],
                      'month' => $this->_params['month'],
                      'day' => $this->_params['day']));
        } else {
            $date = array();
        }
        $gallery->setDate($date);

        return $gallery;
    }

    /**
     * Add an Ansel_Widget to be displayed in this view.
     *
     * @param Ansel_Widget $widget  The Ansel_Widget to display
     */
    public function addWidget($widget)
    {
        $result = $widget->attach($this);
        if (!empty($result)) {
            $this->_widgets[] = $widget;
        }
    }


    /**
     * Output any widgets associated with this view.
     *
     */
    public function renderWidgets()
    {
        $this->_renderWidgets();
    }

    /**
     * Count the number of widgets we have attached.
     *
     * @return integer  The number of widgets attached to this view.
     */
    public function countWidgets()
    {
        return count($this->_widgets);
    }

    /**
     * Default widget rendering, can be overridden by any subclass.
     *
     */
    protected function _renderWidgets()
    {
        echo '<div class="anselWidgets">';
        foreach ($this->_widgets as $widget) {
            if ($widget->autoRender) {
                echo $widget->html();
                echo '<br />';
            }
        }
        echo '</div>';
    }

   /**
     * JSON representation of this gallery's images.
     *
     * @param array $params  An array of parameters for this method:
     *   <pre>
     *      images -     Array of Ansel_Images to generate JSON for [null]
     *      full   -     Should a full URL be generated? [false]
     *      from   -     Starting image count [0]
     *      count  -     The number of images to include (starting at from) [0]
     *      image_view - The type of ImageView to obtain the src url for. [screen]
     *      view_links - Should the JSON include links to the Image and/or Gallery View? [false]
     *      perpage    - Number of images per page [from user prefs]
     *   </pre>
     *
     * @return string  A serialized JSON array.
     */
    public function json($params = array())
    {
        global $conf, $prefs;

        $default = array('full' => false,
                         'from' => 0,
                         'count' => 0,
                         'image_view' => 'screen',
                         'view_links' => false,
                         'perpage' => $prefs->getValue('tilesperpage', $conf['thumbnail']['perpage']));

        $params = array_merge($default, $params);

        $json = array();
        $curimage = 0;
        $curpage =  0;

        if (empty($params['images'])) {
            $images = $this->gallery->getImages($params['from'], $params['count']);
        }

        $style = $this->gallery->getStyle();
        foreach ($images as $image) {
            // Calculate the page this image will appear on in the
            // gallery view.
            if (++$curimage > $params['perpage']) {
                ++$curpage;
                $curimage = 0;
            }

            $data = array((string)Ansel::getImageUrl($image->id, $params['image_view'], $params['full'], $style['name']),
                          htmlspecialchars($image->filename, ENT_COMPAT, Horde_Nls::getCharset()),
                          Horde_Text_Filter::filter($image->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL)),
                          $image->id,
                          $curpage);
            if ($params['view_links']) {
                $data[] = (string)Ansel::getUrlFor('view',
                    array('gallery' => $this->gallery->id,
                          'slug' => $this->gallery->get('slug'),
                          'image' => $image->id,
                          'view' => 'Image',
                          'page' => $curpage),
                    true);
                $data[] = (string)Ansel::getUrlFor('view',
                    array('gallery' => $image->gallery,
                          'slug' => $this->gallery->get('slug'),
                          'view' => 'Gallery'),
                    true);
            }
            // Source, Width, Height, Name, Caption, Image Id, Gallery Page
            $json[] = $data;
        }

        return Horde_Serialize::serialize($json, Horde_Serialize::JSON, Horde_Nls::getCharset());
    }

    /**
     * Getter for the view parameters.
     *
     * @return unknown_type
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @abstract
     * @return unknown_type
     */
    abstract public function viewType();

    abstract public function getGalleryCrumbData();

    abstract public function getTitle();

    abstract public function html();

}
