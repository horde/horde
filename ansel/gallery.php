<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// Redirect to the gallery list if no action has been requested.
$actionID = Horde_Util::getFormData('actionID');
if (is_null($actionID)) {
    Horde::applicationUrl('view.php?view=List', true)->redirect();
    exit;
}

// Run through the action handlers.
switch ($actionID) {
case 'add':
    // Set up the gallery attributes.
    $gallery_name = '';
    $gallery_desc = '';
    $gallery_category = $prefs->getValue('default_category');
    $gallery_tags = '';
    $gallery_thumbstyle = '';
    $gallery_slug = '';
    $gallery_age = 0;
    $gallery_download = $prefs->getValue('default_download');
    $gallery_parent = null;
    $galleryId = null;
    $gallery_mode = 'Normal';
    $gallery_passwd = '';

    Horde::addInlineScript(array(
        '$("gallery_name").focus()'
    ), 'dom');

    $title = _("Adding A New Gallery");
    break;

case 'addchild':
    // Get the parent and make sure that it exists and that we have
    // permissions to add to it.
    $parentId = Horde_Util::getFormData('gallery');
    try {
        $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($parentId);
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
        Horde::applicationUrl('view.php?view=List', true)->redirect();
        exit;
    }

    if (!$parent->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied adding a gallery to \"%s\"."),
                            $parent->get('name')), 'horde.error');
        Horde::applicationUrl('view.php?view=List', true)->redirect();
        exit;
    }

    // Set up the gallery attributes.
    $gallery_name = '';
    $gallery_desc = '';
    $gallery_category = $prefs->getValue('default_category');
    $gallery_tags = '';
    $gallery_slug = '';
    $gallery_age = 0;
    $gallery_thumbstyle = $parent->get('style');
    $gallery_download = $prefs->getValue('default_download');
    $gallery_parent = $parentId;
    $galleryId = null;
    $gallery_mode = 'Normal';
    $gallery_passwd = '';

    Horde::addInlineScript(array(
        '$("gallery_name").focus()'
    ), 'dom');

    $title = sprintf(_("Adding A Subgallery to %s"), $parent->get('name'));
    break;

