<?php
/**
 * Ansel RSS feed. Note that we always return a 'normal' thumb image
 * and not a prettythumb since we have no way of knowing what the client
 * requesting this will be viewing the image on.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel', array('session_control' => 'readonly'));

// Get form data
$stream_type = Horde_Util::getFormData('stream_type', 'all');
$id = Horde_Util::getFormData('id');
$type = basename(Horde_Util::getFormData('type', 'rss2'));
$slug = Horde_Util::getFormData('slug');
$uid = md5($stream_type . $id . $type . $GLOBALS['registry']->getAuth());
$filename = 'ansel_feed_template_' . $uid;
if ($conf['ansel_cache']['usecache']) {
    $cache_key = 'ansel_feed_template_' . $uid;
    $rss = $GLOBALS['injector']->getInstance('Horde_Cache')->get($cache_key, $conf['cache']['default_lifetime']);
    $filename = $GLOBALS['injector']->getInstance('Horde_Cache')->get($filename, $conf['cache']['default_lifetime']);
}

if (empty($rss)) {
    // Assume failure
    $params = array('last_modified' => time(),
                    'name' => _("Error retrieving feed"),
                    'link' => '',
                    'desc' => _("Unable to retrieve requested feed"),
                    'image_url' => Horde::img('alerts/error.png'),
                    'image_link' => '',
                    'image_alt' => '');
    $author = '';

    // Determine what we are requesting
    switch ($stream_type) {
    case 'all':
        try {
            $images = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getRecentImages();
        } catch (Ansel_Exception $e) {
            $images = array();
        }

        // Eventually would like the link to link to a search
        // results page containing the same images present in this
        // feed. For now, just link to the List view until some of
        // the search code can be refactored.
        $params = array('last_modified' => $images[0]->uploaded,
                        'name' => sprintf(_("Recently added photos on %s"),
                                          $conf['server']['name']),
                        'link' => Ansel::getUrlFor('view',
                                                   array('view' => 'List'),
                                                   true),
                        'desc' => sprintf(_("Recently added photos on %s"),
                                          $conf['server']['name']),
                        'image_url' => Ansel::getImageUrl($images[0]->id,
                                                          'thumb', true),
                        'image_alt' => $images[0]->caption,
                        'image_link' => Ansel::getUrlFor(
                            'view', array('image' => $images[0]->id,
                                          'view' => 'Image',
                                          'gallery' => $images[0]->gallery),
                            true));

        break;

    case 'gallery':
        // Retrieve latest from specified gallery
        // Try a slug first.
        if ($slug) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGalleryBySlug($slug);
        } elseif (is_numeric($id)) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($id);
        }
        if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW) &&
            !$gallery->hasPasswd() && $gallery->isOldEnough()) {

            if (!$gallery->countImages() && $gallery->hasSubGalleries()) {
                $subgalleries = $GLOBALS['injector']
                    ->getInstance('Ansel_Injector_Factory_Storage')
                    ->create()
                    ->listGalleries(array('parent' => $gallery));
                $subs = array();
                foreach ($subgalleries as $subgallery) {
                    $subs[] = $subgallery->id;
                }
                $images = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getRecentImages($subs);
            } else {
                $images = $gallery->getRecentImages();
                $owner = $gallery->getIdentity();
                $author = $owner->getValue('from_addr');
            }
        }

        if (!count($images)) {
            $images = array();
        } else {
            $viewurl = Ansel::getUrlFor('view',
                                        array('view' => 'Gallery',
                                              'gallery' => $id),
                                        true);
            $img = &$GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')));
            $params = array('last_modified' => $gallery->get('last_modified'),
                            'name' => sprintf(_("%s on %s"),
                                              $gallery->get('name'),
                                              $conf['server']['name']),
                            'link' => $viewurl,
                            'desc' => $gallery->get('desc'),
                            'image_url' => Ansel::getImageUrl($img->id, 'thumb', true),
                            'image_alt' => $img->caption,
                            'image_link' => Ansel::getUrlFor('view',
                                                             array('image' => $img->id,
                                                                   'gallery' => $img->gallery,
                                                                   'view' => 'Image'),
                                                             true));
        }
        break;

    case 'user':
        $galleries = array();
        try {
            $shares = $GLOBALS['injector']
                ->getInstance('Ansel_Injector_Factory_Storage')
                ->create()
                ->listGalleries(array('filter' => $id));
            foreach ($shares as $gallery) {
                if ($gallery->isOldEnough() && !$gallery->hasPasswd()) {
                    $galleries[] = $gallery->id;
                }
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
        }
        $images = array();
        if (isset($galleries) && count($galleries)) {
            try {
                $images = $GLOBALS['injector']
                    ->getInstance('Ansel_Injector_Factory_Storage')
                    ->create()
                    ->getRecentImages($galleries);
            } catch (Ansel_Exception $e) {
                 Horde::logMessage($e->getMessage(), 'ERR');
            }
            if (count($images)) {
                $owner = $injector->getInstance('Horde_Core_Factory_Identity')->create($id);
                $name = $owner->getValue('fullname');
                $author = $owner->getValue('from_addr');
                if (!$name) {
                    $name = $id;
                }
                $params = array('last_modified' => $images[0]->uploaded,
                                'name' => sprintf(_("Photos by %s"),
                                                  $name),
                                'link' => Ansel::getUrlFor('view',
                                                           array('view' => 'List',
                                                                 'groupby' => 'owner',
                                                                 'owner' => $id),
                                                           true),
                                'desc' => sprintf(_("Recently added photos by %s on %s"),
                                                  $name, $conf['server']['name']),
                                'image_url' => Ansel::getImageUrl($images[0]->id, 'thumb', true),
                                'image_alt' => $images[0]->caption,
                                'image_link' => Ansel::getUrlFor(
                                    'view', array('image' => $images[0]->id,
                                                  'gallery' => $images[0]->gallery,
                                                  'view' => 'Image'), true)
                );
            }
        }
        break;

    case 'tag':
        $filter = array('typeId' => 'image',
                        'limit' => 10);
        $images = $GLOBALS['injector']->getInstance('Ansel_Tagger')->search(array($id), $filter);

        try {
            $images = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImages(array('ids' => $images['images']));
        } catch (Ansel_Exception $e) {
             Horde::logMessage($e->getMessage(), 'ERR');
             $images = array();
        }
        if (count($images)) {
            $images = array_values($images);
            $params = array('last_modified' => $images[0]->uploaded,
                            'name' => sprintf(_("Photos tagged with %s on %s"),
                                              $id, $conf['server']['name']),
                            'link' => Ansel::getUrlFor('view',
                                                       array('tag' => $id,
                                                             'view' => 'Results'),
                                                       true),
                            'desc' => sprintf(_("Photos tagged with %s on %s"),
                                              $id, $conf['server']['name']),
                            'image_url' => Ansel::getImageUrl($images[0]->id, 'thumb', true, 'ansel_default'),
                            'image_alt' => $images[0]->caption,
                            'image_link' => Ansel::getUrlFor('view',
                                                             array('view' => 'Image',
                                                                   'image' => $images[0]->id,
                                                                   'gallery' => $images[0]->gallery),
                                                             true)
                      );
        }

        // Do this here to avoid iterating the images twice
        $galleries = array();
        $imgs = array();
        $cnt = count($images);
        for ($i = 0; $i < $cnt; ++$i) {
            $gallery_id = $images[$i]->gallery;
            if (empty($galleries[$gallery_id])) {
                try {
                    $galleries[$gallery_id]['gallery'] = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($gallery_id);
                } catch (Ansel_Exception $e) {}
            }
            if (!isset($galleries[$gallery_id]['perm'])) {
                $galleries[$gallery_id]['perm'] =
                    ($galleries[$gallery_id]['gallery']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ) &&
                     $galleries[$gallery_id]['gallery']->isOldEnough() &&
                     !$galleries[$gallery_id]['gallery']->hasPasswd());
            }

            if ($galleries[$gallery_id]['perm']) {
                $imgs[$i]['link'] = Ansel::getUrlFor(
                    'view',
                    array('view' => 'Image',
                          'gallery' => $images[$i]->gallery,
                          'image' => $images[$i]->id), true);
                $imgs[$i]['filename'] = $images[$i]->filename;
                $imgs[$i]['caption'] = $images[$i]->caption;
                $imgs[$i]['url'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'screen', true));
                $imgs[$i]['type'] = $images[$i]->getType('screen');
                $imgs[$i]['author'] = $author;
                $imgs[$i]['thumb'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'thumb', true));
                $imgs[$i]['latitude'] = $images[$i]->lat;
                $imgs[$i]['longitude'] = $images[$i]->lng;
            }
        }

    }

    if (!isset($imgs)) {
        $imgs = array();
        $cnt = count($images);
        for ($i = 0; $i < $cnt; ++$i) {
            $imgs[$i]['link'] = Ansel::getUrlFor(
                'view',
                array('view' => 'Image',
                      'gallery' => $images[$i]->gallery,
                      'image' => $images[$i]->id), true);
            $imgs[$i]['filename'] = $images[$i]->filename;
            $imgs[$i]['caption'] = $images[$i]->caption;
            $imgs[$i]['url'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'screen', true));
            $imgs[$i]['type'] = $images[$i]->getType('screen');
            $imgs[$i]['author'] = $author;
            $imgs[$i]['thumb'] = htmlspecialchars(Ansel::getImageUrl($images[$i]->id, 'thumb', true));
            $imgs[$i]['latitude'] = $images[$i]->lat;
            $imgs[$i]['longitude'] = $images[$i]->lng;
        }
    }

    $xsl = $registry->get('themesuri') . '/feed-rss.xsl';
    $stream_name = htmlspecialchars($params['name']);
    $stream_desc = htmlspecialchars($params['desc']);
    $stream_updated = htmlspecialchars(date('r', $params['last_modified']));
    $stream_official = htmlspecialchars($params['link']);
    $image_url = htmlspecialchars($params['image_url']);
    $image_link = htmlspecialchars($params['image_link']);
    $image_alt = htmlspecialchars($params['image_alt']);
    $ansel = 'Ansel ' . $registry->getVersion('ansel') . ' (http://www.horde.org/)';

    if ($stream_type != 'all' && $type != 'rss2') {
        $getparams = array('stream_type' => $stream_type, 'type' => $type);
        if (isset($id)) {
            $getparams['id'] = $id;
        }
    } else {
        $getparams = array();
    }
    $stream_rss = Horde::url('rss.php', true, -1)->add($getparams);
    $stream_rss2 = Horde::url('rss.php', true, -1)->add($getparams);
    $images = $imgs;

    Horde::startBuffer();
    include ANSEL_TEMPLATES . '/rss/' . $type . '.inc';
    $rss = Horde::endBuffer();

    if ($conf['ansel_cache']['usecache']) {
        $GLOBALS['injector']->getInstance('Horde_Cache')->set($cache_key, $rss);
        $GLOBALS['injector']->getInstance('Horde_Cache')->set($filename, $params['name']);
    }
}

$browser->downloadHeaders($filename . '.rss', 'text/xml', true);
echo $rss;
