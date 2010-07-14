<?php
/**
 * Ansel_Ajax_Imple_EditFaces:: class for performing Ajax discovery and editing
 * of image faces
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Duck <duck@obala.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditFaces extends Horde_Core_Ajax_Imple
{
    /**
     * Attach these actions to the view
     *
     */
    public function attach()
    {
        Horde::addScriptFile('editfaces.js');

        $url = $this->_getUrl('EditFaces', 'ansel', array('url' => rawurlencode($this->_params['selfUrl'])));
        $js = array();
        $js[] = "Ansel.ajax['editFaces'] = {'url':'" . $url . "', text: {loading:'" . _("Loading...") . "'}};";
        $js[] = "Event.observe('" . $this->_params['domid'] . "', 'click', function(event) {Ansel.doFaceEdit(" . $this->_params['image_id'] . ");Event.stop(event)});";

        Horde::addInlineScript($js, 'dom');
    }

    function handle($args, $post)
    {
        if ($GLOBALS['registry']->getAuth()) {
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
                    $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($image_id);
                    $image->createView('screen');
                    $result = $faces->getFromPicture($image_id, $autocreate);
                }
                if (!empty($result)) {
                    $customurl = Horde::applicationUrl('faces/custom.php');
                    $url = (!empty($args['url']) ? urldecode($args['url']) : '');
                    Horde::startBuffer();
                    include ANSEL_TEMPLATES . '/faces/image.inc';
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
                $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($image_id);
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($image->gallery);
                if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                    throw new Horde_Exception('Access denied editing the photo.');
                }

                Ansel_Faces::delete($image, $face_id);
                break;

            case 'setname':
                // setname - sets the name of a single image.
                $face_id = (int)$post['face'];
                if (!$face_id) {
                    return array('response' => 0);
                }

                $name = $post['facename'];
                $image = &$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($image_id);
                $gallery = &$GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($image->gallery);
                if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
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
