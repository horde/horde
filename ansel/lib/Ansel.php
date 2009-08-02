<?php
/**
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/** Horde_Share */
require_once 'Horde/Share.php';

/** Need to bring this in explicitly since we extend the object class */
require_once 'Horde/Share/sql_hierarchical.php';

/**
 * Ansel Base Class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel {

    /**
     * Build initial Ansel javascript object.
     *
     * @return string
     */
    function initJSVars()
    {
        $code = array('Ansel = {ajax: {}, widgets: {}}');
        return $code;
    }

    /**
     * Create and initialize the database object.
     *
     * @return mixed MDB2 object || PEAR_Error
     */
    function &getDb()
    {
        $config = $GLOBALS['conf']['sql'];
        unset($config['charset']);
        $mdb = MDB2::singleton($config);
        if (is_a($mdb, 'PEAR_Error')) {
            return $mdb;
        }
        $mdb->setOption('seqcol_name', 'id');

        /* Set DB portability options. */
        switch ($mdb->phptype) {
        case 'mssql':
            $mdb->setOption('field_case', CASE_LOWER);
            $mdb->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_RTRIM | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
            break;
        default:
            $mdb->setOption('field_case', CASE_LOWER);
            $mdb->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
        }

        return $mdb;
    }

    /**
     * Create and initialize the VFS object
     *
     * @return VFS object or fatals on error.
     */
    function &getVFS()
    {
        $v_params = Horde::getVFSConfig('images');
        if (is_a($v_params, 'PEAR_Error')) {
            Horde::fatal(_("You must configure a VFS backend to use Ansel."),
                         __FILE__, __LINE__);
        }
        if ($v_params['type'] != 'none') {
            $vfs = VFS::singleton($v_params['type'], $v_params['params']);
        }
        if (empty($vfs) || is_a($vfs, 'PEAR_ERROR')) {
            Horde::fatal(_("You must configure a VFS backend to use Ansel."),
                         __FILE__, __LINE__);
        }
        return $vfs;
    }

    /**
     * Return a string containing an <option> listing of the given
     * gallery array.
     *
     * @param array $selected     The gallery_id of the  gallery that is
     *                            selected by default in the returned option
     *                            list.
     * @param integer $perm       The permissions filter to use.
     * @param mixed $attributes   Restrict the galleries returned to those
     *                            matching $attributes. An array of
     *                            attribute/values pairs or a gallery owner
     *                            username.
     * @param string $parent      The parent share to start listing at.
     * @param integer $from       The gallery to start listing at.
     * @param integer $count      The number of galleries to return.
     * @param integer $ignore     An Ansel_Gallery id to ignore when building
     *                            the tree.
     *
     * @return string  The <option> list.
     */
    function selectGalleries($selected = null, $perm = PERMS_SHOW,
                             $attributes = null, $parent = null,
                             $allLevels = true, $from = 0, $count = 0,
                             $ignore = null)
    {
        global $ansel_storage;
        $galleries = $ansel_storage->listGalleries($perm, $attributes, $parent,
                                                   $allLevels, $from, $count);
        $tree = Horde_Tree::factory('gallery_tree', 'select');

        if (!empty($ignore)) {
           unset($galleries[$ignore]);
           if ($selected == $ignore) {
               $selected = null;
           }
        }
        foreach ($galleries as $gallery_id => $gallery) {
            // We don't use $gallery->getParents() on purpose since we
            // only need the count of parents. This potentially saves a number
            // of DB queries.
            $parents = $gallery->get('parents');
            if (empty($parents)) {
                $indents = 0;
            } else {
                $indents = substr_count($parents, ':') + 1;
            }

            $gallery_name = $gallery->get('name');
            $len = Horde_String::length($gallery_name);
            if ($len > 30) {
                $label = Horde_String::substr($gallery_name, 0, 30) . '...';
            } else {
                $label = $gallery_name;
            }

            $params['selected'] = ($gallery_id == $selected);
            $parent = $gallery->getParent();
            $parent = (is_null($parent)) ? $parent : $parent->id;
            if ((!empty($parent) && !empty($galleries[$parent])) ||
                (empty($parent))) {
                $tree->addNode($gallery->id, $parent, $label, $indents, true,
                               $params);
            }
        }

        return $tree->getTree();
    }

    /**
     * Return a link to a photo placeholder, suitable for use in an <img/>
     * tag (or a Horde::img() call, with the path parameter set to * '').
     * This photo should be used as a placeholder if the correct photo can't
     * be retrieved
     *
     * @param string $view  The view ('screen', 'thumb', or 'full') to show.
     *                      Defaults to 'screen'.
     *
     * @return string  The image path.
     */
    function getErrorImage($view = 'screen')
    {
        return $GLOBALS['registry']->getImageDir() . '/' . $view . '-error.png';
    }

    /**
     * Return a properly formatted link depending on the global pretty url
     * configuration
     *
     * @param string $controller       The controller to generate a URL for.
     * @param array $data              The data needed to generate the URL.
     * @param boolean $full            Generate a full URL.
     * @param integer $append_session  0 = only if needed, 1 = always,
     *                                 -1 = never.
     *
     * @param string  The generated URL
     */
    function getUrlFor($controller, $data, $full = false, $append_session = 0)
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
                    if (!empty($data['groupby']) &&
                        $data['groupby'] == 'category' &&
                        empty($data['category']) &&
                        empty($data['special'])) {

                        $data['groupby'] = 'owner';
                    }

                    $groupby = isset($data['groupby'])
                        ? $data['groupby']
                        : $prefs->getValue('groupby');
                    if ($groupby == 'owner' && !empty($data['owner'])) {
                        $url = 'user/' . urlencode($data['owner']) . '/';
                    } elseif ($groupby == 'owner') {
                        $url = 'user/';
                    } elseif ($groupby == 'category' &&
                              !empty($data['category'])) {
                            $url = 'category/' . urlencode($data['category']) . '/';

                    } elseif ($groupby == 'category') {
                        $url = 'category/';
                    } elseif ($groupby == 'none') {
                       $url = 'all/';
                    }

                    // Keep the URL as clean as possible - don't append the page
                    // number if it's zero, which would be the default.
                    if (!empty($data['page'])) {
                        $url = Horde_Util::addParameter($url, 'page', $data['page']);
                    }
                    return Horde::applicationUrl($url, $full, $append_session);
                }

                /* Viewing a Gallery or Image */
                if ($data['view'] == 'Gallery' || $data['view'] == 'Image') {

                    /**
                     * This is needed to correctly generate URLs for images in
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
                        $i = &$GLOBALS['ansel_storage']->getImage($data['image']);
                        $g = &$GLOBALS['ansel_storage']->getGallery($data['gallery']);
                        if (!is_a($g, 'PEAR_Error') &&
                            !is_a($i, 'PEAR_Error') &&
                            $g->get('view_mode') == 'Date') {

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

                    if (count($extras)) {
                        $url = Horde_Util::addParameter($url, $extras);
                    }

                }

                if ($data['view'] == 'Results')  {
                    $url = 'tag/' . (!empty($data['tag'])
                                     ? urlencode($data['tag']) . '/'
                                     : '');

                    if (!empty($data['actionID'])) {
                        $url = Horde_Util::addParameter($url, 'actionID',
                                                  $data['actionID']);
                    }

                    if (!empty($data['owner'])) {
                        $url = Horde_Util::addParameter($url, 'owner',
                                                  $data['owner']);
                    }
                }

                // Keep the URL as clean as possible - don't append the page
                // number if it's zero, which would be the default.
                if (!empty($data['page'])) {
                    $url = Horde_Util::addParameter($url, 'page', $data['page']);
                }

                if (!empty($data['year'])) {
                    $url = Horde_Util::addParameter($url, array('year' => $data['year'],
                                                          'month' => (empty($data['month']) ? 0 : $data['month']),
                                                          'day' => (empty($data['day']) ? 0 : $data['day'])));
                }

                // If we are using GalleryLightbox, AND we are linking to an
                // image view, append the imageId here to be sure it's at the
                // end of the URL. This is a complete hack, but saves us from
                // having to delegate the URL generation to the view object for
                // now.
                if ($data['view'] == 'Image' &&
                    !empty($data['gallery_view']) &&
                    $data['gallery_view'] == 'GalleryLightbox') {

                    $url .= '#' . $data['image'];
                }

                return Horde::applicationUrl($url, $full, $append_session);
            } else {
                $url = Horde::applicationUrl(
                         Horde_Util::addParameter('view.php', $data),
                         $full,
                         $append_session);

                if ($data['view'] == 'Image' &&
                    !empty($data['gallery_view']) &&
                    $data['gallery_view'] == 'GalleryLightbox') {

                    $url .= '#' . $data['image'];
                }

                return $url;

            }
            break;

        case 'group':
            if ($rewrite) {
                if (empty($data['groupby'])) {
                    $data['groupby'] = $prefs->getValue('groupby');
                }

                if ($data['groupby'] == 'owner') {
                    $url = 'user/';
                }
                if ($data['groupby'] == 'category') {
                    $url = 'category/';
                }
                if ($data['groupby'] == 'none') {
                    $url = 'all/';
                }
                unset($data['groupby']);
                if (count($data)) {
                    $url = Horde_Util::addParameter($url,$data);
                }
                return Horde::applicationUrl($url, $full, $append_session);
            } else {
                return Horde::applicationUrl(
                    Horde_Util::addParameter('group.php', $data),
                    $full,
                    $append_session);
            }
            break;

        case 'rss_user':
            if ($rewrite) {
                $url = 'user/' . urlencode($data['owner']) . '/rss';
                return Horde::applicationUrl($url, $full, $append_session);
            } else {
                return Horde::applicationUrl(
                    Horde_Util::addParameter('rss.php',
                                       array('stream_type' => 'user',
                                             'id' => $data['owner'])),
                    $full, $append_session);
            }
            break;

        case 'rss_gallery':
            if ($rewrite) {
                $id = (!empty($data['slug'])) ? $data['slug'] : 'id/' . (int)$data['gallery'];
                $url = 'gallery/' . $id . '/rss';
                return Horde::applicationUrl($url, $full, $append_session);
            } else {
                return Horde::applicationUrl(
                    Horde_Util::addParameter('rss.php',
                                       array('stream_type' => 'gallery',
                                             'id' => (int)$data['gallery'])),
                    $full, $append_session);
            }
            break;

        case 'default_view':
            switch ($prefs->getValue('defaultview')) {
            case 'browse':
                $url = 'browse.php';
                return Horde::applicationUrl($url, $full, $append_session);
                break;

            case 'galleries':
                $url = Ansel::getUrlFor('view', array('view' => 'List'), true);
                break;

            case 'mygalleries':
            default:
               $url = Ansel::getUrlFor('view',
                                       array('view' => 'List',
                                             'owner' => Horde_Auth::getAuth(),
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
     * @param string $imageId  The id of the image.
     * @param string $view     The view ('screen', 'thumb', 'prettythumb' or
     *                         'full') to show.
     * @param boolean $full    Return a path that includes the server name?
     * @param string $style    Use this gallery style
     *
     * @return string  The image path.
     */
    function getImageUrl($imageId, $view = 'screen', $full = false,
                         $style = null)
    {
        global $conf, $ansel_storage;

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
            return Ansel::getErrorImage($view);
        }

        // Default to ansel_default since we really only need to know the style
        // if we are requesting a 'prettythumb'
        if (is_null($style)) {
            $style = 'ansel_default';
        }

        // Don't load the image if the view exists
        if ($conf['vfs']['src'] != 'php' &&
            ($viewHash = Ansel_Image::viewExists($imageId, $view, $style)) === false) {
            // We have to make sure the image exists first, since we won't
            // be going through img/*.php to auto-create it.
            if (is_a($image = $ansel_storage->getImage($imageId), 'PEAR_Error')) {
                return Ansel::getErrorImage($view);
            }
            if (is_a($result = $image->createView($view, $style, false), 'PEAR_Error')) {
                return Ansel::getErrorImage($view);
            }
            $viewHash = $image->_getViewHash($view, $style) . '/'
                . $image->getVFSName($view);
        }

        // First check for vfs-direct. If we are not using it, pass this off to
        // the img/*.php files, and check for sendfile support there.
        if ($conf['vfs']['src'] != 'direct') {
            $params = array('image' => $imageId);
            if (!is_null($style)) {
                $params['style'] = $style;
            }
            $url = Horde_Util::addParameter('img/' . $view . '.php', $params);
            return Horde::applicationUrl($url, $full);
        }

        // Using vfs-direct
        $path = substr(str_pad($imageId, 2, 0, STR_PAD_LEFT), -2) . '/'
            . $viewHash;
        if ($full && substr($conf['vfs']['path'], 0, 7) != 'http://') {
            return Horde::url($conf['vfs']['path'] . $path, true, -1);
        } else {
            return $conf['vfs']['path'] . htmlspecialchars($path);
        }
    }

    /**
     * Obtain a Horde_Image object
     *
     * @param array $params  Any additional parameters
     *
     * @return Horde_Image object | PEAR_Error
     */
    function getImageObject($params = array())
    {
        global $conf;
        $context = array('tmpdir' => Horde::getTempDir());
        if (!empty($conf['image']['convert'])) {
            $context['convert'] = $conf['image']['convert'];
        }
        $params = array_merge(array('type' => $conf['image']['type'],
                                    'context' => $context),
                              $params);
        //@TODO: get around to updating horde/config/conf.xml to include the imagick driver
        $driver = empty($conf['image']['convert']) ? 'Gd' : 'Im';
        return Horde_Image::factory($driver, $params);
    }

    /**
     * Read an image from the filesystem.
     *
     * @param string $file     The filename of the image.
     * @param array $override  Overwrite the file array with these values.
     *
     * @return array  The image data of the file as an array or PEAR_Error
     */
    function getImageFromFile($file, $override = array())
    {
        if (!file_exists($file)) {
            return PEAR::raiseError(sprintf(_("The file \"%s\" doesn't exist."),
                                    $file));
        }

        global $conf;

        // Get the mime type of the file (and make sure it's an image).
        $mime_type = Horde_Mime_Magic::analyzeFile($file, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
        if (strpos($mime_type, 'image') === false) {
            return PEAR::raiseError(sprintf(_("Can't get unknown file type \"%s\"."), $file));
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
    function isAvailable($feature)
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
     * Build Ansel's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $conf, $registry;

        $menu = new Horde_Menu();

        /* Browse/Search */
        $menu->add(Horde::applicationUrl('browse.php'), _("_Browse"),
                   'browse.png', null, null, null,
                   (($GLOBALS['prefs']->getValue('defaultview') == 'browse' &&
                     basename($_SERVER['PHP_SELF']) == 'index.php') ||
                    (basename($_SERVER['PHP_SELF']) == 'browse.php'))
                   ? 'current'
                   : '__noselection');

        $menu->add(Ansel::getUrlFor('view', array('view' => 'List')), _("_Galleries"),
                   'galleries.png', null, null, null,
                   (($GLOBALS['prefs']->getValue('defaultview') == 'galleries' &&
                     basename($_SERVER['PHP_SELF']) == 'index.php') ||
                    ((basename($_SERVER['PHP_SELF']) == 'group.php') &&
                     Horde_Util::getFormData('owner') !== Horde_Auth::getAuth())
                    ? 'current'
                    : '__noselection'));
        if (Horde_Auth::getAuth()) {
            $url = Ansel::getUrlFor('view', array('owner' => Horde_Auth::getAuth(),
                                                  'groupby' => 'owner',
                                                  'view' => 'List'));
            $menu->add($url, _("_My Galleries"), 'mygalleries.png', null, null,
                       null,
                       (Horde_Util::getFormData('owner', false) == Horde_Auth::getAuth())
                       ? 'current' :
                       '__noselection');
        }

        /* Let authenticated users create new galleries. */
        if (Horde_Auth::isAdmin() ||
            (!$GLOBALS['perms']->exists('ansel') && Horde_Auth::getAuth()) ||
            $GLOBALS['perms']->hasPermission('ansel', Horde_Auth::getAuth(), PERMS_EDIT)) {
            $menu->add(Horde::applicationUrl(Horde_Util::addParameter('gallery.php', 'actionID', 'add')),
                       _("_New Gallery"), 'add.png', null, null, null,
                       (basename($_SERVER['PHP_SELF']) == 'gallery.php' &&
                        Horde_Util::getFormData('actionID') == 'add')
                       ? 'current'
                       : '__noselection');
        }

        if ($conf['faces']['driver'] && Horde_Auth::isAuthenticated()) {
            $menu->add(Horde::applicationUrl('faces/search/all.php'), _("_Faces"), 'user.png', $registry->getImageDir('horde'));
        }

        /* Print. */
        if ($conf['menu']['print'] && ($pl = Horde_Util::nonInputVar('print_link'))) {
            $menu->add($pl, _("_Print"), 'print.png',
                       $registry->getImageDir('horde'), '_blank',
                       Horde::popupJs($pl, array('urlencode' => true)) . 'return false;');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Generate a list of breadcrumbs showing where we are in the gallery
     * tree.
     */
    function getBreadCrumbs($separator = ' &raquo; ', $gallery = null)
    {
        global $prefs, $ansel_storage;

        $groupby = Horde_Util::getFormData('groupby', $prefs->getValue('groupby'));
        $owner = Horde_Util::getFormData('owner');
        $image_id = (int)Horde_Util::getFormData('image');
        $actionID = Horde_Util::getFormData('actionID');
        $page = Horde_Util::getFormData('page', 0);
        $haveSearch = Horde_Util::getFormData('havesearch', 0);

        if (is_null($gallery)) {
            $gallery_id = (int)Horde_Util::getFormData('gallery');
            $gallery_slug = Horde_Util::getFormData('slug');
            if (!empty($gallery_slug)) {
                $gallery = $ansel_storage->getGalleryBySlug($gallery_slug);
            } elseif (!empty($gallery_id)) {
                $gallery = $ansel_storage->getGallery($gallery_id);
            }
        }

        if (is_a($gallery, 'PEAR_Error')) {
            $gallery = null;
        }

        if ($gallery) {
            $owner = $gallery->get('owner');
        }

        if (!empty($image_id)) {
            $image = &$ansel_storage->getImage($image_id);
            if (empty($gallery) && !is_a($image, 'PEAR_Error')) {
                $gallery = $ansel_storage->getGallery($image->gallery);
            }
        }
        if (isset($gallery) && !is_a($gallery, 'PEAR_Error')) {
            $owner = $gallery->get('owner');
        }
        if (!empty($owner)) {
            if ($owner == Horde_Auth::getAuth()) {
                $owner_title = _("My Galleries");
            } elseif (!empty($GLOBALS['conf']['gallery']['customlabel'])) {
                $uprefs = Prefs::singleton($GLOBALS['conf']['prefs']['driver'],
                                           'ansel',
                                           $owner, '', null, false);
                $fullname = $uprefs->getValue('grouptitle');
                if (!$fullname) {
                    $identity = Identity::singleton('none', $owner);
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
        if (!empty($image_id) && !is_a($image, 'PEAR_Error')) {
            $text = '<span class="thiscrumb" id="PhotoName">' . htmlspecialchars($image->filename, ENT_COMPAT, Horde_Nls::getCharset()) . '</span>';
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
                    $nav = $separator . Horde::link(Ansel::getUrlFor('view', array_merge($navdata, $urlParameters))) . $title . '</a>' . $nav;
                } else {
                    $nav = $separator . '<span class="thiscrumb">' . $title . '</span>' . $nav;
                }
            }
        }

        if (!empty($owner_title)) {
            $owner_title = htmlspecialchars($owner_title, ENT_COMPAT, Horde_Nls::getCharset());
            $levels++;
            if ($gallery) {
                $nav = $separator . Horde::link(Ansel::getUrlFor('view', array('view' => 'List', 'groupby' => 'owner', 'owner' => $owner, 'havesearch' => $haveSearch))) . $owner_title . '</a>' . $nav;
            } else {
                $nav = $separator . $owner_title . $nav;
            }
        }

        if ($haveSearch == 0) {
            $text = _("Galleries");
            $link = Horde::link(Ansel::getUrlFor('view', array('view' => 'List')));
        } else {
            $text = _("Browse Tags");
            $link = Horde::link(Ansel::getUrlFor('view', array('view' => 'Results'), true));
        }
        if ($levels > 0) {
            $nav = $link . $text . '</a>' . $nav;
        } else {
            $nav = $text . $nav;
        }

        $nav = '<span class="breadcrumbs">' . $nav;

        return $nav;
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
    function getStyleSelect($element_name, $selected = '')
    {
        $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');

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
        $html .= '</select>';
        return $html;
    }

    /**
     * Get an array of all currently viewable styles.
     */
    function getAvailableStyles()
    {
        /* Brings in the $styles array in this scope only */
        $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');

        /* No prettythumbs allowed at all by admin choice */
        if (empty($GLOBALS['conf']['image']['prettythumbs'])) {
            $test = $styles;
            foreach ($test as $key => $style) {
                if ($style['thumbstyle'] != 'thumb') {
                    unset($styles[$key]);
                }
            }
        }

        /* Check if the browser / server has png support */
        if ($GLOBALS['browser']->hasQuirk('png_transparency') ||
            $GLOBALS['conf']['image']['type'] != 'png') {

            $test = $styles;
            foreach ($test as $key => $style) {
                if (!empty($style['requires_png'])) {
                    if (!empty($style['fallback'])) {
                        $styles[$key] = $styles[$style['fallback']];
                    } else {
                        unset($styles[$key]);
                    }
                }
            }
        }
        return $styles;
    }

    /**
     * Get a style definition for the requested named style
     *
     * @param string $style  The name of the style to fetch
     *
     * @return array  The definition of the requested style if it's available
     *                otherwise, the ansel_default style is returned.
     */
    function getStyleDefinition($style)
    {
        if (isset($GLOBALS['ansel_styles'][$style])) {
            $style_def = $GLOBALS['ansel_styles'][$style];
        } else {
            $style_def = $GLOBALS['ansel_styles']['ansel_default'];
        }

        /* Fill in defaults */
        if (empty($style_def['gallery_view'])) {
            $style_def['gallery_view'] = 'Gallery';
        }
        if (empty($style_def['default_galleryimage_type'])) {
            $style_def['default_galleryimage_type'] = 'plain';
        }
        if (empty($style_def['requires_png'])) {
            $style_def['requires_png'] = false;
        }

        return $style_def;
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
    function attachStylesheet($stylesheet, $link = false)
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
    function stylesheetLinks($custom_only = false)
    {
        /* Custom CSS */
        $themesuri = $GLOBALS['registry']->get('themesuri', 'ansel');
        $themesfs = $GLOBALS['registry']->get('themesfs', 'ansel');
        $css = array();
        if (!empty($GLOBALS['ansel_stylesheets'])) {
            foreach ($GLOBALS['ansel_stylesheets'] as $css_file) {
                $css[] = array('u' => Horde::applicationUrl($themesuri . '/' . $css_file, true),
                               'f' => $themesfs . '/' . $css_file);
            }
        }

        /* Use Horde's stylesheet code if we aren't ouputting css directly */
        if (!$custom_only) {
            Horde::includeStylesheetFiles(array('additional' => $css));
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
     * @return A trimmed down (if necessary) date parts array.
     */
    function getDateParameter($date = array())
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
     * have been checked on the requested resource.
     * @param unknown_type $images
     */
    function downloadImagesAsZip($gallery = null, $images = array())
    {

        if (empty($GLOBALS['conf']['gallery']['downloadzip'])) {
            $GLOBALS['notification']->push(_("Downloading zip files is not enabled. Talk to your server administrator."));
            header('Location: ' . Horde::applicationUrl('view.php?view=List', true));
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
            header('Location: ' . Horde::applicationUrl('view.php?view=List', true));
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
            $image = &$GLOBALS['ansel_storage']->getImage($id);
            if (!is_a($image, 'PEAR_Error')) {
                // If we didn't select an entire gallery, check the download
                // size for each image.
                if (!isset($view)) {
                    $g = $GLOBALS['ansel_storage']->getGallery($image->gallery);
                    $v = $g->canDownload() ? 'full' : 'screen';
                } else {
                    $v = $view;
                }

                $zipfiles[] = array('data' => $image->raw($v),
                                    'name' => $image->filename);
            }
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

    function embedCode($options)
    {
        if (empty($options['container'])) {
            $domid = md5(uniqid());
            $options['container'] = $domid;
        } else {
            $domid = $options['container'];
        }

        $imple = Horde_Ajax_Imple::factory(array('ansel', 'Embed'), $options);
        $src = $imple->getUrl();

       return '<script type="text/javascript" src="' . $src . '"></script><div id="' . $domid . '"></div>';
    }

}

/**
 * Class to encapsulate a single gallery. Implemented as an extension of
 * the Horde_Share_Object class.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Gallery extends Horde_Share_Object_sql_hierarchical {

    /**
     * Cache the Gallery Id - to match the Ansel_Image interface
     */
    var $id;

    /**
     * The gallery mode helper
     *
     * @var Ansel_Gallery_Mode object
     */
    var $_modeHelper;

    /**
     *
     */
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_shareOb']);
        unset($properties['_modeHelper']);
        $properties = array_keys($properties);
        return $properties;
    }

    function __wakeup()
    {
        $this->setShareOb($GLOBALS['ansel_storage']->shares);
        $mode = $this->get('view_mode');
        $this->_setModeHelper($mode);
    }

    /**
     * The Ansel_Gallery constructor.
     *
     * @param string $name  The name of the gallery
     */
    function Ansel_Gallery($attributes = array())
    {
        /* Existing gallery? */
        if (!empty($attributes['share_id'])) {
            $this->id = (int)$attributes['share_id'];
        }

        /* Pass on up the chain */
        parent::Horde_Share_Object_sql_hierarchical($attributes);
        $this->setShareOb($GLOBALS['ansel_storage']->shares);
        $mode = isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal';
        $this->_setModeHelper($mode);
    }

    /**
     * Check for special capabilities of this gallery.
     *
     */
    function hasFeature($feature)
    {

        // First check for purely Ansel_Gallery features
        // Currently we have none of these.

        // Delegate to the modeHelper
        return $this->_modeHelper->hasFeature($feature);

    }

    /**
     * Simple factory to retrieve the proper mode object.
     *
     * @param string $type  The mode to use
     *
     * @return Ansel_Gallery_Mode object
     */
    function _setModeHelper($type = 'Normal')
    {
        $type = basename($type);
        $class = 'Ansel_GalleryMode_' . $type;
        $this->_modeHelper = new $class($this);
        $this->_modeHelper->init();
    }

    /**
     * Checks if the user can download the full photo
     *
     * @return boolean  Whether or not user can download full photos
     */
    function canDownload()
    {
        if (Horde_Auth::getAuth() == $this->data['share_owner'] || Horde_Auth::isAdmin('ansel:admin')) {
            return true;
        }

        switch ($this->data['attribute_download']) {
        case 'all':
            return true;

        case 'authenticated':
            return Horde_Auth::isAuthenticated();

        case 'edit':
            return $this->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT);

        case 'hook':
            return Horde::callHook('_ansel_hook_can_download', array($this->id));

        default:
            return false;
        }
    }

    /**
     * Saves any changes to this object to the backend permanently.
     *
     * @return mixed true || PEAR_Error on failure.
     */
    function _save()
    {
        // Check for invalid characters in the slug.
        if (!empty($this->data['attribute_slug']) &&
            preg_match('/[^a-zA-Z0-9_@]/', $this->data['attribute_slug'])) {

            return PEAR::raiseError(
                sprintf(_("Could not save gallery, the slug, \"%s\", contains invalid characters."),
                        $this->data['attribute_slug']));
        }

        // Check for slug uniqueness
        $slugGalleryId = $GLOBALS['ansel_storage']->slugExists($this->data['attribute_slug']);
        if ($slugGalleryId > 0 && $slugGalleryId <> $this->id) {
            return PEAR::raiseError(sprintf(_("Could not save gallery, the slug, \"%s\", already exists."),
                                            $this->data['attribute_slug']));
        }

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_Gallery' . $this->id);
        }
        return parent::_save();
    }

    /**
     * Update the gallery image count.
     *
     * @param integer $images      Number of images in action
     * @param boolean $add         Action to take (add or remove)
     * @param integer $gallery_id  Gallery id to update images for
     */
    function _updateImageCount($images, $add = true, $gallery_id = null)
    {
        // We do the query directly here to avoid having to instantiate a
        // gallery object just to increment/decrement one value in the table.
        $sql = 'UPDATE ' . $this->_shareOb->_table
            . ' SET attribute_images = attribute_images '
            . ($add ? ' + ' : ' - ') . $images . ' WHERE share_id = '
            . ($gallery_id ? $gallery_id : $this->id);

        // Make sure to update the local value as well, so it doesn't get
        // overwritten by any other updates from ->set() calls.
        if (is_null($gallery_id) || $gallery_id === $this->id) {
            if ($add) {
                $this->data['attribute_images'] += $images;
            } else {
                $this->data['attribute_images'] -= $images;
            }
        }

        /* Need to expire the cache for the gallery that was changed */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $id = (is_null($gallery_id) ? $this->id : $gallery_id);
            $GLOBALS['cache']->expire('Ansel_Gallery' . $id);
        }

        return $this->_shareOb->_write_db->exec($sql);

    }

    /**
     * Add an image to this gallery.
     *
     * @param array $image_data  The image to add. Required keys include
     *                           'image_caption', and 'data'. Optional keys
     *                           include 'image_filename' and 'image_type'
     *
     * @param boolean $default   Make this image the new default tile image.
     *
     * @return integer  The id of the new image.
     */
    function addImage($image_data, $default = false)
    {
        global $conf;

        /* Normal is the only view mode that can accurately update gallery counts */
        $vMode = $this->get('view_mode');
        if ($vMode != 'Normal') {
            $this->_setModeHelper('Normal');
        }

        $resetStack = false;
        if (!isset($image_data['image_filename'])) {
            $image_data['image_filename'] = 'Untitled';
        }
        $image_data['gallery_id'] = $this->id;
        $image_data['image_sort'] = $this->countImages();

        /* Create the image object */
        $image = new Ansel_Image($image_data);
        $result = $image->save();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (empty($image_data['image_id'])) {
            $this->_updateImageCount(1);
            if ($this->countImages() < 5) {
                $resetStack = true;
            }
        }

        /* Should this be the default image? */
        if (!$default && $this->data['attribute_default_type'] == 'auto') {
            $this->data['attribute_default'] = $image->id;
            $resetStack = true;
        } elseif ($default) {
            $this->data['attribute_default'] = $image->id;
            $this->data['default_type'] = 'manual';
        }

        /* Reset the gallery default image stacks if needed. */
        if ($resetStack) {
            $this->clearStacks();
        }

        /* Update the modified flag and save gallery changes */
        $this->data['attribute_last_modified'] = time();

        /* Save all changes to the gallery */
        $this->save();

        /* Return to the proper view mode */
        if ($vMode != 'Normal') {
            $this->_setModeHelper($vMode);
        }

        /* Return the ID of the new image. */
        return $image->id;
    }

    /**
     * Clear all of this gallery's default image stacks from the VFS and the
     * gallery's data store.
     *
     */
    function clearStacks()
    {
        $ids = @unserialize($this->data['attribute_default_prettythumb']);
        if (is_array($ids)) {
            foreach ($ids as $imageId) {
                $this->removeImage($imageId, true);
            }
        }

        // Using the set function here so we can efficently update the db
        $this->set('default_prettythumb', '', true);
    }

    /**
     * Removes all generated and cached 'prettythumb' thumbnails for this
     * gallery
     *
     */
    function clearThumbs()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('prettythumb');
        }
    }

    /**
     * Removes all generated and cached views for this gallery
     *
     */
    function clearViews()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('all');
        }
    }

    /**
     * Move images from this gallery to a new gallery.
     *
     * @param array $images          An array of image ids.
     * @param Ansel_Gallery $gallery The gallery to move the images to.
     *
     * @return integer | PEAR_Error The number of images moved, or an error message.
     */
    function moveImagesTo($images, $gallery)
    {
        return $this->_modeHelper->moveImagesTo($images, $gallery);
    }

    /**
     * Copy image and related data to specified gallery.
     *
     * @param array $images           An array of image ids.
     * @param Ansel_Gallery $gallery  The gallery to copy images to.
     *
     * @return integer | PEAR_Error The number of images copied or error message
     */
    function copyImagesTo($images, $gallery)
    {
        if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return PEAR::raiseError(
                sprintf(_("Access denied copying photos to \"%s\"."),
                          $gallery->get('name')));
        }

        $db = $this->_shareOb->_write_db;
        $imgCnt = 0;
        foreach ($images as $imageId) {
            $img = &$this->getImage($imageId);
            // Note that we don't pass the tags when adding the image..see below
            $newId = $gallery->addImage(array(
                               'image_caption' => $img->caption,
                               'data' => $img->raw(),
                               'image_filename' => $img->filename,
                               'image_type' => $img->getType(),
                               'image_uploaded_date' => $img->uploaded));
            if (is_a($newId, 'PEAR_Error')) {
                return $newId;
            }
            /* Copy any tags */
            // Since we know that the tags already exist, no need to
            // go through Ansel_Tags::writeTags() - this saves us a SELECT query
            // for each tag - just write the data into the DB ourselves.
            $tags = $img->getTags();
            $query = $this->_shareOb->_write_db->prepare('INSERT INTO ansel_images_tags (image_id, tag_id) VALUES(' . $newId . ',?);');
            if (is_a($query, 'PEAR_Error')) {
                return $query;
            }
            foreach ($tags as $tag_id => $tag_name) {
                $result = $query->execute($tag_id);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
            $query->free();

            /* exif data */
            // First check to see if the exif data was present in the raw data.
            $count = $db->queryOne('SELECT COUNT(image_id) FROM ansel_image_attributes WHERE image_id = ' . (int) $newId . ';');
            if ($count == 0) {
                $exif = $db->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int) $imageId . ';',null, MDB2_FETCHMODE_ASSOC);
                if (is_array($exif) && count($exif) > 0) {
                    $insert = $db->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
                    if (is_a($insert, 'PEAR_Error')) {
                        return $insert;
                    }
                    foreach ($exif as $attr){
                        $result = $insert->execute(array($newId, $attr['attr_name'], $attr['attr_value']));
                        if (is_a($result, 'PEAR_Error')) {
                            return $result;
                        }
                    }
                    $insert->free();
                }
            }
            ++$imgCnt;
        }

        return $imgCnt;
    }

    /**
     * Set the order of an image in this gallery.
     *
     * @param integer $imageId The image to sort.
     * @param integer $pos     The sort position of the image.
     */
    function setImageOrder($imageId, $pos)
    {
        return $this->_shareOb->_write_db->exec('UPDATE ansel_images SET image_sort = ' . (int)$pos . ' WHERE image_id = ' . (int)$imageId);
    }

    /**
     * Remove the given image from this gallery.
     *
     * @param mixed   $image   Image to delete. Can be an Ansel_Image
     *                         or an image ID.
     *
     * @return boolean  True on success, false on failure.
     */
    function removeImage($image, $isStack = false)
    {
        return $this->_modeHelper->removeImage($image, $isStack);
    }

    /**
     * Returns this share's owner's Identity object.
     *
     * @return Identity object for the owner of this gallery.
     */
    function getOwner()
    {
        require_once 'Horde/Identity.php';
        $identity = Identity::singleton('none', $this->data['share_owner']);
        return $identity;
    }

    /**
     * Output the HTML for this gallery's tile.
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object
     * @param string $style          A named gallery style to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     */
    function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

        if (!empty($view_url)) {
            $view_url = str_replace('%g', $this->id, $view_url);
        }

        return Ansel_Tile_Gallery::getTile($this, $style, $mini, $params);
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm    The permissions to limit to.
     * @param integer $from    The child to start at.
     * @param integer $to      The child to end with.
     * @param boolean $noauto  Prevent auto
     *
     * @return A mixed array of Ansel_Gallery and Ansel_Image objects that are
     *         children of this gallery.
     */
    function getGalleryChildren($perm = PERMS_SHOW, $from = 0, $to = 0, $noauto = true)
    {
        return $this->_modeHelper->getGalleryChildren($perm, $from, $to, $noauto);
    }


    /**
     * Return the count of this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    function countGalleryChildren($perm = PERMS_SHOW, $galleries_only = false, $noauto = true)
    {
        return $this->_modeHelper->countGalleryChildren($perm, $galleries_only, $noauto);
    }

    /**
     * Lists a slice of the image ids in this gallery.
     *
     * @param integer $from  The image to start listing.
     * @param integer $count The numer of images to list.
     *
     * @return mixed  An array of image_ids | PEAR_Error
     */
    function listImages($from = 0, $count = 0)
    {
        return $this->_modeHelper->listImages($from, $count);
    }

    /**
     * Gets a slice of the images in this gallery.
     *
     * @param integer $from  The image to start fetching.
     * @param integer $count The numer of images to return.
     *
     * @param mixed An array of Ansel_Image objects | PEAR_Error
     */
    function getImages($from = 0, $count = 0)
    {
        return $this->_modeHelper->getImages($from, $count);
    }

    /**
     * Return the most recently added images in this gallery.
     *
     * @param integer $limit  The maximum number of images to return.
     *
     * @return mixed  An array of Ansel_Image objects | PEAR_Error
     */
    function getRecentImages($limit = 10)
    {
        return $GLOBALS['ansel_storage']->getRecentImages(array($this->id),
                                                          $limit);
    }

    /**
     * Returns the image in this gallery corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given id.
     */
    function &getImage($id)
    {
        return $GLOBALS['ansel_storage']->getImage($id);
    }

    /**
     * Checks if the gallery has any subgallery
     */
    function hasSubGalleries()
    {
        return $this->_modeHelper->hasSubGalleries();
    }

    /**
     * Returns the number of images in this gallery and, optionally, all
     * sub-galleries.
     *
     * @param boolean $subgalleries  Determines whether subgalleries should
     *                               be counted or not.
     *
     * @return integer number of images in this gallery
     */
    function countImages($subgalleries = false)
    {
        return $this->_modeHelper->countImages($subgalleries);
    }

    /**
     * Returns the default image for this gallery.
     *
     * @param string $style  Force the use of this style, if it's available
     *                       otherwise use whatever style is choosen for this
     *                       gallery. If prettythumbs are not available then
     *                       we always use ansel_default style.
     *
     * @return mixed  The image_id of the default image or false.
     */
    function getDefaultImage($style = null)
    {
       // Check for explicitly requested style
        if (!is_null($style)) {
            $gal_style = Ansel::getStyleDefinition($style);
        } else {
            // Use gallery's default.
            $gal_style = $this->getStyle();
            if (!isset($GLOBALS['ansel_styles'][$gal_style['name']])) {
                $gal_style = $GLOBALS['ansel_styles']['ansel_default'];
            }
        }
        Horde::logMessage(sprintf("using gallery style: %s in Ansel::getDefaultImage()", $gal_style['name']), __FILE__, __LINE__, PEAR_LOG_DEBUG);
        if (!empty($gal_style['default_galleryimage_type']) &&
            $gal_style['default_galleryimage_type'] != 'plain') {

            $thumbstyle = $gal_style['default_galleryimage_type'];
            $styleHash = $this->_getViewHash($thumbstyle, $style);

            // First check for the existence of a default image in the style
            // we are looking for.
            if (!empty($this->data['attribute_default_prettythumb'])) {
                $thumbs = @unserialize($this->data['attribute_default_prettythumb']);
            }
            if (!isset($thumbs) || !is_array($thumbs)) {
                $thumbs = array();
            }

            if (!empty($thumbs[$styleHash])) {
                return $thumbs[$styleHash];
            }

            // Don't already have one, must generate it.
            $params = array('gallery' => $this, 'style' => $gal_style);
            $iview = Ansel_ImageView::factory(
                $gal_style['default_galleryimage_type'], $params);

            if (!is_a($iview, 'PEAR_Error')) {
                $img = $iview->create();
                if (!is_a($img, 'PEAR_Error')) {
                     // Note the gallery_id is negative for generated stacks
                     $iparams = array('image_filename' => $this->get('name'),
                                      'image_caption' => $this->get('name'),
                                      'data' => $img->raw(),
                                      'image_sort' => 0,
                                      'gallery_id' => -$this->id);
                     $newImg = new Ansel_Image($iparams);
                     $newImg->save();
                     $prettyData = serialize(
                         array_merge($thumbs,
                                     array($styleHash => $newImg->id)));

                     $this->set('default_prettythumb', $prettyData, true);
                     return $newImg->id;
                } else {
                    Horde::logMessage($img, __FILE__, __LINE__, PEAR_LOG_ERR);
                }
            } else {
                // Might not support the requested style...try ansel_default
                // but protect against infinite recursion.
                Horde::logMessage($iview, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                if ($style != 'ansel_default') {
                    return $this->getDefaultImage('ansel_default');
                }
                Horde::logMessage($iview, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        } else {
            // We are just using an image thumbnail for the gallery default.
            if ($this->countImages()) {
                if (!empty($this->data['attribute_default']) &&
                    $this->data['attribute_default'] > 0) {

                    return $this->data['attribute_default'];
                }
                $keys = $this->listImages();
                if (is_a($keys, 'PEAR_Error')) {
                    return $keys;
                }
                $this->data['attribute_default'] = $keys[count($keys) - 1];
                $this->data['attribute_default_type'] = 'auto';
                $this->save();
                return $keys[count($keys) - 1];
            }

            if ($this->hasSubGalleries()) {
                // Fall through to a default image of a sub gallery.
                $galleries = $GLOBALS['ansel_storage']->listGalleries(
                    PERMS_SHOW, null, $this, false);
                if ($galleries && !is_a($galleries, 'PEAR_Error')) {
                    foreach ($galleries as $galleryId => $gallery) {
                        if ($default_img = $gallery->getDefaultImage($style)) {
                            return $default_img;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns this gallery's tags.
     */
    function getTags() {
        if ($this->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            return Ansel_Tags::readTags($this->id, 'gallery');
        } else {
            return PEAR::raiseError(_("Access denied viewing this gallery."));
        }
    }

    /**
     * Set/replace this gallery's tags.
     *
     * @param array $tags  AN array of tag names to associate with this image.
     */
    function setTags($tags)
    {
        if ($this->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return Ansel_Tags::writeTags($this->id, $tags, 'gallery');
        } else {
            return PEAR::raiseError(_("Access denied adding tags to this gallery."));
        }
    }

    /**
     * Return the style definition for this gallery. Returns the first available
     * style in this order: Explicitly configured style if available, if
     * configured style is not available, use ansel_default.  If nothing has
     * been configured, the user's selected default is attempted.
     *
     * @return array  The style definition array.
     */
    function getStyle()
    {
        if (empty($this->data['attribute_style'])) {
            $style = $GLOBALS['prefs']->getValue('default_gallerystyle');
        } else {
            $style = $this->data['attribute_style'];
        }
        return Ansel::getStyleDefinition($style);

    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view   The view (thumb, prettythumb etc...)
     * @param string $style  The named style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    function _getViewHash($view, $style = null)
    {
        if (is_null($style)) {
            $style = $this->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }
        if ($view != 'screen' && $view != 'thumb' && $view != 'mini' &&
            $view != 'full') {

            $view = md5($style['thumbstyle'] . '.' . $style['background']);
        }
        return $view;
    }
    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A PERMS_* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid == $this->data['share_owner'] ||
            Horde_Auth::isAdmin('ansel:admin')) {

            return true;
        }


        return $GLOBALS['perms']->hasPermission($this->getPermission(),
                                                $userid, $permission, $creator);
    }

    /**
     * Check user age limtation
     *
     * @return boolean
     */
    function isOldEnough()
    {
        if ($this->data['share_owner'] == Horde_Auth::getAuth() ||
            empty($GLOBALS['conf']['ages']['limits']) ||
            empty($this->data['attribute_age'])) {

            return true;
        }

        // Do we have the user age already cheked?
        if (!isset($_SESSION['ansel']['user_age'])) {
            $_SESSION['ansel']['user_age'] = 0;
        } elseif ($_SESSION['ansel']['user_age'] >= $this->data['attribute_age']) {
            return true;
        }

        // Can we hook user's age?
        if ($GLOBALS['conf']['ages']['hook'] && Horde_Auth::isAuthenticated()) {
            $result = Horde::callHook('_ansel_hook_user_age');
            if (is_int($result)) {
                $_SESSION['ansel']['user_age'] = $result;
            }
        }

        return ($_SESSION['ansel']['user_age'] >= $this->data['attribute_age']);
    }

    /**
     * Determine if we need to unlock a password protected gallery
     *
     * @return boolean
     */
    function hasPasswd()
    {
        if (Horde_Auth::getAuth() == $this->get('owner') || Horde_Auth::isAdmin('ansel:admin')) {
            return false;
        }

        $passwd = $this->get('passwd');
        if (empty($passwd) ||
            (!empty($_SESSION['ansel']['passwd'][$this->id])
                && $_SESSION['ansel']['passwd'][$this->id] = md5($this->get('passwd')))) {
            return false;
        }

        return true;
    }

    /**
     * Sets this gallery's parent gallery.
     *
     * @TODO: Check how this interacts with date galleries - shouldn't be able
     *        to remove a subgallery from a date gallery anyway, but just incase
     * @param mixed $parent    An Ansel_Gallery or a gallery_id.
     *
     * @return mixed  Ture || PEAR_Error
     */
    function setParent($parent)
    {
        /* Make sure we have a gallery object */
        if (!is_null($parent) && !is_a($parent, 'Ansel_Gallery')) {
            $parent = $GLOBALS['ansel_storage']->getGallery($parent);
            if (is_a($parent, 'PEAR_Error')) {
                return $parent;
            }
        }

        /* Check this now since we don't know if we are updating the DB or not */
        $old = $this->getParent();
        $reset_has_subgalleries = false;
        if (!is_null($old)) {
            $cnt = $old->countGalleryChildren(PERMS_READ, true);
            if ($cnt == 1) {
                /* Count is 1, and we are about to delete it */
                $reset_has_subgalleries = true;
            }
        }

        /* Call the parent class method */
        $result = parent::setParent($parent);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Tell the parent the good news */
        if (!is_null($parent) && !$parent->get('has_subgalleries')) {
            return $parent->set('has_subgalleries', '1', true);
        }
        Horde::logMessage('Ansel_Gallery parent successfully set', __FILE__,
                          __LINE__, PEAR_LOG_DEBUG);

       /* Gallery parent changed, safe to change the parent's attributes */
       if ($reset_has_subgalleries) {
           $old->set('has_subgalleries', 0, true);
       }

        return true;
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     * @param boolean $update    Commit only this change to storage.
     *
     * @return mixed  True if setting the attribute did succeed, a PEAR_Error
     *                otherwise.
     */
    function set($attribute, $value, $update = false)
    {
        /* Translate the keys */
        if ($attribute == 'owner') {
            $driver_key = 'share_owner';
        } else {
            $driver_key = 'attribute_' . $attribute;
        }

        if ($driver_key == 'attribute_view_mode' &&
            !empty($this->data[$driver_key]) &&
            $value != $this->data[$driver_key]) {

            $mode = isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal';
            $this->_setModeHelper($mode);
        }

        $this->data[$driver_key] = $value;

        /* Update the backend, but only this current change */
        if ($update) {
            $db = $this->_shareOb->_write_db;
            // Manually convert the charset since we're not going through save()
            $data = $this->_shareOb->_toDriverCharset(array($driver_key => $value));
            $query = $db->prepare('UPDATE ' . $this->_shareOb->_table . ' SET ' . $driver_key . ' = ? WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['cache']->expire('Ansel_Gallery' . $this->id);
            }
            $result = $query->execute(array($data[$driver_key], $this->id));
            $query->free();

            return $result;
        }

        return true;
    }

    function setDate($date)
    {
        $this->_modeHelper->setDate($date);
    }

    function getDate()
    {
        return $this->_modeHelper->getDate();
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    function getGalleryCrumbData()
    {
        return $this->_modeHelper->getGalleryCrumbData();
    }

}

/**
 * Class to describe a single Ansel image.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Image {

    /**
     * @var integer  The gallery id of this image's parent gallery
     */
    var $gallery;

    /**
     * @var Horde_Image  Horde_Image object for this image.
     */
    var $_image;

    var $id = null;
    var $filename = 'Untitled';
    var $caption = '';
    var $type = 'image/jpeg';

    /**
     * timestamp of uploaded date
     *
     * @var integer
     */
    var $uploaded;

    var $sort;
    var $commentCount;
    var $facesCount;
    var $lat;
    var $lng;
    var $location;
    var $geotag_timestamp;

    var $_dirty;


    /**
     * Timestamp of original date.
     *
     * @var integer
     */
    var $originalDate;

    /**
     * Holds an array of tags for this image
     * @var array
     */
    var $_tags = array();

    var $_loaded = array();
    var $_data = array();

    /**
     * Cache the raw EXIF data locally
     *
     * @var array
     */
    var $_exif = array();

    /**
     * TODO: refactor Ansel_Image to use a ::get() method like Ansel_Gallery
     * instead of direct instance variable access and all the nonsense below.
     *
     * @param unknown_type $image
     * @return Ansel_Image
     */
    function Ansel_Image($image = array())
    {
        if ($image) {
            $this->filename = $image['image_filename'];
            $this->caption = $image['image_caption'];
            $this->sort = $image['image_sort'];
            $this->gallery = $image['gallery_id'];

            // New image?
            if (!empty($image['image_id'])) {
                $this->id = $image['image_id'];
            }

            if (!empty($image['data'])) {
                $this->_data['full'] = $image['data'];
            }

            if (!empty($image['image_uploaded_date'])) {
                $this->uploaded = $image['image_uploaded_date'];
            } else {
                $this->uploaded = time();
            }

            if (!empty($image['image_type'])) {
                $this->type = $image['image_type'];
            }

            if (!empty($image['tags'])) {
                $this->_tags = $image['tags'];
            }

            if (!empty($image['image_faces'])) {
               $this->facesCount = $image['image_faces'];
            }

            $this->location = !empty($image['image_location']) ? $image['image_location'] : '';

            // The following may have to be rewritten by EXIF.
            // EXIF requires both an image id and a stream, so we can't
            // get EXIF data before we save the image to the VFS.
            if (!empty($image['image_original_date'])) {
                $this->originalDate = $image['image_original_date'];
            } else {
                $this->originalDate = $this->uploaded;
            }
            $this->lat = !empty($image['image_latitude']) ? $image['image_latitude'] : '';
            $this->lng = !empty($image['image_longitude']) ? $image['image_longitude'] : '';
            $this->geotag_timestamp = !empty($image['image_geotag_date']) ? $image['image_geotag_date'] : '0';
        }

        $this->_image = Ansel::getImageObject();
        $this->_image->reset();
    }

    /**
     * Return the vfs path for this image.
     *
     * @param string $view   The view we want.
     * @param string $style  A named gallery style.
     *
     * @return string  The vfs path for this image.
     */
    function getVFSPath($view = 'full', $style = null)
    {
        $view = $this->_getViewHash($view, $style);
        return '.horde/ansel/'
               . substr(str_pad($this->id, 2, 0, STR_PAD_LEFT), -2)
               . '/' . $view;
    }

    /**
     * Returns the file name of this image as used in the VFS backend.
     *
     * @return string  This image's VFS file name.
     */
    function getVFSName($view)
    {
        $vfsname = $this->id;

        if ($view == 'full' && $this->type) {
            $type = strpos($this->type, '/') === false ? 'image/' . $this->type : $this->type;
            if ($ext = Horde_Mime_Magic::mimeToExt($type)) {
                $vfsname .= '.' . $ext;
            }
        } elseif (($GLOBALS['conf']['image']['type'] == 'jpeg') || $view == 'screen') {
            $vfsname .= '.jpg';
        } else {
            $vfsname .= '.png';
        }

        return $vfsname;
    }

    /**
     * Loads the given view into memory.
     *
     * @param string $view   Which view to load.
     * @param string $style  The named gallery style.
     *
     * @return mixed  True || PEAR_Error
     */
    function load($view = 'full', $style = null)
    {
        // If this is a new image that hasn't been saved yet, we will
        // already have the full data loaded. If we auto-rotate the image
        // then there is no need to save it just to load it again.
        if ($view == 'full' && !empty($this->_data['full'])) {
            $this->_image->loadString('original', $this->_data['full']);
            $this->_loaded['full'] = true;
            return true;
        }

        $viewHash = $this->_getViewHash($view, $style);
        /* If we've already loaded the data, just return now. */
        if (!empty($this->_loaded[$viewHash])) {
            return true;
        }

        $result = $this->createView($view, $style);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* If createView() had to resize the full image, we've already
         * loaded the data, so return now. */
        if (!empty($this->_loaded[$viewHash])) {
            return;
        }

        /* We've definitely successfully loaded the image now. */
        $this->_loaded[$viewHash] = true;

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);
        if (is_a($vfspath, 'PEAR_Error')) {
            return $vfspath;
        }

        /* Read in the requested view. */
        $data = $GLOBALS['ansel_vfs']->read($vfspath, $this->getVFSName($view));
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage($date, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $data;
        }

        $this->_data[$viewHash] = $data;
        $this->_image->loadString($vfspath . '/' . $this->id, $data);
        return true;
    }

    /**
     * Check if an image view exists and returns the vfs name complete with
     * the hash directory name prepended if appropriate.
     *
     * @param integer $id    Image id to check
     * @param string $view   Which view to check for
     * @param string $style  A named gallery style
     *
     * @return mixed  False if image does not exists | string vfs name
     *
     * @static
     */
    function viewExists($id, $view, $style)
    {
        /* We cannot check empty styles since we cannot get the hash */
        if (empty($style)) {
            return false;
        }

        /* Get the VFS path. */
        $view = Ansel_Gallery::_getViewHash($view, $style);

        /* Can't call the various vfs methods here, since this method needs
        to be called statically */
        $vfspath = '.horde/ansel/' . substr(str_pad($id, 2, 0, STR_PAD_LEFT), -2) . '/' . $view;

        /* Get VFS name */
        $vfsname = $id . '.';
        if ($GLOBALS['conf']['image']['type'] == 'jpeg' || $view == 'screen') {
            $vfsname .= 'jpg';
        } else {
            $vfsname .= 'png';
        }

        if ($GLOBALS['ansel_vfs']->exists($vfspath, $vfsname)) {
            return $view . '/' . $vfsname;
        } else {
            return false;
        }
    }

    /**
     * Creates and caches the given view.
     *
     * @param string $view  Which view to create.
     * @param string $style  A named gallery style
     */
    function createView($view, $style = null)
    {
        // HACK: Need to replace the image object with a JPG typed image if
        //       we are generating a screen image. Need to do the replacement
        //       and do it *here* for BC reasons with Horde_Image...and this
        //       needs to be done FIRST, since the view might already be cached
        //       in the VFS.
        if ($view == 'screen' && $GLOBALS['conf']['image']['type'] != 'jpeg') {
            $this->_image = Ansel::getImageObject(array('type' => 'jpeg'));
            $this->_image->reset();
        }

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);
        if ($GLOBALS['ansel_vfs']->exists($vfspath, $this->getVFSName($view))) {
            return true;
        }

        $data = $GLOBALS['ansel_vfs']->read($this->getVFSPath('full'),
                                            $this->getVFSName('full'));
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage($data, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $data;
        }
        $this->_image->loadString($this->getVFSPath('full') . '/' . $this->id, $data);
        $styleDef = Ansel::getStyleDefinition($style);
        if ($view == 'prettythumb') {
            $viewType = $styleDef['thumbstyle'];
        } else {
            $viewType = $view;
        }
        $iview = Ansel_ImageView::factory($viewType, array('image' => $this,
                                                           'style' => $style));

        if (is_a($iview, 'PEAR_Error')) {
            // It could be we don't support the requested effect, try
            // ansel_default before giving up.
            if ($view == 'prettythumb') {
                $iview = Ansel_ImageView::factory(
                    'thumb', array('image' => $this,
                                   'style' => 'ansel_default'));

                if (is_a($iview, 'PEAR_Error')) {
                    return $iview;
                }
            }
        }

        $res = $iview->create();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $view = $this->_getViewHash($view, $style);

        $this->_data[$view] = $this->_image->raw();
        $this->_image->loadString($vfspath . '/' . $this->id,
                                  $this->_data[$view]);
        $this->_loaded[$view] = true;
        $GLOBALS['ansel_vfs']->writeData($vfspath, $this->getVFSName($view),
                                         $this->_data[$view], true);

        // Autowatermark the screen view
        if ($view == 'screen' &&
            $GLOBALS['prefs']->getValue('watermark_auto') &&
            $GLOBALS['prefs']->getValue('watermark_text') != '') {

            $this->watermark('screen');
            $GLOBALS['ansel_vfs']->writeData($vfspath, $this->getVFSName($view),
                                             $this->_image->_data);
        }

        return true;
    }

    /**
     * Writes the current data to vfs, used when creating a new image
     */
    function _writeData()
    {
        $this->_dirty = false;
        return $GLOBALS['ansel_vfs']->writeData($this->getVFSPath('full'),
                                                $this->getVFSName('full'),
                                                $this->_data['full'], true);
    }

    /**
     * Change the image data. Deletes old cache and writes the new
     * data to the VFS. Used when updating an image
     *
     * @param string $data  The new data for this image.
     * @param string $view  If specified, the $data represents only this
     *                      particular view. Cache will not be deleted.
     */
    function updateData($data, $view = 'full')
    {
        if (is_a($data, 'PEAR_Error')) {
            return $data;
        }

        /* Delete old cached data if we are replacing the full image */
        if ($view == 'full') {
            $this->deleteCache();
        }

        return $GLOBALS['ansel_vfs']->writeData($this->getVFSPath($view),
                                                $this->getVFSName($view),
                                                $data, true);
    }

    /**
     * Update the geotag data
     */
    function geotag($lat, $lng, $location = '')
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->location = $location;
        $this->geotag_timestamp = time();
        $this->save();
    }

    /**
     * Save basic image details
     *
     * @TODO: Move all SQL queries to Ansel_Storage::?
     */
    function save()
    {
        /* If we have an id, then it's an existing image.*/
        if ($this->id) {
            $update = $GLOBALS['ansel_db']->prepare('UPDATE ansel_images SET image_filename = ?, image_type = ?, image_caption = ?, image_sort = ?, image_original_date = ?, image_latitude = ?, image_longitude = ?, image_location = ?, image_geotag_date = ? WHERE image_id = ?');
            if (is_a($update, 'PEAR_Error')) {
                Horde::logMessage($update, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $update;
            }
            $result = $update->execute(array(Horde_String::convertCharset($this->filename, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset']),
                                             $this->type,
                                             Horde_String::convertCharset($this->caption, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset']),
                                             $this->sort,
                                             $this->originalDate,
                                             $this->lat,
                                             $this->lng,
                                             $this->location,
                                             $this->geotag_timestamp,
                                             $this->id));
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($update, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $update->free();
            }
            return $result;
        }

        /* Saving a new Image */
        if (!$this->gallery || !strlen($this->filename) || !$this->type) {
            $error = PEAR::raiseError(_("Incomplete photo"));
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        /* Get the next image_id */
        $image_id = $GLOBALS['ansel_db']->nextId('ansel_images');
        if (is_a($image_id, 'PEAR_Error')) {
            return $image_id;
        }

        /* Prepare the SQL statement */
        $insert = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_images (image_id, gallery_id, image_filename, image_type, image_caption, image_uploaded_date, image_sort, image_original_date, image_latitude, image_longitude, image_location, image_geotag_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (is_a($insert, 'PEAR_Error')) {
            Horde::logMessage($insert, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $insert;
        }

        /* Perform the INSERT */
        $result = $insert->execute(array($image_id,
                                         $this->gallery,
                                         Horde_String::convertCharset($this->filename, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset']),
                                         $this->type,
                                         Horde_String::convertCharset($this->caption, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset']),
                                         $this->uploaded,
                                         $this->sort,
                                         $this->originalDate,
                                         $this->lat,
                                         $this->lng,
                                         $this->location,
                                         (empty($this->lat) ? 0 : $this->uploaded)));
        $insert->free();
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Keep the image_id */
        $this->id = $image_id;

        /* The EXIF functions require a stream, so we need to save before we read */
        $this->_writeData();

        /* Get the EXIF data if we are not a gallery key image. */
        if ($this->gallery > 0) {
            $needUpdate = $this->_getEXIF();
        }

        /* Create tags from exif data if desired */
        $fields = @unserialize($GLOBALS['prefs']->getValue('exif_tags'));
        if ($fields) {
            $this->_exifToTags($fields);
        }

        /* Save the tags */
        if (count($this->_tags)) {
            $result = $this->setTags($this->_tags);
            if (is_a($result, 'PEAR_Error')) {
                // Since we got this far, the image has been added, so
                // just log the tag failure.
                Horde::logMessage($result, __LINE__, __FILE__, PEAR_LOG_ERR);
            }
        }

        /* Save again if EXIF changed any values */
        if (!empty($needUpdate)) {
            $this->save();
        }

        return $this->id;
    }

   /**
    * Replace this image's image data.
    *
    */
    function replace($imageData)
    {
        /* Reset the data array and remove all cached images */
        $this->_data = array();
        $this->reset();

        /* Remove attributes */
        $result = $GLOBALS['ansel_db']->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$this->id);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERROR);
            return $result;
        }
        /* Load the new image data */
        $this->_getEXIF();
        $this->updateData($imageData);

        return true;
    }

    /**
     * Adds specified EXIF fields to this image's tags. Called during image
     * upload/creation.
     *
     * @param array $fields  An array of EXIF fields to import as a tag.
     *
     */
    function _exifToTags($fields = array())
    {
        $tags = array();
        foreach ($fields as $field) {
            if (!empty($this->_exif[$field])) {
                if (substr($field, 0, 8) == 'DateTime') {
                    $d = new Horde_Date(strtotime($this->_exif[$field]));
                    $tags[] = $d->format("Y-m-d");
                } else {
                    $tags[] = $this->_exif[$field];
                }
            }
        }

        $this->_tags = array_merge($this->_tags, $tags);
    }

    /**
     * Reads the EXIF data from the image and stores in _exif array() as well
     * also populates any local properties that come from the EXIF data.
     *
     * @return mixed  true if any local properties were modified, false otherwise, PEAR_Error on failure
     */
    function _getEXIF()
    {
        /* Clear the local copy */
        $this->_exif = array();

        /* Get the data */
        $imageFile = $GLOBALS['ansel_vfs']->readFile($this->getVFSPath('full'),
                                                     $this->getVFSName('full'));
        if (is_a($imageFile, 'PEAR_Error')) {
            return $imageFile;
        }
        $exif = Horde_Image_Exif::factory();
        $exif_fields = $exif->getData($imageFile);

        /* Flag to determine if we need to resave the image data */
        $needUpdate = false;

        /* Populate any local properties that come from EXIF
         * Save any geo data to a seperate table as well */
        if (!empty($exif_fields['GPSLatitude'])) {
            $this->lat = $exif_fields['GPSLatitude'];
            $this->lng = $exif_fields['GPSLongitude'];
            $this->geotag_timestamp = time();
            $needUpdate = true;
        }

        if (!empty($exif_fields['DateTimeOriginal'])) {
            $this->originalDate = $exif_fields['DateTimeOriginal'];
            $needUpdate = true;
        }

        /* Attempt to autorotate based on Orientation field */
        $this->_autoRotate();

        /* Save attributes. */
        $insert = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
        foreach ($exif_fields as $name => $value) {
            $result = $insert->execute(array($this->id, $name, Horde_String::convertCharset($value, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset'])));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            /* Cache it locally */
            $this->_exif[$name] = Horde_Image_Exif::getHumanReadable($name, $value);
        }
        $insert->free();


        return $needUpdate;
    }

    /**
     * Autorotate based on EXIF orientation field. Updates the data in memory
     * only.
     *
     */
    function _autoRotate()
    {
        if (isset($this->_exif['Orientation']) && $this->_exif['Orientation'] != 1) {
            switch ($this->_exif['Orientation']) {
            case 2:
                 $this->mirror();
                break;

            case 3:
                $this->rotate('full', 180);
                break;

            case 4:
                $this->mirror();
                $this->rotate('full', 180);
                break;

            case 5:
                $this->flip();
                $this->rotate('full', 90);
                break;

            case 6:
                $this->rotate('full', 90);
                break;

            case 7:
                $this->mirror();
                $this->rotate('full', 90);
                break;

            case 8:
                $this->rotate('full', 270);
                break;
            }

            if ($this->_dirty) {
                $this->_exif['Orientation'] = 1;
                $this->data['full'] = $this->raw();
                $this->_writeData();
            }
        }
    }

    /**
     * Reset the image, removing all loaded views.
     */
    function reset()
    {
        $this->_image->reset();
        $this->_loaded = array();
    }

    /**
     * Deletes the specified cache file.
     *
     * If none is specified, deletes all of the cache files.
     *
     * @param string $view  Which cache file to delete.
     */
    function deleteCache($view = 'all')
    {
        /* Delete cached screen image. */
        if ($view == 'all' || $view == 'screen') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('screen'),
                                              $this->getVFSName('screen'));
        }

        /* Delete cached thumbnail. */
        if ($view == 'all' || $view == 'thumb') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('thumb'),
                                              $this->getVFSName('thumb'));
        }

        /* Delete cached mini image. */
        if ($view == 'all' || $view == 'mini') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('mini'),
                                              $this->getVFSName('mini'));
        }

        if ($view == 'all' || $view == 'prettythumb') {

            /* No need to try to delete a hash we already removed */
            $deleted = array();

            /* Need to generate hashes for each possible style */
            $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');
            foreach ($styles as $style) {
                $hash =  md5($style['thumbstyle'] . '.' . $style['background']);
                if (empty($deleted[$hash])) {
                    $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath($hash),
                                                      $this->getVFSName($hash));
                    $deleted[$hash] = true;
                }
            }
        }
    }

    /**
     * Returns the raw data for the given view.
     *
     * @param string $view  Which view to return.
     */
    function raw($view = 'full')
    {
        if ($this->_dirty) {
          return $this->_image->raw();
        } else {
            $this->load($view);
            return $this->_data[$view];
        }
    }

    /**
     * Sends the correct HTTP headers to the browser to download this image.
     *
     * @param string $view  The view to download.
     */
    function downloadHeaders($view = 'full')
    {
        global $browser, $conf;

        $filename = $this->filename;
        if ($view != 'full') {
            if ($ext = Horde_Mime_Magic::mimeToExt('image/' . $conf['image']['type'])) {
                $filename .= '.' . $ext;
            }
        }

        $browser->downloadHeaders($filename);
    }

    /**
     * Display the requested view.
     *
     * @param string $view   Which view to display.
     * @param string $style  Force use of this gallery style.
     */
    function display($view = 'full', $style = null)
    {
        if ($view == 'full' && !$this->_dirty) {

            // Check full photo permissions
            $gallery = $GLOBALS['ansel_storage']->getGallery($this->gallery);
            if (is_a($gallery, 'PEAR_Error')) {
                return $gallery;
            }
            if (!$gallery->canDownload()) {
                return PEAR::RaiseError(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')));
            }

            $data = $GLOBALS['ansel_vfs']->read($this->getVFSPath('full'),
                                                $this->getVFSName('full'));

            if (is_a($data, 'PEAR_Error')) {
                return $data;
            }
            echo $data;
            return;
        }

        if (is_a($result = $this->load($view, $style), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        $this->_image->display();
    }

    /**
     * Wraps the given view into a file.
     *
     * @param string $view  Which view to wrap up.
     */
    function toFile($view = 'full')
    {
        if (is_a(($result = $this->load($view)), 'PEAR_Error')) {
            return $result;
        }
        return $this->_image->toFile($this->_dirty ? false : $this->_data[$view]);
    }

    /**
     * Returns the dimensions of the given view.
     *
     * @param string $view  The view (size) to check dimensions for.
     */
    function getDimensions($view = 'full')
    {
        if (is_a(($result = $this->load($view)), 'PEAR_Error')) {
            return $result;
        }
        return $this->_image->getDimensions();
    }

    /**
     * Rotates the image.
     *
     * @param string $view The view (size) to work with.
     * @param integer $angle  What angle to rotate the image by.
     */
    function rotate($view = 'full', $angle)
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->rotate($angle);
    }

    function crop($x1, $y1, $x2, $y2)
    {
        $this->_dirty = true;
        $this->_image->crop($x1, $y1, $x2, $y2);
    }

    /**
     * Converts the image to grayscale.
     *
     * @param string $view The view (size) to work with.
     */
    function grayscale($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->grayscale();
    }

    /**
     * Watermarks the image.
     *
     * @param string $view The view (size) to work with.
     * @param string $watermark  String to use as the watermark.
     */
    function watermark($view = 'full', $watermark = null, $halign = null,
                       $valign = null, $font = null)
    {
        if (empty($watermark)) {
            $watermark = $GLOBALS['prefs']->getValue('watermark_text');
        }

        if (empty($halign)) {
            $halign = $GLOBALS['prefs']->getValue('watermark_horizontal');
        }

        if (empty($valign)) {
            $valign = $GLOBALS['prefs']->getValue('watermark_vertical');
        }

        if (empty($font)) {
            $font = $GLOBALS['prefs']->getValue('watermark_font');
        }

        if (empty($watermark)) {
            require_once 'Horde/Identity.php';
            $identity = Identity::singleton();
            $name = $identity->getValue('fullname');
            if (empty($name)) {
                $name = Horde_Auth::getAuth();
            }
            $watermark = sprintf(_("(c) %s %s"), date('Y'), $name);
        }

        $this->load($view);
        $this->_dirty = true;
        $params = array('text' => $watermark,
                        'halign' => $halign,
                        'valign' => $valign,
                        'fontsize' => $font);
        if (!empty($GLOBALS['conf']['image']['font'])) {
            $params['font'] = $GLOBALS['conf']['image']['font'];
        }
        $this->_image->addEffect('TextWatermark', $params);
    }

    /**
     * Flips the image.
     *
     * @param string $view The view (size) to work with.
     */
    function flip($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->flip();
    }

    /**
     * Mirrors the image.
     *
     * @param string $view The view (size) to work with.
     */
    function mirror($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->mirror();
    }

    /**
     * Returns this image's tags.
     *
     * @return mixed  An array of tags | PEAR_Error
     * @see Ansel_Tags::readTags()
     */
    function getTags()
    {
        global $ansel_storage;

        if (count($this->_tags)) {
            return $this->_tags;
        }
        $gallery = $ansel_storage->getGallery($this->gallery);
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        }
        if ($gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            $res = Ansel_Tags::readTags($this->id);
            if (!is_a($res, 'PEAR_Error')) {
                $this->_tags = $res;
                return $this->_tags;
            } else {
                return $res;
            }
        } else {
            return PEAR::raiseError(_("Access denied viewing this photo."));
        }
    }

    /**
     * Set/replace this image's tags.
     *
     * @param array $tags  An array of tag names to associate with this image.
     */
    function setTags($tags)
    {
        global $ansel_storage;

        $gallery = $ansel_storage->getGallery(abs($this->gallery));
        if ($gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            // Clear the local cache.
            $this->_tags = array();
            return Ansel_Tags::writeTags($this->id, $tags);
        } else {
            return PEAR::raiseError(_("Access denied adding tags to this photo."));
        }
    }

    /**
     * Get the Ansel_View_Image_Thumb object
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object.
     * @param string $style          A named gallery style to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     *
     */
    function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

        return Ansel_Tile_Image::getTile($this, $style, $mini, $params);
    }

    /**
     * Get the image type for the requested view.
     */
    function getType($view = 'full')
    {
        if ($view == 'full') {
           return $this->type;
        } elseif ($view == 'screen') {
            return 'image/jpg';
        } else {
            return 'image/' . $GLOBALS['conf']['image']['type'];
        }
    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view   The view (thumb, prettythumb etc...)
     * @param string $style  The named style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    function _getViewHash($view, $style = null)
    {
        global $ansel_storage;

        // These views do not care about style...just return the $view value.
        if ($view == 'screen' || $view == 'thumb' || $view == 'mini' ||
            $view == 'full') {

            return $view;
        }
        if (is_null($style)) {
            $gallery = $ansel_storage->getGallery(abs($this->gallery));
            if (is_a($gallery, 'PEAR_Error')) {
                return $gallery;
            }
            $style = $gallery->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

       $view = md5($style['thumbstyle'] . '.' . $style['background']);
       return $view;
    }

    /**
     * Get the image attributes from the backend.
     *
     * @param Ansel_Image $image  The image to retrieve attributes for.
     *                            attributes for.
     * @param boolean $format     Format the EXIF data. If false, the raw data
     *                            is returned.
     *
     * @return array  The EXIF data.
     * @static
     */
    function getAttributes($format = false)
    {
        $attributes = $GLOBALS['ansel_storage']->getImageAttributes($this->id);
        $fields = Horde_Image_Exif::getFields();
        $output = array();

        foreach ($fields as $field => $data) {
            if (!isset($attributes[$field])) {
                continue;
            }
            $value = Horde_Image_Exif::getHumanReadable($field, Horde_String::convertCharset($attributes[$field], $GLOBALS['conf']['sql']['charset']));
            if (!$format) {
                $output[$field] = $value;
            } else {
                $description = isset($data['description']) ? $data['description'] : $field;
                $output[] = '<td><strong>' . $description . '</strong></td><td>' . htmlspecialchars($value, ENT_COMPAT, Horde_Nls::getCharset()) . '</td>';
            }
        }

        return $output;
    }

}

/**
 * Class for interfacing with back end data storage.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Storage {

    var $_scope = 'ansel';
    var $_db = null;
    var $galleries = array();

    /**
     * The Horde_Shares object to use for this scope.
     *
     * @var Horde_Share
     */
    var $shares = null;

    /* Local cache of retrieved images */
    var $images = array();

    function Ansel_Storage($scope = null)
    {
        /* Check for a scope other than the default Ansel scope.*/
        if (!is_null($scope)) {
            $this->_scope = $scope;
        }

        /* This is the only supported share backend for Ansel */
        $this->shares = Horde_Share::singleton($this->_scope,
                                               'sql_hierarchical');

        /* Ansel_Gallery is just a subclass of Horde_Share_Object */
        $this->shares->_shareObject = 'Ansel_Gallery';

        /* Database handle */
        $this->_db = $GLOBALS['ansel_db'];
    }

   /**
    * Create and initialise a new gallery object.
    *
    * @param array $attributes     The gallery attributes
    * @param object Perms $perm    The permissions for the gallery if the
    *                              defaults are not desirable.
    * @param mixed  $parent       The gallery id of the parent (if any)
    *
    * @return Ansel_Gallery  A new gallery object or PEAR_Error.
    */
    function createGallery($attributes = array(), $perm = null, $parent = null)
    {
        /* Required values. */
        if (empty($attributes['owner'])) {
            $attributes['owner'] = Horde_Auth::getAuth();
        }
        if (empty($attributes['name'])) {
            $attributes['name'] = _("Unnamed");
        }
        if (empty($attributes['desc'])) {
            $attributes['desc'] = '';
        }

        /* Default values */
        $attributes['default_type'] = isset($attributes['default_type']) ? $attributes['default_type'] : 'auto';
        $attributes['default'] = isset($attributes['default']) ? (int)$attributes['default'] : 0;
        $attributes['default_prettythumb'] = isset($attributes['default_prettythumb']) ? $attributes['default_prettythumb'] : '';
        $attributes['style'] = isset($attributes['style']) ? $attributes['style'] : $GLOBALS['prefs']->getValue('default_gallerystyle');
        $attributes['category'] = isset($attributes['category']) ? $attributes['category'] : $GLOBALS['prefs']->getValue('default_category');
        $attributes['date_created'] = time();
        $attributes['last_modified'] = $attributes['date_created'];
        $attributes['images'] = isset($attributes['images']) ? (int)$attributes['images'] : 0;
        $attributes['slug'] = isset($attributes['slug']) ? $attributes['slug'] : '';
        $attributes['age'] = isset($attributes['age']) ? (int)$attributes['age'] : 0;
        $attributes['download'] = isset($attributes['download']) ? $attributes['download'] : $GLOBALS['prefs']->getValue('default_download');
        $attributes['view_mode'] = isset($attributes['view_mode']) ? $attributes['view_mode'] : 'Normal';
        $attributes['passwd'] = isset($attributes['passwd']) ? $attributes['passwd'] : '';

        /* Don't pass tags to the share creation method */
        if (isset($attributes['tags'])) {
            $tags = $attributes['tags'];
            unset($attributes['tags']);
        } else {
            $tags = array();
        }

        /* Check for slug uniqueness */
        if (!empty($attributes['slug']) &&
            $this->slugExists($attributes['slug'])) {
            return PEAR::raiseError(sprintf(_("The slug \"%s\" already exists."),
                                            $attributes['slug']));
        }

        /* Create the gallery */
        $gallery = $this->shares->newShare('');
        if (is_a($gallery, 'PEAR_Error')) {
            Horde::logMessage($gallery, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $gallery;
        }
        Horde::logMessage('New Ansel_Gallery object instantiated', __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Set the gallery's parent if needed */
        if (!is_null($parent)) {
            $result = $gallery->setParent($parent);

            /* Clear the parent from the cache */
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['cache']->expire('Ansel_Gallery' . $parent);
            }
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }
        }

        /* Fill up the new gallery */
        // TODO: New private method to bulk load these (it's done this way
        // since the data is stored in the Share_Object class keyed by the
        // DB specific fields and set() translates them.
        foreach ($attributes as $key => $value) {
            $gallery->set($key, $value);
        }

        /* Save it to storage */
        $result = $this->shares->addShare($gallery);
        if (is_a($result, 'PEAR_Error')) {
            $error = sprintf(_("The gallery \"%s\" could not be created: %s"),
                             $attributes['name'], $result->getMessage());
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
            return PEAR::raiseError($error);
        }

        /* Convenience */
        $gallery->id = $gallery->getId();

        /* Add default permissions. */
        if (empty($perm)) {
            $perm = $gallery->getPermission();

            /* Default permissions for logged in users */
            switch ($GLOBALS['prefs']->getValue('default_permissions')) {
            case 'read':
                $perms = PERMS_SHOW | PERMS_READ;
                break;
            case 'edit':
                $perms = PERMS_SHOW | PERMS_READ | PERMS_EDIT;
                break;
            case 'none':
                $perms = 0;
                break;
            }
            $perm->addDefaultPermission($perms, false);

            /* Default guest permissions */
            switch ($GLOBALS['prefs']->getValue('guest_permissions')) {
            case 'read':
                $perms = PERMS_SHOW | PERMS_READ;
                break;
            case 'none':
            default:
                $perms = 0;
                break;
            }
            $perm->addGuestPermission($perms, false);

            /* Default user groups permissions */
            switch ($GLOBALS['prefs']->getValue('group_permissions')) {
            case 'read':
                $perms = PERMS_SHOW | PERMS_READ;
                break;
            case 'edit':
                $perms = PERMS_SHOW | PERMS_READ | PERMS_EDIT;
                break;
            case 'delete':
                $perms = PERMS_SHOW | PERMS_READ | PERMS_EDIT | PERMS_DELETE;
                break;
            case 'none':
            default:
                $perms = 0;
                break;
            }

            if ($perms) {
                $groups = Group::singleton();
                $group_list = $groups->getGroupMemberships(Horde_Auth::getAuth());
                if (!is_a($group_list, 'PEAR_Error') && count($group_list)) {
                    foreach ($group_list as $group_id => $group_name) {
                        $perm->addGroupPermission($group_id, $perms, false);
                    }
                }
            }
        }
        $gallery->setPermission($perm, true);

        /* Initial tags */
        if (count($tags)) {
            $gallery->setTags($tags);
        }

        return $gallery;
    }

    /**
     * Check that a slug exists.
     *
     * @param string $slug  The slug name
     *
     * @return integer  The share_id the slug represents, or 0 if not found.
     */
    function slugExists($slug)
    {
        // An empty slug should never match.
        if (!strlen($slug)) {
            return 0;
        }

        $stmt = $this->_db->prepare('SELECT share_id FROM '
            . $this->shares->_table . ' WHERE attribute_slug = ?');

        if (is_a($stmt, 'PEAR_Error')) {
            Horde::logMessage($stmt, __FILE__, __LINE__, PEAR_LOG_ERR);
            return 0;
        }

        $result = $stmt->execute($slug);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
        if (!$result->numRows()) {
            return 0;
        }

        $slug = $result->fetchRow();

        $result->free();
        $stmt->free();

        return $slug[0];
    }

    /**
     * Retrieve an Ansel_Gallery given the gallery's slug
     *
     * @param string $slug  The gallery slug
     * @param array $overrides  An array of attributes that should be overridden
     *                          when the gallery is returned.
     *
     * @return mixed  Ansel_Gallery object | PEAR_Error
     */
    function &getGalleryBySlug($slug, $overrides = array())
    {
        $id = $this->slugExists($slug);
        if ($id) {
            return $this->getGallery($id, $overrides);
        } else {
            return PEAR::raiseError(sprintf(_("Gallery %s not found."), $slug));
        }
     }

    /**
     * Retrieve an Ansel_Gallery given the share id
     *
     * @param integer $gallery_id  The share_id to fetch
     * @param array $overrides     An array of attributes that should be
     *                             overridden when the gallery is returned.
     *
     * @return mixed  Ansel_Gallery | PEAR_Error
     */
    function &getGallery($gallery_id, $overrides = array())
    {
        // avoid cache server hits
        if (isset($this->galleries[$gallery_id]) && !count($overrides)) {
            return $this->galleries[$gallery_id];
        }

       if (!count($overrides) && $GLOBALS['conf']['ansel_cache']['usecache'] &&
           ($gallery = $GLOBALS['cache']->get('Ansel_Gallery' . $gallery_id, $GLOBALS['conf']['cache']['default_lifetime'])) !== false) {

               $this->galleries[$gallery_id] = unserialize($gallery);

               return $this->galleries[$gallery_id];
       }

       $result = &$this->shares->getShareById($gallery_id);
       if (is_a($result, 'PEAR_Error')) {
           return $result;
       }
       $this->galleries[$gallery_id] = &$result;

       // Don't cache if we have overridden anything
       if (!count($overrides)) {
           if ($GLOBALS['conf']['ansel_cache']['usecache']) {
               $GLOBALS['cache']->set('Ansel_Gallery' . $gallery_id, serialize($result));
           }
       } else {
           foreach ($overrides as $key => $value) {
               $this->galleries[$gallery_id]->set($key, $value, false);
           }
       }
        return $this->galleries[$gallery_id];
    }

    /**
     * Retrieve an array of Ansel_Gallery objects for the given slugs.
     *
     * @param array $slugs  The gallery slugs
     *
     * @return mixed  Array of Ansel_Gallery objects | PEAR_Error
     */
    function getGalleriesBySlugs($slugs)
    {
        $sql = 'SELECT share_id FROM ' . $this->shares->_table
            . ' WHERE attribute_slug IN (' . str_repeat('?, ', count($slugs) - 1) . '?)';

        $stmt = $this->shares->_db->prepare($sql);
        if (is_a($stmt, 'PEAR_Error')) {
            return $stmt;
        }
        $result = $stmt->execute($slugs);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $ids = array_values($result->fetchCol());
        $shares = $this->shares->getShares($ids);

        $stmt->free();
        $result->free();

        return $shares;
    }

    /**
     * Retrieve an array of Ansel_Gallery objects for the requested ids
     */
    function getGalleries($ids)
    {
        return $this->shares->getShares($ids);
    }

    /**
     * Empties a gallery of all images.
     *
     * @param Ansel_Gallery $gallery  The ansel gallery to empty.
     */
    function emptyGallery($gallery)
    {
        $images = $gallery->listImages();
        foreach ($images as $image) {
            // Pretend we are a stack so we don't update the images count
            // for every image deletion, since we know the end result will
            // be zero.
            $gallery->removeImage($image, true);
        }
        $gallery->set('images', 0, true);

        // Clear the OtherGalleries widget cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
        }

    }

    /**
     * Removes an Ansel_Gallery.
     *
     * @param Ansel_Gallery $gallery  The gallery to delete
     *
     * @return mixed  True || PEAR_Error
     */
    function removeGallery($gallery)
    {
        /* Get any children and empty them */
        $children = $gallery->getChildren(null, true);
        if (is_a($children, 'PEAR_Error')) {
            return $children;
        }
        foreach ($children as $child) {
            $this->emptyGallery($child);
            $child->setTags(array());
        }

        /* Now empty the selected gallery of images */
        $this->emptyGallery($gallery);

        /* Clear all the tags. */
        $gallery->setTags(array());

        /* Get the parent, if it exists, before we delete the gallery. */
        $parent = $gallery->getParent();
        $id = $gallery->id;

        /* Delete the gallery from storage */
        $result = $this->shares->removeShare($gallery);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Expire the cache */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_Gallery' . $id);
        }
        unset($this->galleries[$id]);

        /* See if we need to clear the has_subgalleries field */
        if (is_a($parent, 'Ansel_Gallery')) {
            if (!$parent->countChildren(PERMS_SHOW, false)) {
                $parent->set('has_subgalleries', 0, true);

                if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                    $GLOBALS['cache']->expire('Ansel_Gallery' . $parent->id);
                }
                unset($this->galleries[$id]);
            }
        }

        return true;
    }

    /**
     * Returns the image corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given name.
     */
    function &getImage($id)
    {
        if (isset($this->images[$id])) {
            return $this->images[$id];
        }

        $q = $this->_db->prepare('SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE image_id = ?');
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }
        $result = $q->execute((int)$id);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }
        $image = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $q->free();
        $result->free();
        if (is_null($image)) {
            return PEAR::raiseError(_("Photo not found"));
        } elseif (is_a($image, 'PEAR_Error')) {
            Horde::logMessage($image, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $image;
        } else {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset']);
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset']);
            $this->images[$id] = new Ansel_Image($image);

            return $this->images[$id];
        }
    }

    /**
     * Returns the images corresponding to the given ids.
     *
     * @param array $ids  An array of image ids.
     *
     * @return array of Ansel_Image objects.
     */
    function getImages($ids, $preserve_order = false)
    {
        if (is_array($ids) && count($ids) > 0) {
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE image_id IN (';
            $i = 1;
            $cnt = count($ids);
            foreach ($ids as $id) {
                $sql .= (int)$id . (($i++ < $cnt) ? ',' : ');');
            }

            $images = $this->_db->query($sql);
            if (is_a($images, 'PEAR_Error')) {
                return $images;
            } elseif ($images->numRows() == 0) {
                $images->free();
                return PEAR::raiseError(_("Photos not found"));
            }

            $return = array();
            while ($image = $images->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset']);
                $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset']);
                $return[$image['image_id']] = new Ansel_Image($image);
                $this->images[(int)$image['image_id']] = &$return[$image['image_id']];
            }
            $images->free();

            /* Need to get comment counts if comments are enabled */
            $ccounts = $this->_getImageCommentCounts(array_keys($return));
            if (!is_a($ccounts, 'PEAR_Error') && count($ccounts)) {
                foreach ($return as $key => $image) {
                    $return[$key]->commentCount = (!empty($ccounts[$key]) ? $ccounts[$key] : 0);
                }
            }

            /* Preserve the order the ids were passed in) */
            if ($preserve_order) {
                foreach ($ids as $id) {
                    $ordered[$id] = $return[$id];
                }
                return $ordered;
            }
            return $return;
        } else {
            return array();
        }
    }

    function _getImageCommentCounts($ids)
    {
        global $conf, $registry;

        /* Need to get comment counts if comments are enabled */
        if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && Horde_Auth::getAuth())) &&
            $registry->hasMethod('forums/numMessagesBatch')) {

            return $registry->call('forums/numMessagesBatch',
                                   array($ids, 'ansel'));
        }

        return array();
    }

    /**
     * Return a list of image ids of the most recently added images.
     *
     * @param array $galleries  An array of gallery ids to search in. If
     *                          left empty, will search all galleries
     *                          with PERMS_SHOW.
     * @param integer $limit    The maximum number of images to return
     * @param string $slugs     An array of gallery slugs.
     * @param string $where     Additional where clause
     *
     * @return array An array of Ansel_Image objects
     */
    function getRecentImages($galleries = array(), $limit = 10, $slugs = array())
    {
        $results = array();

        if (!count($galleries) && !count($slugs)) {
            $sql = 'SELECT DISTINCT ' . $this->_getImageFields('i') . ' FROM ansel_images i, '
            . str_replace('WHERE' , ' WHERE i.gallery_id = s.share_id AND (', substr($this->shares->_getShareCriteria(Horde_Auth::getAuth()), 5)) . ')';
        } elseif (!count($slugs) && count($galleries)) {
            // Searching by gallery_id
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images '
                   . 'WHERE gallery_id IN ('
                   . str_repeat('?, ', count($galleries) - 1) . '?) ';
        } elseif (count($slugs)) {
            // Searching by gallery_slug so we need to join the share table
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images LEFT JOIN '
                . $this->shares->_table . ' ON ansel_images.gallery_id = '
                . $this->shares->_table . '.share_id ' . 'WHERE attribute_slug IN ('
                . str_repeat('?, ', count($slugs) - 1) . '?) ';
        } else {
            return array();
        }

        $sql .= ' ORDER BY image_uploaded_date DESC LIMIT ' . (int)$limit;
        $query = $this->_db->prepare($sql);
        if (is_a($query, 'PEAR_Error')) {
            return $query;
        }

        if (count($slugs)) {
            $images = $query->execute($slugs);
        } else {
            $images = $query->execute($galleries);
        }
        $query->free();
        if (is_a($images, 'PEAR_Error')) {
            return $images;
        } elseif ($images->numRows() == 0) {
            return array();
        }

        while ($image = $images->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset']);
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset']);
            $results[] = new Ansel_Image($image);
        }

        $images->free();
        return $results;
    }

    /**
     * Check if a gallery exists. Need to do this here instead of Horde_Share
     * since Horde_Share::exists() takes a share_name, not a share_id plus we
     * might also be checking by gallery_slug and this is more efficient than
     * a listShares() call for one gallery.
     *
     * @param integer $gallery_id  The gallery id
     * @param string  $slug        The gallery slug
     *
     * @return mixed  true | false | PEAR_Error
     */
    function galleryExists($gallery_id, $slug = null)
    {
        if (empty($slug)) {
            return (bool)$this->_db->queryOne(
                'SELECT COUNT(share_id) FROM ' . $this->shares->_table
                . ' WHERE share_id = ' . (int)$gallery_id);
        } else {
            return (bool)$this->slugExists($slug);
        }
    }

   /**
    * Return a list of categories containing galleries with the given
    * permissions for the current user.
    *
    * @param integer $perm   The level of permissions required.
    * @param integer $from   The gallery to start listing at.
    * @param integer $count  The number of galleries to return.
    *
    * @return mixed  List of categories | PEAR_Error
    */
    function listCategories($perm = PERMS_SHOW, $from = 0, $count = 0)
    {
        $sql = 'SELECT DISTINCT attribute_category FROM '
               . $this->shares->_table;
        $results = $this->shares->_db->query($sql);
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $all_categories = $results->fetchCol('attribute_category');
        $results->free();
        if (count($all_categories) < $from) {
            return array();
        } else {
            $categories = array();
            foreach ($all_categories as $category) {
                $categories[] = Horde_String::convertCharset(
                    $category, $GLOBALS['conf']['sql']['charset']);
            }
            if ($count > 0) {
                return array_slice($categories, $from, $count);
            } else {
                return array_slice($categories, $from);
            }
        }
    }

    function countCategories($perms = PERMS_SHOW)
    {
        return count($this->listCategories($perms));
    }

   /**
    * Return the count of galleries that the user has specified permissions to
    * and that match any of the requested attributes.
    *
    * @param string  $userid       The user to check access for.
    * @param integer $perm         The level of permissions to require for a
    *                              gallery to return it.
    * @param mixed   $attributes   Restrict the galleries counted to those
    *                              matching $attributes. An array of
    *                              attribute/values pairs or a gallery owner
    *                              username.
    * @param string  $parent       The parent share to start counting at.
    * @param boolean $allLevels    Return all levels, or just the direct
    *                              children of $parent? Defaults to all levels.
    */
    function countGalleries($userid, $perm = PERMS_SHOW, $attributes = null,
                            $parent = null, $allLevels = true)
    {
        static $counts;

        if (is_a($parent, 'Ansel_Gallery')) {
            $parent_id = $parent->getId();
        } else {
            $parent_id = $parent;
        }

        $key = "$userid,$perm,$parent_id,$allLevels"
               . serialize($attributes);
        if (isset($counts[$key])) {
            return $counts[$key];
        }

        $count = $this->shares->countShares($userid, $perm, $attributes,
                                            $parent, $allLevels);

        $counts[$key] = $count;

        return $count;
    }

   /**
    * Retrieves the current user's gallery list from storage.
    *
    * @param integer $perm         The level of permissions to require for a
    *                              gallery to return it.
    * @param mixed   $attributes   Restrict the galleries counted to those
    *                              matching $attributes. An array of
    *                              attribute/values pairs or a gallery owner
    *                              username.
    * @param mixed   $parent       The parent gallery to start listing at.
    *                              (Ansel_Gallery, gallery id or null)
    * @param boolean $allLevels    Return all levels, or just the direct
    *                              children of $parent?
    * @param integer $from         The gallery to start listing at.
    * @param integer $count        The number of galleries to return.
    * @param string  $sort_by      The field to order the results by.
    * @param integer $direction    Sort direction:
    *                               0 - ascending
    *                               1 - descending
    *
    * @return mixed An array of Ansel_Gallery objects | PEAR_Error
    */
    function listGalleries($perm = PERMS_SHOW,
                           $attributes = null,
                           $parent = null,
                           $allLevels = true,
                           $from = 0,
                           $count = 0,
                           $sort_by = null,
                           $direction = 0)
    {
        return $this->shares->listShares(Horde_Auth::getAuth(), $perm, $attributes,
                                         $from, $count, $sort_by, $direction,
                                         $parent, $allLevels);
    }

    /**
     * Retrieve json data for an arbitrary list of image ids, not necessarily
     * from the same gallery.
     *
     * @param array $images        An array of image ids
     * @param string $style        A named gallery style to force if requesting
     *                             pretty thumbs.
     * @param boolean $full        Generate full urls
     * @param string $image_view   Which image view to use? screen, thumb etc..
     * @param boolean $view_links  Include links to the image view
     *
     * @return string  The json data || PEAR_Error
     */
    function getImageJson($images, $style = null, $full = false,
                          $image_view = 'mini', $view_links = false)
    {
        $galleries = array();
        if (is_null($style)) {
            $style = 'ansel_default';
        }

        $json = array();

        foreach ($images as $id) {
            $image = $this->getImage($id);
            if (!is_a($image, 'PEAR_Error')) {
                $gallery_id = abs($image->gallery);

                if (empty($galleries[$gallery_id])) {
                    $galleries[$gallery_id]['gallery'] = $GLOBALS['ansel_storage']->getGallery($gallery_id);
                    if (is_a($galleries[$gallery_id]['gallery'], 'PEAR_Error')) {
                        return $galleries[$gallery_id];
                    }
                }

                // Any authentication that needs to take place for any of the
                // images included here MUST have already taken place or the
                // image will not be incldued in the output.
                if (!isset($galleries[$gallery_id]['perm'])) {
                    $galleries[$gallery_id]['perm'] =
                        ($galleries[$gallery_id]['gallery']->hasPermission(Horde_Auth::getAuth(), PERMS_READ) &&
                         $galleries[$gallery_id]['gallery']->isOldEnough() &&
                         !$galleries[$gallery_id]['gallery']->hasPasswd());
                }

                if ($galleries[$gallery_id]['perm']) {
                    $data = array(Ansel::getImageUrl($image->id, $image_view, $full, $style),
                        htmlspecialchars($image->filename, ENT_COMPAT, Horde_Nls::getCharset()),
                        Horde_Text_Filter::filter($image->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL)),
                        $image->id,
                        0);

                    if ($view_links) {
                        $data[] = Ansel::getUrlFor('view',
                            array('gallery' => $image->gallery,
                                  'image' => $image->id,
                                  'view' => 'Image',
                                  'slug' => $galleries[$gallery_id]['gallery']->get('slug')),
                            $full);

                        $data[] = Ansel::getUrlFor('view',
                            array('gallery' => $image->gallery,
                                  'slug' => $galleries[$gallery_id]['gallery']->get('slug'),
                                  'view' => 'Gallery'),
                            $full);
                    }

                    $json[] = $data;
                }
            }
        }

        if (count($json)) {
            return Horde_Serialize::serialize($json, Horde_Serialize::JSON, Horde_Nls::getCharset());
        } else {
            return '';
        }
    }

    /**
     * Returns a random Ansel_Gallery from a list fitting the search criteria.
     *
     * @see Ansel_Storage::listGalleries()
     */
    function getRandomGallery($perm = PERMS_SHOW, $attributes = null,
                              $parent = null, $allLevels = true)
    {
        $num_galleries = $this->countGalleries(Horde_Auth::getAuth(), $perm,
                                               $attributes, $parent,
                                               $allLevels);
        if (!$num_galleries) {
            return $num_galleries;
        }

        $galleries = $this->listGalleries($perm, $attributes, $parent,
                                          $allLevels,
                                          rand(0, $num_galleries - 1),
                                          1);
        $gallery = array_pop($galleries);
        return $gallery;
    }

    /**
     * Lists a slice of the image ids in the given gallery.
     *
     * @param integer $gallery_id  The gallery to list from.
     * @param integer $from        The image to start listing.
     * @param integer $count       The numer of images to list.
     * @param mixed $fields        The fields to return (either an array of
     *                             fileds or a single string).
     * @param string $where        A SQL where clause ($gallery_id will be
     *                             ignored if this is non-empty).
     * @param mixed $sort          The field(s) to sort by.
     *
     * @return mixed  An array of image_ids | PEAR_Error
     */
    function listImages($gallery_id, $from = 0, $count = 0,
                        $fields = 'image_id', $where = '', $sort = 'image_sort')
    {
        if (is_array($fields)) {
            $field_count = count($fields);
            $fields = implode(', ', $fields);
        } elseif ($fields == '*') {
            // The count is not important, as long as it's > 1
            $field_count = 2;
        } else {
            $field_count = substr_count($fields, ',') + 1;
        }

        if (is_array($sort)) {
            $sort = implode(', ', $sort);
        }

        if (!empty($where)) {
            $query_where = 'WHERE ' . $where;
        } else {
            $query_where = 'WHERE gallery_id = ' . $gallery_id;
        }
        $this->_db->setLimit($count, $from);
        $sql = 'SELECT ' . $fields . ' FROM ansel_images ' . $query_where . ' ORDER BY ' . $sort;
        Horde::logMessage('Query by Ansel_Storage::listImages: ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $results = $this->_db->query('SELECT ' . $fields . ' FROM ansel_images '
            . $query_where . ' ORDER BY ' . $sort);
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        if ($field_count > 1) {
            return $results->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, false);
        } else {
            return $results->fetchCol();
        }
    }

    /**
     * Return images' geolocation data.
     *
     * @param array $image_ids  An array of image_ids to look up.
     * @param integer $gallery  A gallery id. If this is provided, will return
     *                          all images in the gallery that have geolocation
     *                          data ($image_ids would be ignored).
     *
     * @return mixed An array of geodata || PEAR_Error
     */
    function getImagesGeodata($image_ids = array(), $gallery = null)
    {
        if ((!is_array($image_ids) || count($image_ids) == 0) && empty($gallery)) {
            return array();
        }

        if (!empty($gallery)) {
            $where = 'gallery_id = ' . (int)$gallery . ' AND LENGTH(image_latitude) > 0';
        } elseif (count($image_ids) > 0) {
            $where = 'image_id IN(' . implode(',', $image_ids) . ') AND LENGTH(image_latitude) > 0';
        } else {
            return array();
        }

        return $this->listImages(0, 0, 0, array('image_id as id', 'image_id', 'image_latitude', 'image_longitude', 'image_location'), $where);
    }

    /**
     * Get image attribtues from ansel_image_attributes table
     *
     * @param $image_id
     * @return unknown_type
     */
    function getImageAttributes($image_id)
    {
        return $GLOBALS['ansel_db']->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int)$image_id, null, MDB2_FETCHMODE_ASSOC, true);
    }

    /**
     * Like getRecentImages, but returns geotag data for the most recently added
     * images from the current user. Useful for providing images to help locate
     * images at the same place.
     */
    function getRecentImagesGeodata($user = null, $start = 0, $count = 8)
    {
        $galleries = $this->listGalleries('PERMS_EDIT', $user);
        $where = 'gallery_id IN(' . implode(',', array_keys($galleries)) . ') AND LENGTH(image_latitude) > 0 GROUP BY image_latitude, image_longitude';
        return $this->listImages(0, $start, $count, array('image_id as id', 'image_id', 'gallery_id', 'image_latitude', 'image_longitude', 'image_location'), $where, 'image_geotag_date DESC');
    }

    function searchLocations($search = '')
    {
        $sql = 'SELECT DISTINCT image_location, image_latitude, image_longitude'
            . ' FROM ansel_images WHERE image_location LIKE "' . $search . '%"';
        $results = $this->_db->query($sql);
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }

        return $results->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, false);
    }

    /**
     * Helper function to get a string of field names
     *
     * @return string
     */
    function _getImageFields($alias = '')
    {
        $fields = array('image_id', 'gallery_id', 'image_filename', 'image_type',
                        'image_caption', 'image_uploaded_date', 'image_sort',
                        'image_faces', 'image_original_date', 'image_latitude',
                        'image_longitude', 'image_location', 'image_geotag_date');
        if (!empty($alias)) {
            foreach ($fields as $field) {
                $new[] = $alias . '.' . $field;
            }
            return implode(', ', $new);
        }

        return implode(', ', $fields);
    }

}
