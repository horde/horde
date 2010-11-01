<?php
/**
 * Face recognition class
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
     * @param integer $face  Face id
     * @static
     */
    static public function delete($image, $face = null)
    {
        if ($image->facesCount == 0) {
            return true;
        }

        $path = self::getVFSPath($image->id) . '/faces';
        $ext = self::getExtension();

        if ($face === null) {
            $sql = 'SELECT face_id FROM ansel_faces WHERE image_id = ' . $image->id;
            $face = $GLOBALS['ansel_db']->queryCol($sql);
            if ($face instanceof PEAR_Error) {
                throw new Horde_Exception_Prior($face);
            }
            try {
                foreach ($face as $id) {
                    $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->deleteFile($path, $id . $ext);
                }
            } catch (VFS_Exception $e) {}
            $GLOBALS['ansel_db']->exec('DELETE FROM ansel_faces WHERE image_id = ' . $image->id);
            $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = 0 WHERE image_id = ' . $image->id . ' AND image_faces > 0 ');
            $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces - ' . count($face) . ' WHERE gallery_id = ' . $image->gallery . ' AND attribute_faces > 0 ');
        } else {
            try {
                $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->deleteFile($path, (int)$face . $ext);
            } catch (VFS_Exception $e) {}
            $GLOBALS['ansel_db']->exec('DELETE FROM ansel_faces WHERE face_id = ' . (int)$face);
            $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = image_faces - 1 WHERE image_id = ' . $image->id . ' AND image_faces > 0 ');
            $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces - 1 WHERE gallery_id = ' . $image->gallery . ' AND attribute_faces > 0 ');
        }

        return true;
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
     * @static
     * @return string  The url for the image this face belongs to.
     */
    static public function getLink($face)
    {
        return Ansel::getUrlFor('view',
                                array('view' => 'Image',
                                      'gallery' => $face['gallery_id'],
                                      'image' => $face['image_id']));
    }

    /**
     * Output HTML for a face's tile
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
