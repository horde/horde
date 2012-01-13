<?php
/**
 * Face recognition class
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Faces
{
    /**
     * Delete faces from VFS and DB storage.
     *
     * @TODO: Move SQL queries to Ansel_Storage::
     *
     * @param Ansel_Image $image Image object to delete faces for
     * @param integer $face      Face id. If empty, all faces for $image are
     *                           removed
     *
     * @throws Ansel_Exception
     */
    static public function delete(Ansel_Image $image, $face = null)
    {
        if ($image->facesCount == 0) {
            return true;
        }

        $path = self::getVFSPath($image->id) . '/faces';
        $ext = self::getExtension();

        if ($face === null) {
            $sql = 'SELECT face_id FROM ansel_faces WHERE image_id = ' . $image->id;
            try {
                $faces = $GLOBALS['ansel_db']->selectValues($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Ansel_Exception($e);
            }
            try {
                foreach ($faces as $id) {
                    $GLOBALS['injector']
                        ->getInstance('Horde_Core_Factory_Vfs')
                        ->create('images')
                        ->deleteFile($path, $id . $ext);
                }
            } catch (Horde_Vfs_Exception $e) {}
            try {
                $GLOBALS['ansel_db']->delete('DELETE FROM ansel_faces WHERE '
                    . 'image_id = ' . $image->id);
                $GLOBALS['ansel_db']->update('UPDATE ansel_images SET '
                    . 'image_faces = 0 WHERE image_id = ' . $image->id
                    . ' AND image_faces > 0 ');
            } catch (Horde_Db_Exception $e) {
                throw new Ansel_Exception($e);
            }
            $gallery = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($image->gallery);
            $gallery->set('faces', $gallery->get('faces') - count($faces), true);
        } else {
            try {
                $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Vfs')
                    ->create('images')
                    ->deleteFile($path, (int)$face . $ext);
            } catch (Horde_Vfs_Exception $e) {}
            try {
                $GLOBALS['ansel_db']->delete('DELETE FROM ansel_faces WHERE'
                    . ' face_id = ' . (int)$face);
                $GLOBALS['ansel_db']->update('UPDATE ansel_images SET '
                    . 'image_faces = image_faces - 1 WHERE image_id = '
                    . $image->id . ' AND image_faces > 0 ');
            } catch (Horde_Db_Exception $e) {
                throw new Ansel_Exception($e);
            }
            $gallery = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($image->gallery);
            $gallery->set('faces', $gallery->get('faces') - 1, true);
        }
    }

    /**
     * Get image path
     *
     * @param integer $image Image ID to get
     */
    static public function getVFSPath($image)
    {
        return '.horde/ansel/' . substr(str_pad($image, 2, 0, STR_PAD_LEFT), -2) . '/';
    }

    /**
     * Get filename extension
     *
     */
    static public function getExtension()
    {
        if ($GLOBALS['conf']['image']['type'] == 'jpeg') {
            return '.jpg';
        } else {
            return '.png';
        }
    }

    /**
     * Get face link. Points to the image that this face is from.
     *
     * @param array $face  Face data
     *
     * @return string  The url for the image this face belongs to.
     */
    static public function getLink(array $face)
    {
        return Ansel::getUrlFor(
            'view',
            array('view' => 'Image',
                  'gallery' => $face['gallery_id'],
                  'image' => $face['image_id']));
    }

    /**
     * Generate HTML for a face's tile
     *
     * @param integer $face  The face id.
     *
     * @return string  The generated HTML
     */
    static public function getFaceTile($face)
    {
        $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
        if (!is_array($face)) {
            $face = $faces->getFaceById($face, true);
        }
        $face_id = $face['face_id'];

        // The HTML to display the face image.
        $imghtml = sprintf("<img src=\"%s\" class=\"bordered-facethumb\" id=\"%s\" alt=\"%s\" />",
            $faces->getFaceUrl($face['image_id'], $face_id),
            'facethumb' . $face_id,
            htmlspecialchars($face['face_name']));

        $img_view_url = Ansel::getUrlFor('view',
            array('gallery' => $face['gallery_id'],
                  'view' => 'Image',
                  'image'=> $face['image_id'],
                  'havesearch' => false));

        // Build the actual html
        $html = '<div id="face' . $face_id . '"><table><tr><td>' . $img_view_url->link() . $imghtml . '</a></td><td>';
        if (!empty($face['face_name'])) {
            $html .= Horde::url('faces/face.php')->add('face', $face['face_id'])->link() . $face['face_name'] . '</a><br />';
        }

        // Display the face name or a link to claim the face.
        if (empty($face['face_name']) && $GLOBALS['conf']['report_content']['driver']) {
            $html .= Horde::url('faces/claim.php')->add('face', $face_id)->link(array('title' => _("Do you know someone in this photo?"))) . _("Claim") . '</a>';
        }

        // Link for searching for similar faces.
        if (Horde_Util::loadExtension('libpuzzle') !== false) {
            $html .= Horde::url('faces/search/image_search.php')->add('face_id', $face_id)->link() . _("Find similar") . '</a>';
        }
        $html .= '</div></td></tr></table>';

        return $html;
    }

}
