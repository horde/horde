<?php
/**
 * Ansel_Ajax_Imple_EditGalleryFaces:: class for performing Ajax discovery of
 * an entire gallery's images.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditGalleryFaces extends Horde_Ajax_Imple_Base
{
    /**
     * Attach these actions to the view
     *
     */
    public function attach()
    {
        Horde::addScriptFile('editfaces.js');
        $url = $this->_getUrl('EditFaces', 'ansel');
        $js = array();
        $js[] = "Ansel.ajax['editFaces'] = {'url':'" . $url . "', text: {loading:'" . _("Loading...") . "'}};";
        $image_id = $this->_params['image_id'];
        /* Start by getting the faces */
        $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
        $name = '';
        $autocreate = true;
        $result = $faces->getImageFacesData($image_id);
        if (empty($result)) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($this->_params['image_id']);
            $image->createView('screen');
            $result = $faces->getFromPicture($this->_params['image_id'], $autocreate);
        }
        if (!empty($result)) {
            $customurl = Horde::url('faces/custom.php');
            $url = (!empty($args['url']) ? urldecode($args['url']) : '');
            Horde::startBuffer();
            require_once ANSEL_TEMPLATES . '/faces/image.inc';
            return Horde::endBuffer();
        } else {
            return _("No faces found");
        }

        Horde::addInlineScript($js, 'dom');
    }

    function handle($args, $post)
    {
        if (Horde_Auth::getAuth()) {
            $action = $args['action'];
            $image_id = (int)$post['image'];
            $reload = empty($post['reload']) ? 0 : $post['reload'];

            if (empty($action)) {
                return array('response' => 0);
            }

            $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
            switch($action) {
            case 'process':
                // process - detects all faces in the image.
                $name = '';
                $autocreate = true;
                $result = $faces->getImageFacesData($image_id);
                // Attempt to get faces from the picture if we don't already have results,
                // or if we were asked to explicitly try again.
                if (($reload || empty($result))) {
                    $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image_id);
                    $image->createView('screen');
                    $result = $faces->getFromPicture($image_id, $autocreate);
                }
                if (!empty($result)) {
                    $imgdir = Horde_Themes::img(null, 'horde');
                    $customurl = Horde::url('faces/custom.php');
                    $url = (!empty($args['url']) ? urldecode($args['url']) : '');
                    Horde::startBuffer();
                    require_once ANSEL_TEMPLATES . '/faces/image.inc';
                    $html = Horde::endBuffer();
                    return array('response' => 1,
                                 'message' => $html);
                } else {
                    return array('response' => 1,
                                 'message' => _("No faces found"));
                }
                break;

            case 'delete':
                // delete - deletes a single face from an image.
                $face_id = (int)$post['face'];
                $image = &$GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image_id);
                $gallery = &$GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
                if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
                    throw new Horde_Exception('Access denied editing the photo.');
                }

                $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
                $faces->delete($image, $face_id);
                break;

            case 'setname':
                // setname - sets the name of a single image.
                $face_id = (int)$post['face'];
                if (!$face_id) {
                    return array('response' => 0);
                }

                $name = $post['facename'];
                $image = &$GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image_id);
                $gallery = &$GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
                if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
                    throw new Horde_Exception('You are not allowed to edit this photo');
                }

                $faces = $GLOBALS['injector']->getInstance('Ansel_Faces');
                $result = $faces->setName($face_id, $name);
                return array('response' => 1,
                             'message' => Ansel_Faces::getFaceTile($face_id));
                break;
            }
        }
    }

}
