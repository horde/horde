<?php
/**
 * Class to encapsulate the UI for adding/viewing/changing galleries.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_GalleryProperties
{
    /**
     * View parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Hash of gallery properties.
     *
     * @var array
     */
    protected $_properties;

    /**
     * The view title
     *
     * @var string
     */
    protected $_title;

    /**
     * Const'r
     *
     * @param array $params  Parameters for the view
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Runs the view
     *
     * @return void
     */
    public function run()
    {
        switch ($this->_params['actionID']) {
        case 'add':
            $this->_runNew();
            $this->_output();
            break;
        case 'addchild':
            $this->_runNewChild();
            $this->_output();
            break;
        case 'modify':
            $this->_runEdit();
            $this->_output();
            break;
        case 'save':
            $this->_runSave();
            break;
        }
    }

    private function _loadDefaults()
    {
        /*Gallery properties */
        $this->_properties = array(
            'name' => '',
            'desc' => '',
            'tags' => '',
            'style' => Ansel::getStyleDefinition($GLOBALS['prefs']->getValue('default_gallerystyle')),
            'slug' => '',
            'age' => 0,
            'download' => $GLOBALS['prefs']->getValue('default_download'),
            'parent' => null,
            'id' => null,
            'mode' => 'Normal',
            'passwd' => '',
            'owner' => ''
        );
    }

    /**
     * Outputs the view to the browser.
     *
     * @return void
     */
    private function _output()
    {
        $view = new Horde_View(array('templatePath' => array(ANSEL_TEMPLATES . '/gallery',
                                                             ANSEL_TEMPLATES . '/gallery/partial',
                                                             ANSEL_TEMPLATES . '/gallery/layout')));
        $view->addHelper('Text');
        $view->properties = $this->_properties;
        $view->title = $this->_title;
        $view->action = $this->_params['actionID'];
        $view->url = $this->_params['url'];
        $view->availableThumbs = $this->_thumbStyles();
        $view->galleryViews = $this->_galleryViewStyles();

        Horde::addInlineScript(array('$("gallery_name").focus()'), 'dom');
        Horde::addScriptFile('stripe.js', 'horde');
        Horde::addScriptFile('popup.js', 'horde');

        /* Attach the slug check action to the form */
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('ansel', 'GallerySlugCheck'), array(
            'bindTo' => 'gallery_slug',
            'slug' => $this->_properties['slug']
        ));

        require ANSEL_TEMPLATES . '/common-header.inc';
        echo Horde::menu();
        $GLOBALS['notification']->notify(array('listeners' => 'status'));
        echo $view->render('properties');
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
    }

    /**
     * Set up for adding new galleries.
     *
     * @return void
     */
    private function _runNew()
    {
        $this->_loadDefaults();
        $this->_title = _("Adding a New Gallery");
        $this->_properties['owner'] = $GLOBALS['registry']->getAuth();
    }

    /**
     * Set up for adding a new child gallery.
     *
     * @return void
     */
    private function _runNewChild()
    {
        $this->_loadDefaults();

        // Get the parent and make sure that it exists and that we have
        // permissions to add to it.
        $parentId = $this->_params['gallery'];
        try {
            $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($parentId);
        } catch (Ansel_Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }

        if (!$parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $GLOBALS['notification']->push(sprintf(_("Access denied adding a gallery to \"%s\"."),
                                $parent->get('name')), 'horde.error');
            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }

        // Set up the gallery attributes.
        $this->_properties['style'] = $parent->get('style');
        $this->_properties['parent'] = $parentId;
        $this->_title = sprintf(_("Adding A Subgallery to %s"), $parent->get('name'));
    }

    /**
     * Handle setting up the form for editing an existing gallery
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function _runEdit()
    {
        if (empty($this->_params['gallery'])) {
            throw new InvalidArgumentException(_("Missing gallery parameter"));
        }

        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($this->_params['gallery']);
            $parent = $gallery->getParent();
            $this->_properties = array(
                'name' => $gallery->get('name'),
                'desc' => $gallery->get('desc'),
                'tags' => implode(',', $gallery->getTags()),
                'slug' => $gallery->get('slug'),
                'age' => (int)$gallery->get('age'),
                'download' => $gallery->get('download'),
                'mode' => $gallery->get('view_mode'),
                'passwd' => $gallery->get('passwd'),
                'parent' => !is_null($parent) ? $parent->getId() : $parent,
                'id' => $gallery->getId(),
                'owner' => $gallery->get('owner'),
                'style' => $gallery->getStyle()
            );
            $this->_title = sprintf(_("Modifying: %s"), $this->_properties['name']);
        } catch (Ansel_Exception $e) {
            $title = _("Unknown Gallery");
        }
    }

    /**
     * Handles saving the gallery information from the form submission, and
     * redirects back to previous view when complete.
     *
     * @return void
     */
    private function _runSave()
    {
        // Check general permissions.
        if (!$GLOBALS['registry']->isAdmin() &&
            ($GLOBALS['injector']->getInstance('Horde_Perms')->exists('ansel') &&
             !$GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('ansel', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT))) {

            $GLOBALS['notification']->push(_("Access denied editing galleries."), 'horde.error');
            Horde::url('view.php?view=List', true)->redirect();
            exit;
        }

        // Get the form values.
        $galleryId = Horde_Util::getFormData('gallery');
        $gallery_name = Horde_Util::getFormData('gallery_name');
        $gallery_desc = Horde_Util::getFormData('gallery_desc');
        $gallery_slug = Horde_Util::getFormData('gallery_slug');
        $gallery_age = (int)Horde_Util::getFormData('gallery_age', 0);
        $gallery_download = Horde_Util::getFormData('gallery_download');
        $gallery_mode = Horde_Util::getFormData('view_mode', 'Normal');
        $gallery_passwd = Horde_Util::getFormData('gallery_passwd');
        $gallery_tags = Horde_Util::getFormData('gallery_tags');
        $gallery_thumbstyle = Horde_Util::getFormData('gallery_style');
        $gallery_parent = Horde_Util::getFormData('gallery_parent');

        // Style
        $style = new Ansel_Style(array(
            'thumbstyle' => Horde_Util::getFormData('thumbnail_style'),
            'background' => Horde_Util::getFormData('background_color'),
            'gallery_view' => Horde_Util::getFormData('gallery_view'),
            // temporary hack until widgets are also configurable.
            'widgets' => array(
                 'Tags' => array('view' => 'gallery'),
                 'OtherGalleries' => array(),
                 'Geotag' => array(),
                 'Links' => array(),
                 'GalleryFaces' => array(),
                 'OwnerFaces' => array())
        ));

        // Double check for an empty string instead of null
        if (empty($gallery_parent)) {
            $gallery_parent = null;
        }
        if ($galleryId &&
            ($exists = ($GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->galleryExists($galleryId)) === true)) {

            // Modifying an existing gallery.
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $GLOBALS['notification']->push(sprintf(_("Access denied saving gallery \"%s\"."), $gallery->get('name')), 'horde.error');
            } else {
                // Don't allow the display name to be nulled out.
                if ($gallery_name) {
                    $gallery->set('name', $gallery_name);
                }
                $gallery->set('desc', $gallery_desc);
                $gallery->setTags(!empty($gallery_tags) ? explode(',', $gallery_tags) : '');
                $gallery->set('style', $style);
                $gallery->set('slug', $gallery_slug);
                $gallery->set('age', $gallery_age);
                $gallery->set('download', $gallery_download);
                $gallery->set('view_mode', $gallery_mode);
                if ($GLOBALS['registry']->getAuth() &&
                    $gallery->get('owner') == $GLOBALS['registry']->getAuth()) {
                    $gallery->set('passwd', $gallery_passwd);
                }

                // Did the parent change?
                $old_parent = $gallery->getParent();
                if (!is_null($old_parent)) {
                    $old_parent_id = $old_parent->getId();
                } else {
                    $old_parent_id = null;
                }
                if ($gallery_parent != $old_parent_id) {
                    if (!is_null($gallery_parent)) {
                        $new_parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_parent);
                    } else {
                        $new_parent = null;
                    }
                    try {
                        $result = $gallery->setParent($new_parent);
                    } catch (Ansel_Exception $e) {
                        $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                        Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                        exit;
                    }
                }
                try {
                    $result = $gallery->save();
                    $GLOBALS['notification']->push(_("The gallery was saved."),'horde.success');
                } catch (Ansel_Exception $e) {
                    $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                }
            }
        } else {
            // Is this a new subgallery?
            if ($gallery_parent) {
                try {
                    $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_parent);
                } catch (Ansel_Exception $e) {
                    $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                    Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                    exit;
                }
                if (!$parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                    $GLOBALS['notification']->push(sprintf(
                        _("You do not have permission to add children to %s."),
                        $parent->get('name')), 'horde.error');

                    Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                    exit;
                }
            }

            // Require a display name.
            if (!$gallery_name) {
                $GLOBALS['notification']->push(
                    _("You must provide a display name for your new gallery."),
                    'horde.warning');
                $actionId = 'add';
                $title = _("Adding A New Gallery");
            }

            // Create the new gallery.
            $perm = (!empty($parent)) ? $parent->getPermission() : null;
            $parent = (!empty($gallery_parent)) ? $gallery_parent : null;

            try {
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->createGallery(
                        array('name' => $gallery_name,
                              'desc' => $gallery_desc,
                              'tags' => explode(',', $gallery_tags),
                              'style' => $style,
                              'slug' => $gallery_slug,
                              'age' => $gallery_age,
                              'download' => $gallery_download,
                              'view_mode' => $gallery_mode,
                              'passwd' => $gallery_passwd,
                              ),
                        $perm, $parent);

                $galleryId = $gallery->getId();
                $msg = sprintf(_("The gallery \"%s\" was created successfully."), $gallery_name);
                Horde::logMessage($msg, 'DEBUG');
                $GLOBALS['notification']->push($msg, 'horde.success');
            } catch (Ansel_Exception $e) {
                $galleryId = null;
                $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"),
                                 $gallery_name, $gallery->getMessage());
                Horde::logMessage($error, 'ERR');
                $GLOBALS['notification']->push($error, 'horde.error');
            }

        }

        // Make sure that the style hash is recorded, ignoring non-styled thumbs
        if ($style->thumbstyle != 'Thumb') {
            $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->ensureHash($gallery->getViewHash('prettythumb'));
        }

        // Clear the OtherGalleries widget cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
        }

        // Return to the last view.
        $url = Horde_Util::getFormData('url');
        if (empty($url) && empty($exists)) {
            // Redirect to the images upload page for newly creted galleries
            $url = Horde::url('img/upload.php')->add('gallery', $galleryId);
        } elseif (empty($url)) {
            $url = Horde::url('index.php', true);
        } else {
            $url = new Horde_Url($url);
        }
        $url->redirect();
    }

   /**
    * Get a list of available, currently usable thumbnail styles.
    *
    * @return array  An array of Classnames => titles
    */
    protected function _thumbStyles()
    {
        // Iterate all available thumbstyles:
        $dir = ANSEL_BASE . '/lib/ImageGenerator';
        $files = scandir($dir);
        $thumbs = array();
        foreach ($files as $file) {
            if (substr($file, -9) == 'Thumb.php') {
                try {
                    $generator = Ansel_ImageGenerator::factory(substr($file, 0, -4), array('style' => ''));
                    $thumbs[substr($file, 0, -4)] = $generator->title;
                } catch (Ansel_Exception $e) {}
            }
        }

        return $thumbs;
    }

    /**
     * Get a list of available Gallery View styles
     *
     * @return array
     */
    protected function _galleryViewStyles()
    {
        // Iterate all available thumbstyles:
        $dir = ANSEL_BASE . '/lib/View/GalleryRenderer';
        $files = scandir($dir);
        $views = array();
        foreach ($files as $file) {
            if ($file != 'Base.php' && $file != '.' && $file != '..') {
                $class = 'Ansel_View_GalleryRenderer_' . substr($file, 0, -4);
                $view = new $class(null);
                $views[substr($file, 0, -4)] = $view->title;
            }
        }

        return $views;
    }
}

