<?php
/**
 * Ansel Base Class.

 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel
{
    /* Sort constants */
    const SORT_ASCENDING = 0;
    const SORT_DESCENDING = 1;

    /**
     * Build initial Ansel javascript object.
     *
     * @return string
     */
    static public function initJSVars()
    {
        $code = array('Ansel = {ajax: {}, widgets: {}}');
        return $code;
    }

    /**
     * Create and initialize the database object.
     *
     * @return mixed MDB2 object
     * @throws Ansel_Exception
     */
    static public function &getDb()
    {
        $config = $GLOBALS['conf']['sql'];
        unset($config['charset']);
        $mdb = MDB2::singleton($config);
        if ($mdb instanceof PEAR_Error) {
            throw new Ansel_Exception($mdb->getMessage());
        }
        $mdb->setOption('seqcol_name', 'id');

        /* Set DB portability options. */
        switch ($mdb->phptype) {
        case 'mssql':
            $mdb->setOption('field_case', CASE_LOWER);
            $mdb->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_RTRIM | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
            break;
        default:
            switch ($mdb->phptype) {
            case 'oci8':
                $mdb->setOption('emulate_database', false);
                break;
            }
            $mdb->setOption('field_case', CASE_LOWER);
            $mdb->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
        }

        return $mdb;
    }

    /**
     * Return a string containing an <option> listing of the given
     * gallery array.
     *
     * @param array $params  An array of options:
     *   <pre>
     *     (integer)selected  The gallery_id of the gallery that is selected
     *     (integer)perm      The permissions filter to use [Horde_Perms::SHOW]
     *     (mixed)filter      Restrict the galleries returned to those matching
     *                        the filters. Can be an array of attribute/values
     *                        pairs or a gallery owner username.
     *     (boolean)allLevels
     *     (integer)parent    The parent share to start listing at.
     *     (integer)from      The gallery to start listing at.
     *     (integer)count     The number of galleries to return.
     *     (integer)ignore    An Ansel_Gallery id to ignore when building the tree.
     *   </pre>
     *
     * @return string  The HTML to display the option list.
     */
    static public function selectGalleries($params = array())
    {
        $params = new Horde_Support_Array($params);
        $galleries = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getScope()
            ->listGalleries($params);

        $tree = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Tree')->create('gallery_tree', 'Select');

        /* Remove the ignored gallery, make sure it's also not the selected
         * gallery */
        if ($params->ignore) {
           if ($params->selected == $params->ignore) {
               $params->selected = null;
           }
        }

        foreach ($galleries as $gallery_id => $gallery) {
            // We don't use $gallery->getParents() on purpose since we
            // only need the count of parents. This potentially saves a number
            // of DB queries.
            $parents = $gallery->get('parents');
            $indents = empty($parents) ? 0 : substr_count($parents, ':') + 1;
            $gallery_name = $gallery->get('name');
            $len = Horde_String::length($gallery_name);
            if ($len > 30) {
                $label = Horde_String::substr($gallery_name, 0, 30) . '...';
            } else {
                $label = $gallery_name;
            }
            $treeparams = array();
            $treeparams['selected'] = $gallery_id == $params->selected;
            $parent = $gallery->getParent();
            $parent = (empty($params['parent'])) ? null : $params['parent']->id;
            if ((!empty($params['parent']) && !empty($galleries[$params['parent']])) ||
                (empty($params['parent']))) {

                $tree->addNode($gallery->id, $parent, $label, $indents, true, $treeparams);
            }
        }

        return $tree->getTree();
    }

    /**
     * This photo should be used as a placeholder if the correct photo can't
     * be retrieved
     *
     * @param string $view  The view ('screen', 'thumb', or 'full') to show.
     *                      Defaults to 'screen'.
     *
     * @return string  The image path.
     */
    static public function getErrorImage($view = 'screen')
    {
        return Horde_Themes::img($view . '-error.png');
    }

    /**
     * Return a properly formatted link depending on the global pretty url
     * configuration
     *
     * @param string $controller       The controller to generate a URL for.
     * @param array $data              The data needed to generate the URL.
     * @param boolean $full            Generate a full URL.
     * @param integer $append_session  0 = only if needed, 1 = always, -1 = never.
     *
     * @return Horde_Url The generated URL
     */
    static public function getUrlFor($controller, $data, $full = false, $append_session = 0)
    {
        global $prefs;

        $rewrite = isset($GLOBALS['conf']['urls']['pretty']) &&
                   $GLOBALS['conf']['urls']['pretty'] == 'rewrite';

        switch ($controller ) {
        case 'view':
            if ($rewrite && (empty($data['special']))) {
                $url = '';

                /* Viewing a List */
                if ($data['view'] == 'List') {

                    $groupby = isset($data['groupby'])
                        ? $data['groupby']
                        : $prefs->getValue('groupby');
                    if ($groupby == 'owner' && !empty($data['owner'])) {
                        $url = 'user/' . urlencode($data['owner']) . '/';
                    } elseif ($groupby == 'owner') {
                        $url = 'user/';
                    } elseif ($groupby == 'none') {
                       $url = 'all/';
                    }
                    $url = Horde::url($url, $full, $append_session);
                    // Keep the URL as clean as possible - don't append the page
                    // number if it's zero, which would be the default.
                    if (!empty($data['page'])) {
                        $url->add('page', $data['page']);
                    }
                    return $url;
                }

                /* Viewing a Gallery or Image */
                if ($data['view'] == 'Gallery' || $data['view'] == 'Image') {

                    /**
                     * @TODO: This is needed to correctly generate URLs for images in
                     * places that are not specifically requested by the user,
                     * for instance, in a gallery block. Otherwise, the proper
                     * date variables would not be attached to the url, since we
                     * don't know them ahead of time.  This is a slight hack and
                     * needs to be corrected, probably by delegating at least
                     * some of the URL generation to the gallery/image/view
                     * object...most likely when we move to PHP5.
                     */
                    if (empty($data['year']) && $data['view'] == 'Image') {
                        // Getting these objects is not ideal, but at this point
                        // they should already be locally cached so the cost
                        // is minimized.
                        $i = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($data['image']);
                        $g = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($data['gallery']);
                        if ($g->get('view_mode') == 'Date') {
                            $imgDate = new Horde_Date($i->originalDate);
                            $data['year'] = $imgDate->year;
                            $data['month'] = $imgDate->month;
                            $data['day'] = $imgDate->mday;
                        }
                    }

                    $url = 'gallery/'
                        . (!empty($data['slug'])
                           ? $data['slug']
                           : 'id/' . (int)$data['gallery'])
                        . '/';

                    // See comments below about lightbox
                    if ($data['view'] == 'Image' &&
                        (empty($data['gallery_view']) ||
                         (!empty($data['gallery_view']) &&
                         $data['gallery_view'] != 'GalleryLightbox'))) {

                        $url .= (int)$data['image'] . '/';
                    }

                    $extras = array();
                    // We may have a value of zero here, but it's the default,
                    // so ignore it if it's empty.
                    if (!empty($data['havesearch'])) {
                        $extras['havesearch'] = $data['havesearch'];
                    }

                    // Block any auto navigation (for date views)
                    if (!empty($data['force_grouping'])) {
                        $extras['force_grouping'] = $data['force_grouping'];
                    }

                    $url = new Horde_Url($url);
                    if (count($extras)) {
                        $url->add($extras);
                    }

                    /* Slight hack until we delegate at least some of the url
                     * generation to the gallery/image/view object. */
                    if ($data['view'] == 'Image' &&
                        !empty($data['gallery_view']) &&
                        $data['gallery_view'] == 'GalleryLightbox') {
                        $url->setAnchor($data['image']);
                    }

                } elseif ($data['view'] == 'Results')  {
                    $url = new Horde_Url('tag/' . (!empty($data['tag'])
                                     ? urlencode($data['tag']) . '/'
                                     : ''));

                    if (!empty($data['actionID'])) {
                        $url->add(array('actionID' => $data['actionID']));
                    }

                    if (!empty($data['owner'])) {
                        $url->add('owner', $data['owner']);
                    }
                }

                // Keep the URL as clean as possible - don't append the page
                // number if it's zero, which would be the default.
                if (!empty($data['page'])) {
                    $url->add('page', $data['page']);
                }

                if (!empty($data['year'])) {
                    $url->add(array('year' => $data['year'],
                                    'month' => (empty($data['month']) ? 0 : $data['month']),
                                    'day' => (empty($data['day']) ? 0 : $data['day'])));
                }

                return Horde::url($url, $full, $append_session);

            } else {
                $url = Horde::url('view.php', $full, $append_session);

                /* See note above about delegating url generation to gallery/view */
                if ($data['view'] == 'Image' &&
                    !empty($data['gallery_view']) &&
                    $data['gallery_view'] == 'GalleryLightbox') {
                    $data['view'] = 'Gallery';
                    $url->setAnchor($data['image']);
                }

                return $url->add($data)->setRaw(true);
            }

        case 'group':
            if ($rewrite) {
                if (empty($data['groupby'])) {
                    $data['groupby'] = $prefs->getValue('groupby');
                }
                if ($data['groupby'] == 'owner') {
                    $url = 'user/';
                } elseif ($data['groupby'] == 'none') {
                    $url = 'all/';
                }
                unset($data['groupby']);

                $url = Horde::url($url, $full, $append_session);
                if (count($data)) {
                    $url->add($data);
                }
                return $url;
            } else {
                return Horde::url('group.php', $full, $append_session)->add($data);
            }

        case 'rss_user':
            if ($rewrite) {
                return Horde::url('user/' . urlencode($data['owner']) . '/rss', $full, $append_session);
            } else {
                $url = Horde::url(new Horde_Url('rss.php'), $full, $append_session);
                return $url->add(array('stream_type' => 'user', 'id' => $data['owner']));
            }

        case 'rss_gallery':
            if ($rewrite) {
                $id = (!empty($data['slug'])) ? $data['slug'] : 'id/' . (int)$data['gallery'];
                return Horde::url(new Horde_Url('gallery/' . $id . '/rss'), $full, $append_session);
            } else {
                return Horde::url('rss.php', $full, $append_session)->add(
                            array('stream_type' => 'gallery',
                                  'id' => (int)$data['gallery']));
            }

        case 'default_view':
            switch ($prefs->getValue('defaultview')) {
            case 'browse':
                return Horde::url(new Horde_Url('browse.php'), $full, $append_session);

            case 'galleries':
                $url = Ansel::getUrlFor('view', array('view' => 'List'), true);
                break;

            case 'mygalleries':
            default:
               $url = Ansel::getUrlFor('view',
                                       array('view' => 'List',
                                             'owner' => $GLOBALS['registry']->getAuth(),
                                             'groupby' => 'owner'),
                                       true);
               break;
            }

            return $url;
        }
    }

    /**
     * Return a link to an image, suitable for use in an <img/> tag
     * Takes into account $conf['vfs']['direct'] and other
     * factors.
     *
     * @param string $imageId     The id of the image.
     * @param string $view        The view ('screen', 'thumb', 'prettythumb' or
     *                            'full') to show.
     * @param boolean $full       Return a path that includes the server name?
     * @param Ansel_Style $style  Use this gallery style
     *
     * @return Horde_Url The image path.
     */
    static public function getImageUrl($imageId, $view = 'screen', $full = false, $style = null)
    {
        global $conf;

        // To avoid having to add a new img/* file everytime we add a new
        // thumbstyle, we check for the 'non-prettythumb' views, then route the
        // rest through prettythumb, passing it the style.
        switch ($view) {
        case 'screen':
        case 'full':
        case 'thumb':
        case 'mini':
            // Do nothing.
            break;
        default:
            $view = 'prettythumb';
        }

        if (empty($imageId)) {
            return Horde::url((string)Ansel::getErrorImage($view), $full);
        }

        // Default to ansel_default since we really only need to know the style
        // if we are requesting a 'prettythumb'
        if (is_null($style)) {
            $style = Ansel::getStyleDefinition('ansel_default');
        }

        // Don't load the image if the view exists
        if ($conf['vfs']['src'] != 'php' &&
            ($viewHash = Ansel_Image::viewExists($imageId, $view, $style)) === false) {
            // We have to make sure the image exists first, since we won't
            // be going through img/*.php to auto-create it.
            try {
                $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($imageId);
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return Horde::url((string)Ansel::getErrorImage($view), $full);
            }
            try {
                $image->createView($view, $style, false);
            } catch (Ansel_Exception $e) {
                return Horde::url((string)Ansel::getErrorImage($view), $full);
            }
            $viewHash = $image->getViewHash($view, $style) . '/' . $image->getVFSName($view);
        }

        // First check for vfs-direct. If we are not using it, pass this off to
        // the img/*.php files, and check for sendfile support there.
        if ($conf['vfs']['src'] != 'direct') {
            $params = array('image' => $imageId);
            if (!is_null($style)) {
                $params['t'] = $style->thumbstyle;
                $params['b'] = $style->background;
            }

            return Horde::url('img/' . $view . '.php', $full)->add($params);
        }

        // Using vfs-direct
        $path = substr(str_pad($imageId, 2, 0, STR_PAD_LEFT), -2) . '/' . $viewHash;
        if ($full && substr($conf['vfs']['path'], 0, 7) != 'http://') {
            return Horde::url($conf['vfs']['path'] . $path, true, -1);
        } else {
            return new Horde_Url($conf['vfs']['path'] . htmlspecialchars($path));
        }
    }

    /**
     * Obtain a Horde_Image object
     *
     * @param array $params  Any additional parameters
     *
     * @return Horde_Image object
     */
    static public function getImageObject($params = array())
    {
        global $conf;
        $context = array('tmpdir' => Horde::getTempDir(),
                         'logger' => $GLOBALS['injector']->getInstance('Horde_Log_Logger'));
        if (!empty($conf['image']['convert'])) {
            $context['convert'] = $conf['image']['convert'];
            $context['identify'] = $conf['image']['identify'];
        }
        $params = array_merge(array('type' => $conf['image']['type'],
                                    'context' => $context),
                              $params);

        $driver = $conf['image']['driver'];
        return Horde_Image::factory($driver, $params);
    }

    /**
     * Read an image from the filesystem.
     *
     * @param string $file     The filename of the image.
     * @param array $override  Overwrite the file array with these values.
     *
     * @return array  The image data of the file as an array
     * @throws Horde_Exception_NotFound
     */
    static public function getImageFromFile($file, $override = array())
    {
        if (!file_exists($file)) {
            throw new Horde_Exception_NotFound(sprintf(_("The file \"%s\" doesn't exist."), $file));
        }

        global $conf;

        // Get the mime type of the file (and make sure it's an image).
        $mime_type = Horde_Mime_Magic::analyzeFile($file, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
        if (strpos($mime_type, 'image') === false) {
            throw new Horde_Exception_NotFound(sprintf(_("Can't get unknown file type \"%s\"."), $file));
        }

        $image = array('image_filename' => basename($file),
                       'image_caption' => '',
                       'image_type' => $mime_type,
                       'data' => file_get_contents($file),
                       );

        // Override the array, for example if we're setting the filename to
        // something else.
        if (count($override)) {
            $image = array_merge($image, $override);
        }

        return $image;
    }

    /**
     * Check to see if a particular image manipulation function is
     * available.
     *
     * @param string $feature  The name of the function.
     *
     * @return boolean  True if the function is available.
     */
    static public function isAvailable($feature)
    {
        static $capabilities;

        // If the administrator locked auto watermark on, disable user
        // intervention
        if ($feature == 'text_watermark' &&
            $GLOBALS['prefs']->getValue('watermark_auto') &&
            $GLOBALS['prefs']->isLocked('watermark_auto')) {

            return false;
        }

        if (!isset($capabilities)) {
            $im = Ansel::getImageObject();
            $capabilities = array_merge($im->getCapabilities(),
                                        $im->getLoadedEffects());
        }

        return in_array($feature, $capabilities);
    }

    /**
     * Generate a list of breadcrumbs showing where we are in the gallery
     * tree.
     *
     * @return string
     */
    static public function getBreadCrumbs($gallery = null, $separator = ' &raquo; ')
    {
        global $prefs;

        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();
        $groupby = Horde_Util::getFormData('groupby', $prefs->getValue('groupby'));
        $owner = Horde_Util::getFormData('owner');
        $image_id = (int)Horde_Util::getFormData('image');
        $actionID = Horde_Util::getFormData('actionID');
        $page = Horde_Util::getFormData('page', 0);
        $haveSearch = Horde_Util::getFormData('havesearch', 0);

        if (is_null($gallery)) {
            $gallery_id = (int)Horde_Util::getFormData('gallery');
            $gallery_slug = Horde_Util::getFormData('slug');
            try {
                if (!empty($gallery_slug)) {
                    $gallery = $ansel_storage->getGalleryBySlug($gallery_slug);
                } elseif (!empty($gallery_id)) {
                    $gallery = $ansel_storage->getGallery($gallery_id);
                }
            } catch (Ansel_Exception $e) {}
        }

        if ($gallery) {
            $owner = $gallery->get('owner');
        }

        if (!empty($image_id)) {
            $image = $ansel_storage->getImage($image_id);
            if (empty($gallery)) {
                $gallery = $ansel_storage->getGallery($image->gallery);
            }
        }
        if (isset($gallery)) {
            $owner = $gallery->get('owner');
        }
        if (!empty($owner)) {
            if (!$owner) {
                $owner_title = _("System Galleries");
            } elseif ($owner == $GLOBALS['registry']->getAuth()) {
                $owner_title = _("My Galleries");
            } elseif (!empty($GLOBALS['conf']['gallery']['customlabel'])) {
                $uprefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('ansel', array(
                    'cache' => false,
                    'user' => $owner
                ));
                $fullname = $uprefs->getValue('grouptitle');
                if (!$fullname) {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($owner);
                    $fullname = $identity->getValue('fullname');
                    if (!$fullname) {
                        $fullname = $owner;
                    }
                    $owner_title = sprintf(_("%s's Galleries"), $fullname);
                } else {
                    $owner_title = $fullname;
                }
            } else {
                $owner_title = sprintf(_("%s's Galleries"), $owner);
            }
        }

        // Construct the breadcrumbs backward, from where we are now up through
        // the path back to the top.  By constructing it backward we can treat
        // the last element (the current page) specially.
        $levels = 0;
        $nav = '</span>';
        $urlFlags = array('havesearch' => $haveSearch,
                          'force_grouping' => true);

        // Check for an active image
        if (!empty($image_id)) {
            $text = '<span class="thiscrumb" id="PhotoName">' . htmlspecialchars($image->filename) . '</span>';
            $nav = $separator . $text . $nav;
            $levels++;
        }

        if ($gallery) {
            $trails = $gallery->getGalleryCrumbData();
            foreach ($trails as $trail) {
                $title = $trail['title'];
                $navdata = $trail['navdata'];
                if ($levels++ > 0) {
                    if ((empty($image_id) && $levels == 1) ||
                        (!empty($image_id) && $levels == 2)) {
                        $urlParameters = array_merge($urlFlags, array('page' => $page));
                    } else {
                        $urlParameters = $urlFlags;
                    }
                    $nav = $separator . Ansel::getUrlFor('view', array_merge($navdata, $urlParameters))->link() . $title . '</a>' . $nav;
                } else {
                    $nav = $separator . '<span class="thiscrumb">' . $title . '</span>' . $nav;
                }
            }
        }

        if (!empty($owner_title)) {
            $owner_title = htmlspecialchars($owner_title);
            $levels++;
            if ($gallery) {
                $nav = $separator . Ansel::getUrlFor('view', array('view' => 'List', 'groupby' => 'owner', 'owner' => $owner, 'havesearch' => $haveSearch))->link() . $owner_title . '</a>' . $nav;
            } else {
                $nav = $separator . $owner_title . $nav;
            }
        }

        if ($haveSearch == 0) {
            $text = _("Galleries");
            $link = Ansel::getUrlFor('view', array('view' => 'List'))->link();
        } else {
            $text = _("Browse Tags");
            $link = Ansel::getUrlFor('view', array('view' => 'Results'), true)->link();
        }
        if ($levels > 0) {
            $nav = $link . $text . '</a>' . $nav;
        } else {
            $nav = $text . $nav;
        }

        return '<span class="breadcrumbs">' . $nav . '</span>';
    }

    /**
     * Build a HTML <select> element containing all the available
     * gallery styles.
     *
     * @param string $element_name  The element's id/name attribute.
     * @param string $selected      Mark this element as currently selected.
     *
     * @return string  The HTML for the <select> element.
     */
    static public function getStyleSelect($element_name, $selected = '')
    {
        $styles = $GLOBALS['injector']->getInstance('Ansel_Styles');

        // @TODO: Look at this code: probably duplicated in the binder above.
        /* No prettythumbs allowed at all by admin choice */
        if (empty($GLOBALS['conf']['image']['prettythumbs'])) {
            $test = $styles;
            foreach ($test as $key => $style) {
                if ($style['thumbstyle'] != 'thumb') {
                    unset($styles[$key]);
                }
            }
        }

        /* Build the available styles, but don't show hidden styles */
        foreach ($styles as $key => $style) {
            if (empty($style['hide'])) {
                $options[$key] = $style['title'];
            }
        }

        /* Nothing explicitly selected, use the global pref */
        if ($selected == '') {
            $selected = $GLOBALS['prefs']->getValue('default_gallerystyle');
        }

        $html = '<select id="' . $element_name . '" name="' . $element_name . '">';
        foreach ($options as $key => $option) {
            $html .= '  <option value="' . $key . '"' . (($selected == $key) ? 'selected="selected"' : '') . '>' . $option . '</option>';
        }

        return $html .= '</select>';
    }

    /**
     * Get a pre-defined style definition for the requested named style
     *
     * @param string $style  The name of the style to fetch
     *
     * @return Ansel_Style   The definition of the requested style if it's
     *                       available, otherwise, the ansel_default style is
     *                       returned.
     */
    static public function getStyleDefinition($style)
    {
        $styles = $GLOBALS['injector']->getInstance('Ansel_Styles');
        if (isset($styles[$style])) {
            return new Ansel_Style($styles[$style]);
        } else {
            return  new Ansel_Style($styles['ansel_default']);
        }
    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view        The view (thumb, prettythumb etc...)
     * @param Ansel_Style $style  The style object.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    static public function getViewHash($view, $style)
    {
        if ($view != 'screen' && $view != 'thumb' && $view != 'mini' &&
            $view != 'full') {

            $view = md5($style->thumbstyle . '.' . $style->background);
        }

        return $view;
    }

    /**
     * Add a custom stylesheet to the current page. Need our own implementation
     * since we want to be able to ouput specific CSS files at specific times
     * (like when rendering embedded content, or calling via the api etc...).
     *
     * @param string $stylesheet  The stylesheet to add. A path relative
     *                            to $themesfs
     * @param boolean $link       Immediately output the CSS link
     */
    static public function attachStylesheet($stylesheet, $link = false)
    {
       $GLOBALS['ansel_stylesheets'][] = $stylesheet;
       if ($link) {
           Ansel::stylesheetLinks(true);
       }
    }

    /**
     * Output the stylesheet links
     *
     * @param boolean $custom_only  Don't include ansel's base CSS file
     */
    static public function stylesheetLinks($custom_only = false)
    {
        /* Custom CSS */
        $themesuri = $GLOBALS['registry']->get('themesuri', 'ansel');
        $themesfs = $GLOBALS['registry']->get('themesfs', 'ansel');
        $css = array();
        if (!empty($GLOBALS['ansel_stylesheets'])) {
            foreach ($GLOBALS['ansel_stylesheets'] as $css_file) {
                $css[] = array('u' => Horde::url($themesuri . '/' . $css_file, true),
                               'f' => $themesfs . '/' . $css_file);
            }
        }

        /* Use Horde's stylesheet code if we aren't ouputting css directly */
        if (!$custom_only) {
            Horde_Themes::includeStylesheetFiles(array('additional' => $css));
        } else {
            foreach ($css as $file) {
                echo '<link href="' . $file['u']
                     . '" rel="stylesheet" type="text/css"'
                     . (isset($file['m']) ? ' media="' . $file['m'] . '"' : '')
                     . ' />' . "\n";
            }
        }
    }

    /**
     * Get a date parts array containing only enough date parts for the depth
     * we are at. If an empty array is passed, attempt to get the parts from
     * url parametrs. Any missing date parts must be set to 0.
     *
     * @param array $date  A full date parts array or an empty array.
     *
     * @return array A trimmed down (if necessary) date parts array.
     */
    static public function getDateParameter($date = array())
    {
        if (!count($date)) {
            $date = array(
                'year' => Horde_Util::getFormData('year', 0),
                'month' => Horde_Util::getFormData('month', 0),
                'day' => Horde_Util::getFormData('day', 0));
        }
        $return = array();
        $return['year'] = !empty($date['year']) ? $date['year'] : 0;
        $return['month'] = !empty($date['month']) ? $date['month'] : 0;
        $return['day'] = !empty($date['day']) ? $date['day'] : 0;

        return $return;
    }

    /**
     * Downloads all requested images as a zip file.  Assumes all permissions
     * have been checked on the requested resource.  Can request either a
     * single gallery of images, OR an array of individual image ids.
     *
     * @param Ansel_Gallery $gallery  The galleries to download
     * @param array $images           The images to download
     */
    static public function downloadImagesAsZip($gallery = null, $images = array())
    {

        if (empty($GLOBALS['conf']['gallery']['downloadzip'])) {
            $GLOBALS['notification']->push(_("Downloading zip files is not enabled. Talk to your server administrator."));
            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }

        /* Requested a gallery */
        if (!is_null($gallery)) {
            /* We can name the zip file with the slug if we have it */
            $slug = $gallery->get('slug');

            /* Set the date in case we are viewing in date mode */
            $gallery->setDate(Ansel::getDateParameter());

            /*
             * More efficeint to get the images and then see how many instead of calling
             * countImages() and then getting the images.
             */
            $images = $gallery->listImages();
        }

        /* At this point, we should always have a list of images */
        if (!count($images)) {
            $notification->push(sprintf(_("There are no photos in %s to download."),
                                $gallery->get('name')), 'horde.message');

            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }

        // Try to close off the current session to avoid locking it while the
        // gallery is downloading.
        @session_write_close();

        if (!is_null($gallery)) {
            // Check full photo permissions
            if ($gallery->canDownload()) {
                $view = 'full';
            } else {
                $view = 'screen';
            }
        }

        $zipfiles = array();
        foreach ($images as $id) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($id);
            // If we didn't select an entire gallery, check the download
            // size for each image.
            if (!isset($view)) {
                $g = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($image->gallery);
                $v = $g->canDownload() ? 'full' : 'screen';
            } else {
                $v = $view;
            }

            $zipfiles[] = array('data' => $image->raw($v),
                                'name' => $image->filename);
        }

        $zip = Horde_Compress::factory('zip');
        $body = $zip->compress($zipfiles);
        if (!empty($gallery)) {
            $filename = (!empty($slug) ? $slug : $gallery->id) . '.zip';
        } else {
            $filename = 'Ansel.zip';
        }
        $GLOBALS['browser']->downloadHeaders($filename, 'application/zip', false,
                                  strlen($body));
        echo $body;
        exit;
    }

    /**
     * Generate the JS necessary to embed a gallery / images into another
     * external site.
     *
     * @param array $options  The options to build the view.
     *
     * @return string  The javascript code
     */
    static public function embedCode($options)
    {
        if (empty($options['container'])) {
            $domid = uniqid();
            $options['container'] = $domid;
        } else {
            $domid = $options['container'];
        }

        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('ansel', 'Embed'), $options);

       return '<script type="text/javascript" src="' . $imple->getUrl() . '"></script><div id="' . $domid . '"></div>';
    }

    /**
     * Get the URL for a tag search link
     *
     * @TODO: Move this to Tagger
     *
     * @param array $tags      The tag ids to link to
     * @param string $action   The action we want to perform with this tag.
     * @param string $owner    The owner we want to filter the results by
     *
     * @return string  The URL for this tag and action
     */
    static public function getTagLinks($tags, $action = 'add', $owner = null)
    {

        $results = array();
        foreach ($tags as $id => $taginfo) {
            $params = array('view' => 'Results',
                            'tag' => $taginfo['tag_name']);
            if (!empty($owner)) {
                $params['owner'] = $owner;
            }
            if ($action != 'add') {
                $params['actionID'] = $action;
            }
            $link = Ansel::getUrlFor('view', $params, true);
            $results[$id] = $link;
        }

        return $results;
    }

}
