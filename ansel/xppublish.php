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
Horde_Registry::appInit('ansel', array('authentication' => 'none'));

$cmd = Horde_Util::getFormData('cmd');
if (empty($cmd)) {
    $publisher = new Ansel_XPPublisher();
    $publisher->sendRegFile(
        $registry->getApp() . '-' . $conf['server']['name'],
        $registry->get('name'),
        Horde_String::convertCharset(sprintf(_("Publish your photos to %s on %s."), $registry->get('name'), $conf['server']['name']), $registry->getCharset(), $registry->getCharset(true)),
        Horde::applicationUrl('xppublish.php', true, -1)->add('cmd', 'publish'),
        Horde::url(Horde_Themes::img('favicon.ico'), true, -1));
    exit;
}

$PUBLISH_BUTTONS = 'false,true,false';
$PUBLISH_ONBACK = '';
$PUBLISH_ONNEXT = '';
$PUBLISH_CMD = '';

$title = sprintf(_("Publish to %s"), $registry->get('name'));
require ANSEL_TEMPLATES . '/common-header.inc';

// Check for a login.
if ($cmd == 'login') {
    $username = Horde_Util::getFormData('username');
    $password = Horde_Util::getFormData('password');
    if ($username && $password) {
        $auth = $injector->getInstance('Horde_Auth')->getAuth();
        if ($auth->authenticate($username,
                                array('password' => $password))) {
            $cmd = 'list';
            $PUBLISH_BUTTONS = 'true,true,false';
            $PUBLISH_ONBACK = 'history.go(-1);';
        } else {
            echo '<span class="form-error">' . _("Username or password are incorrect.") . '</span>';
            $PUBLISH_BUTTONS = 'false,true,false';
        }
    } else {
        echo '<span class="form-error">'. _("Please enter your username and password.") . '</span>';
        $PUBLISH_BUTTONS = 'false,true,false';
    }
}

// If we don't have a valid login, print the login form.
if (!$registry->isAuthenticated()) {
    $PUBLISH_ONNEXT = 'login.submit();';
    $PUBLISH_CMD = 'login.username.focus();';
    require ANSEL_TEMPLATES . '/xppublish/login.inc';
    require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

// If we already have a login (through sessions or whatever), and this
// is the initial request, assume we want to list galleries.
if ($cmd == 'publish') {
    $cmd = 'list';
}

// We're listing galleries.
$galleryId = Horde_Util::getFormData('gallery');
if ($cmd == 'list') {
    $PUBLISH_ONNEXT = 'folder.submit();';
    $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=publish', true) . '";';
    $PUBLISH_BUTTONS = 'true,true,true';
    require ANSEL_TEMPLATES . '/xppublish/list.inc';
}

// Check if a gallery was selected from the list.
if ($cmd == 'select') {
    if (!$galleryId || !$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->galleryExists($galleryId)) {
        $error = _("Invalid gallery specified.") . "<br />\n";
    } else {
        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            $error = false;
        } catch (Ansel_Exception $e) {
            $error = _("There was an error accessing the gallery");
        }
    }

    if ($error) {
        echo '<span class="form-error">' . $error . '</span><br />';
        echo _("Press the \"Back\" button and try again.");
        $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=list', true) . '";';
        $PUBLISH_BUTTONS = 'true,false,true';
    } else {
        echo '<form id="folder">';
        Horde_Util::pformInput();
        echo '<input type="hidden" name="gallery" value="' . $galleryId . '" />';
        echo '</form>';

        $PUBLISH_CMD = 'publish();';
    }
}

// We're creating a new gallery.
if ($cmd == 'new') {
    $create = Horde_Util::getFormData('create');
    $galleryId = Horde_Util::getFormData('gallery_id');
    $gallery_name = Horde_Util::getFormData('gallery_name');
    $gallery_desc = Horde_Util::getFormData('gallery_desc');
    if ($create) {
        /* Creating a new gallery. */
        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->createGallery(
                    array('name' => $gallery_name, 'desc' => $gallery_desc));
            $galleryId = $gallery->id;
            $msg = sprintf(_("The gallery \"%s\" was created successfully."), $gallery_name);
            Horde::logMessage($msg, 'DEBUG');
        } catch (Ansel_Exception $e) {
            $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"), $gallery_name, $e->getMessage());
            Horde::logMessage($error, 'ERR');
        }
    } else {
        if (empty($galleryId) && $prefs->getValue('autoname')) {
            $galleryId = strval(new Horde_Support_Uuid());
        }
        if (!$gallery_name) {
            $gallery_name = _("Untitled");
        }
        $PUBLISH_CMD = 'folder.gallery_name.focus(); folder.gallery_name.select();';
        $PUBLISH_ONNEXT = 'folder.submit();';
        $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=list', true) . '";';
        $PUBLISH_BUTTONS = 'true,true,true';
        require ANSEL_TEMPLATES . '/xppublish/new.inc';
        require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }

    if ($error) {
        echo '<span class="form-error">' . $error . '</span><br />';
        echo _("Press the \"Back\" button and try again.");
        echo '<form id="folder">';
        Horde_Util::pformInput();
        echo '<input type="hidden" name="cmd" value="new" />';
        echo '<input type="hidden" name="gallery_name" value="' . $gallery_name . '" />';
        echo '</form>';
        $PUBLISH_ONBACK = 'folder.submit();';
        $PUBLISH_BUTTONS = 'true,false,true';
    } else {
        echo '<form id="folder">';
        Horde_Util::pformInput();
        echo '<input type="hidden" name="gallery" value="' . $galleryId . '" />';
        echo '<input type="hidden" name="cmd" value="list" />';
        echo '</form>';

        $PUBLISH_CMD = 'folder.submit();';
    }
}

// We're adding a photo.
if ($cmd == 'add') {
    $galleryId = Horde_Util::getFormData('gallery');
    $name = isset($_FILES['imagefile']['name']) ? Horde_Util::dispelMagicQuotes($_FILES['imagefile']['name']) : null;
    $file = isset($_FILES['imagefile']['tmp_name']) ? $_FILES['imagefile']['tmp_name'] : null;
    if (!$galleryId || !$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->galleryExists($galleryId)) {
        $error = _("Invalid gallery specified.") . "<br />\n";
    } else {
        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $error = sprintf(_("Access denied adding photos to \"%s\"."), $gallery->get('name'));
            } else {
                $error = false;
            }
        } catch (Ansel_Exception $e) {
            $error = _("There was an error accessing the gallery");
        }
    }
    if (!$name || $error) {
        $error = _("No file specified");
    } else {
        try {
            $GLOBALS['browser']->wasFileUploaded('imagefile', _("photo"));
            try {
                $image = Ansel::getImageFromFile($file, array('image_filename' => $name));
            } catch (Ansel_Exception $e) {
                $error = $e->getMessage();
            }

            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            try {
                $image_id = $gallery->addImage($image);
                $error = false;
            } catch (Ansel_Exception $e) {
                $error = _("There was a problem uploading the photo.");
            }
        } catch (Horde_Browser_Exception $e) {
            $error = $e->getMessage();
        }
    }

    if ($error) {
        printf(_("ERROR: %s"), $error);
    } else {
        echo 'SUCCESS';
    }
}

require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
