<?php

if ($GLOBALS['registry']->images->hasComments() &&
    $GLOBALS['registry']->hasMethod('forums/getThreadsBatch')) {
    $block_name = _("Recent Photo Comments");
}

/**
 * Display most recent image comments for galleries.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_recent_comments extends Horde_Block
{
    /**
     * @var string
     */
    protected $_app = 'ansel';
    
    /**
     *
     * @var Ansel_Gallery
     */
    private $_gallery = null;

    /**
     *
     * @return array
     */
    protected function _params()
    {
        $params = array('gallery' => array(
                        'name' => _("Gallery"),
                        'type' => 'enum',
                        'default' => '__random',
                        'values' => array('all' => 'All')));
        $storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();
        if ($storage->getScope()->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::READ) < $GLOBALS['conf']['gallery']['listlimit']) {
            foreach ($storage->listGalleries(array('perm' => Horde_Perms::READ)) as $id => $gal) {
                $params['gallery']['values'][$id] = $gal->get('name');
            }
        }

        return $params;
    }

    /**
     *
     * @return string
     */
    protected function _title()
    {
        if ($this->_params['gallery'] != 'all') {
            try {
                $gallery = $this->_getGallery();
            } catch (Horde_Exception $e) {
                return Ansel::getUrlFor('view', array('view' => 'List'), true)->link() . _("Gallery") . '</a>';
            }
            // Build the gallery name.
            if (isset($this->_params['gallery'])) {
                $name = @htmlspecialchars($gallery->get('name'), ENT_COMPAT, $GLOBALS['registry']->getCharset());
            }
            $style = $gallery->getStyle();
            $viewurl = Ansel::getUrlFor('view',
                                        array('gallery' => $gallery->id,
                                              'view' => 'Gallery',
                                              'slug' => $gallery->get('slug')),
                                        true);
        } else {
            $viewurl = Ansel::getUrlFor('view', array('view' => 'List'), true);
            $name = _("All Galleries");
        }

        return sprintf(_("Recent Comments In %s"), $viewurl->link() . $name . '</a>');
    }

    /**
     *
     * @global Horde_Registry $registry
     * @return string
     */
    protected function _content()
    {
        global $registry;

        if ($this->_params['gallery'] == 'all') {
            $threads = $registry->call('forums/list', array(0, 'ansel'));
            $image_ids = array();
            foreach ($threads as $thread) {
                $image_ids[] = $thread['forum_name'];
            }
        } else {
            try {
                $gallery = $this->_getGallery();
            } catch (Horde_Exception $e) {
                return $e->getMessage();
            }
            $results = array();
            $image_ids = $gallery->listImages();
        }
        $results = array();
        $threads = $registry->call('forums/getThreadsBatch', array($image_ids, 'message_timestamp', 1, false, 'ansel', null, 0, 10));
        foreach ($threads as $image_id => $messages) {
            foreach ($messages as $message) {
                $message['image_id'] = $image_id;
                $results[] = $message;
            }
        }

        $results = $this->_asortbyindex($results, 'message_timestamp');
        $html = '<div id="ansel_preview"></div>'
            . '<script type="text/javascript">'
            . 'function previewImage(e, image_id) {$(\'ansel_preview\').style.left = Event.pointerX(e) + \'px\'; $(\'ansel_preview\').style.top = Event.pointerY(e) + \'px\';new Ajax.Updater({success:\'ansel_preview\'}, \'' . Horde::url('preview.php') . '\', {method: \'post\', parameters:\'?image=\' + image_id, onsuccess:$(\'ansel_preview\').show()});}'
            . '</script>'
            . '<table class="linedRow" cellspacing="0" style="width:100%"><thead><tr class="item nowrap"><th class="item leftAlign">' . _("Date") . '</th><th class="item leftAlign">' . _("Image") . '</th><th class="item leftAlign">' . _("Subject") . '</th><th class="item leftAlign">' . _("By") . '</th></tr></thead><tbody>';

        foreach ($results as $comment) {
            try {
                $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($comment['image_id']);
                $url = Ansel::getUrlFor('view',
                                        array('view' => 'Image',
                                              'gallery' => abs($image->gallery),
                                              'image' => $comment['image_id']),
                                        true);
                $caption = substr($image->caption, 0, 30);
                if (strlen($image->caption) > 30) {
                    $caption .= '...';
                }
                $html .= '<tr><td>'
                    . strftime('%x', $comment['message_timestamp'])
                    . '</td><td class="nowrap">'
                    . $url->link(array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");',
                                       'onmouseover' => 'previewImage(event, ' . $comment['image_id'] . ');'))
                    . ($image->caption == '' ? $image->filename : $caption)
                    . '</a></td><td class="nowrap">'
                    . $comment['message_subject'] . '</td><td class="nowrap">'
                    . $comment['message_author'] . '</td></tr>';
            } catch (Horde_Exception $e) {}
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     *
     * @return Ansel_Gallery
     * @throws Horde_Exception_NotFound
     * @throws Horde_Exception_PermissionDenied
     */
    private function _getGallery()
    {
        // Make sure we haven't already selected a gallery.
        if ($this->_gallery instanceof Ansel_Gallery) {
            return $this->_gallery;
        }

        // Get the gallery object and cache it.
        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($this->_params['gallery']);
        } else {
            $this->_gallery =$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getRandomGallery();
        }

        if (empty($this->_gallery)) {
            throw new Horde_Exception_NotFound(_("Gallery does not exist."));
        } elseif (!$this->_gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied(_("Access denied viewing this gallery."));
        }

        // Return a reference to the gallery.
        return $this->_gallery;
    }

    /**
     * Numerically sorts an associative array by a specific index.
     *
     * Designed to ease sorting stories by a timestamp when combining seperate
     * channels into one array.
     *
     * @param array  $sortarray  The array to sort.
     * @param string $index      The index that contains the numerical value
     *                           to sort by.
     */
    private function _asortbyindex ($sortarray, $index) {
        $lastindex = count ($sortarray) - 1;
        for ($subindex = 0; $subindex < $lastindex; $subindex++) {
            $lastiteration = $lastindex - $subindex;
            for ($iteration = 0; $iteration < $lastiteration; $iteration++) {
                $nextchar = 0;
                if ($sortarray[$iteration][$index] < $sortarray[$iteration + 1][$index]) {
                    $temp = $sortarray[$iteration];
                    $sortarray[$iteration] = $sortarray[$iteration + 1];
                    $sortarray[$iteration + 1] = $temp;
                }
            }
        }

        return ($sortarray);
    }

}