case 'downloadzip':
    $galleryId = Horde_Util::getFormData('gallery');
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    if (!$registry->getAuth() ||
        !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {

        $notification->push(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')), 'horde.error');
        Horde::applicationUrl('view.php?view=List', true)->redirect();
        exit;
    }

    Ansel::downloadImagesAsZip($gallery);
    exit;

case 'modify':
    $galleryId = Horde_Util::getFormData('gallery');

    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
         // Set up the gallery attributes.
        $gallery_name = $gallery->get('name');
        $gallery_desc = $gallery->get('desc');
        $gallery_category = $gallery->get('category');
        $gallery_tags = implode(',', $gallery->getTags());
        $gallery_thumbstyle = $gallery->get('style');
        $gallery_slug = $gallery->get('slug');
        $gallery_age = (int)$gallery->get('age');
        $gallery_download = $gallery->get('download');
        $title = sprintf(_("Modifying: %s"), $gallery_name);
        $gallery_parent = $gallery->getParent();
        if (!is_null($gallery_parent)) {
            $gallery_parent = $gallery_parent->getId();
        }
        $gallery_mode = $gallery->get('view_mode');
        $gallery_passwd = $gallery->get('passwd');
    } catch (Ansel_Exception $e) {
        $title = _("Unknown Gallery");
    }

    break;

case 'save':
    // Check general permissions.
    if (!$registry->isAdmin() &&
        ($injector->getInstance('Horde_Perms')->exists('ansel') &&
         !$injector->getInstance('Horde_Perms')->hasPermission('ansel', $registry->getAuth(), Horde_Perms::EDIT))) {
        $notification->push(_("Access denied editing galleries."), 'horde.error');
        Horde::applicationUrl('view.php?view=List', true)->redirect();
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
    if ($new_category = Horde_Util::getFormData('new_category')) {
        $cManager = new Horde_Prefs_CategoryManager();
        $new_category = $cManager->add($new_category);
        if ($new_category) {
            $gallery_category = $new_category;
        }
    } else {
        $gallery_category = Horde_Util::getFormData('gallery_category');
    }

    $gallery_tags = Horde_Util::getFormData('gallery_tags');
    $gallery_thumbstyle = Horde_Util::getFormData('gallery_style');
    $gallery_parent = Horde_Util::getFormData('gallery_parent');
    // Double check for an empty string instead of null
    if (empty($gallery_parent)) {
        $gallery_parent = null;
    }
    if ($galleryId &&
        ($exists = ($GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->galleryExists($galleryId)) === true)) {

        // Modifying an existing gallery.
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            $notification->push(sprintf(_("Access denied saving gallery \"%s\"."), $gallery->get('name')), 'horde.error');
        } else {
            // Don't allow the display name to be nulled out.
            if ($gallery_name) {
                $gallery->set('name', $gallery_name);
            }

            $gallery->set('desc', $gallery_desc);
            $gallery->set('category', $gallery_category);
            $gallery->setTags(explode(',', $gallery_tags));
            $gallery->set('style', $gallery_thumbstyle);
            $gallery->set('slug', $gallery_slug);
            $gallery->set('age', $gallery_age);
            $gallery->set('download', $gallery_download);
            $gallery->set('view_mode', $gallery_mode);
            if ($registry->getAuth() &&
                $gallery->get('owner') == $registry->getAuth()) {
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
                    $notification->push($e->getMessage(), 'horde.error');
                    Horde::applicationUrl(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                    exit;
                }
            }
            try {
                $result = $gallery->save();
                $notification->push(_("The gallery was saved."),'horde.success');
            } catch (Ansel_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
            }
        }
    } else {
        // Is this a new subgallery?
        if ($gallery_parent) {
            try {
                $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_parent);
            } catch (Ansel_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
                Horde::applicationUrl(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                exit;
            }
            if (!$parent->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                $notification->push(sprintf(
                    _("You do not have permission to add children to %s."),
                    $parent->get('name')), 'horde.error');

                Horde::applicationUrl(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
                exit;
            }
        }

        // Require a display name.
        if (!$gallery_name) {
            $notification->push(
                _("You must provide a display name for your new gallery."),
                'horde.warning');
            $actionId = 'add';
            $title = _("Adding A New Gallery");
            break;
        }

        // Create the new gallery.
        $perm = (!empty($parent)) ? $parent->getPermission() : null;
        $parent = (!empty($gallery_parent)) ? $gallery_parent : null;

        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->createGallery(
                    array('name' => $gallery_name,
                          'desc' => $gallery_desc,
                          'category' => $gallery_category,
                          'tags' => explode(',', $gallery_tags),
                          'style' => $gallery_thumbstyle,
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
            $notification->push($msg, 'horde.success');
        } catch (Ansel_Exception $e) {
            $galleryId = null;
            $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"),
                             $gallery_name, $gallery->getMessage());
            Horde::logMessage($error, 'ERR');
            $notification->push($error, 'horde.error');
        }

    }

    // Clear the OtherGalleries widget cache
    if ($conf['ansel_cache']['usecache']) {
        $injector->getInstance('Horde_Cache')->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
    }

    // Return to the last view.
    $url = Horde_Util::getFormData('url');
    if (empty($url) && empty($exists)) {
        // Redirect to the images upload page for newly creted galleries
        $url = Horde::applicationUrl('img/upload.php')->add('gallery', $galleryId);
    } elseif (empty($url)) {
        $url = Horde::applicationUrl('index.php', true);
    }
    $url->redirect();
    exit;

case 'delete':
case 'empty':
    // Print the confirmation screen.
    $galleryId = Horde_Util::getFormData('gallery');
    if ($galleryId) {
        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            require ANSEL_TEMPLATES . '/common-header.inc';
            require ANSEL_TEMPLATES . '/menu.inc';
            require ANSEL_TEMPLATES . '/gallery/delete_confirmation.inc';
            require $registry->get('templates', 'horde') . '/common-footer.inc';
            exit;
        } catch (Ansel_Exception $e) {
            $notification->push($gallery->getMessage(), 'horde.error');
        }
    }

    // Return to the gallery list.
    Horde::applicationUrl(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
    exit;

case 'generateDefault':
    // Re-generate the default pretty gallery image.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
        $gallery->clearStacks();
        $notification->push(_("The gallery's default photo has successfully been reset."), 'horde.success');
        Horde::applicationUrl('view.php', true)->add('gallery', $galleryId)->redirect();
        exit;
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
        Horde::applicationUrl('index.php', true)->redirect();
        exit;
    }

case 'generateThumbs':
    // Re-generate all of this gallery's prettythumbs.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    } catch (Ansel_Exception $e) {
        $notification->push($gallery->getMessage(), 'horde.error');
        Horde::applicationUrl('index.php', true)->redirect();
        exit;
    }
    $gallery->clearThumbs();
    $notification->push(_("The gallery's thumbnails have successfully been reset."), 'horde.success');
    Horde::applicationUrl('view.php', true)->add('gallery', $galleryId)->redirect();
    exit;

case 'deleteCache':
    // Delete all cached image views.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    } catch (Ansel_Exception $e) {
        $notification->push($gallery->getMessage(), 'horde.error');
        Horde::applicationUrl('index.php', true)->redirect();
        exit;
    }
    $gallery->clearViews();
    $notification->push(_("The gallery's views have successfully been reset."), 'horde.success');
    Horde::applicationUrl('view.php', true)->add('gallery', $galleryId)->redirect();
    exit;

default:
    Horde::applicationUrl(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
    exit;
}

Horde::addScriptFile('stripe.js', 'horde');
require ANSEL_TEMPLATES . '/common-header.inc';

/* Attach the slug check action to the form */
$injector->getInstance('Horde_Ajax_Imple')->getImple(array('ansel', 'GallerySlugCheck'), array(
    'bindTo' => 'gallery_slug',
    'slug' => $gallery_slug
));
Horde::addScriptFile('popup.js', 'horde');
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/gallery/gallery.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
