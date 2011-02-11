<?php
/**
 * Displays mini thumbnails of images in the selected (or random) gallery.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <Duck@obla.net>
 * @author  Marcus Ryan <marcus@horde.org>
 */
class Ansel_Block_Gallery extends Horde_Core_Block
{
    /**
     * @var Ansel_Gallery
     */
    private $_gallery = null;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Gallery");
    }

    /**
     */
    protected function _params()
    {
        $params = array(
            'gallery' => array(
                'name' => _("Gallery"),
                'type' => 'enum',
                'default' => '__random',
                'values' => array('__random' => _("Random gallery"))
            ),
            'perpage' => array(
                'name' => _("Maximum number of photos to display (0 means unlimited)"),
                'type' => 'int',
                'default' => 20
            ),
            'use_lightbox' => array(
                'name' => _("Use a lightbox to view photos"),
                'type' => 'checkbox',
                'default' => true
            )
        );

        $storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
        if (empty($GLOBALS['conf']['gallery']['listlimit']) ||
            ($storage->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::READ) < $GLOBALS['conf']['gallery']['listlimit'])) {

            foreach ($storage->listGalleries() as $gal) {
                $params['gallery']['values'][$gal->id] = $gal->get('name');
            }
        }

        return $params;
    }

    /**
     */
    protected function _title()
    {
        try {
            $gallery = $this->_getGallery();
        } catch (Horde_Exception $e) {
            return Ansel::getUrlFor('view', array('view' => 'List'), true)->link() . $this->getName() . '</a>';
        }

        // Build the gallery name.
        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] == '__random') {
            $name = _("Random Gallery") . ': ' . $gallery->get('name');
        } else {
            $name = $gallery->get('name');
        }
        $viewurl = Ansel::getUrlFor('view',
            array('view' => 'Gallery',
                  'gallery' => $gallery->id,
                  'slug' => $gallery->get('slug')),
            true);

        return $viewurl->link() . htmlspecialchars($name) . '</a>';
    }

    /**
     */
    protected function _content()
    {
        try {
           $gallery = $this->_getGallery();
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $params = array('gallery_id' => $gallery->id,
                        'count' => $this->_params['perpage']);
        if (!empty($this->_params['use_lightbox'])) {
            $params['lightbox'] = true;
        }

        $html = Ansel::embedCode($params);

        // Be nice to people with <noscript>
        $viewurl = Ansel::getUrlFor('view', array('view' => 'Gallery',
                                                  'gallery' => $gallery->id,
                                                  'slug' => $gallery->get('slug')),
                                    true);
        $html .= '<noscript>';
        $html .= $viewurl->link(array('title' => sprintf(_("View %s"), $gallery->get('name'))));
        if ($iid = $gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')) &&
            $gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {

            $html .= '<img src="' . Ansel::getImageUrl($gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')), 'thumb', true) . '" alt="' . htmlspecialchars($gallery->get('name')) . '" />';
        } else {
            $html .= Horde::img('thumb-error.png');
        }

        return $html . '</a></noscript>';
    }

    /**
     * @param boolean $retry
     *
     * @return Ansel_Gallery
     */
    private function _getGallery($retry = false)
    {
        // Make sure we haven't already selected a gallery.
        if ($this->_gallery instanceof Ansel_Gallery) {
            return $this->_gallery;
        }

        // Get the gallery object and cache it.
        if (isset($this->_params['gallery']) && $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($this->_params['gallery']);
        } else {
            $this->_gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getRandomGallery();
        }

        // Protect at least a little bit against getting an empty gallery. We
        // can't just loop until we get one with images since it's possible we
        // actually don't *have* any with images yet.
        if ($this->_params['gallery'] == '__random' &&
            !empty($this->_gallery) &&
            !$this->_gallery->countImages() &&
            $this->_gallery->hasSubGalleries() && !$retry) {

            $this->_gallery = null;
            $this->_gallery = $this->_getGallery(true);
        }

        if (empty($this->_gallery)) {
            throw new Horde_Exception_NotFound(_("Gallery does not exist."));
        } elseif (!$this->_gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW) ||
                  !$this->_gallery->isOldEnough() || $this->_gallery->hasPasswd()) {
            throw new Horde_Exception_PermissionDenied(_("Access denied viewing this gallery."));
        }

        // Return the gallery.
        return $this->_gallery;
    }

}
