<?php
/**
 * Imple for performing Ajax discovery and editing of image faces.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Duck <duck@obala.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditFaces extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected function _attach($init)
    {
        if ($init) {
            $this->_jsOnDoAction(
                '$("faces_widget_content").update(' .
                     Horde_Serialize::serialize(_("Loading..."), Horde_Serialize::JSON) .
                 ')'
            );
            $this->_jsOnComplete(
                '$("faces_widget_content").update(e.memo.response)'
            );

            $GLOBALS['page_output']->addScriptFile('editfaces.js');
        }

        return array(
            'image_id' => $this->_params['image_id']
        );
    }

    /**
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $prefs;

        $faces = $injector->getInstance('Ansel_Faces');
        $image_id = intval($vars->image_id);

        $results = $faces->getImageFacesData($image_id);
        // Attempt to get faces from the picture if we don't already have
        // results, or if we were asked to explicitly try again.
        if (empty($results)) {
            $image = $injector->getInstance('Ansel_Storage')->getImage($image_id);
            $image->createView('screen', null, ($prefs->getValue('watermark_auto') ?  $prefs->getValue('watermark_text', '') : ''));
            $results = $faces->getFromPicture($image_id, true);
        }

        if (empty($results)) {
            return new Horde_Core_Ajax_Response_Raw(_("No faces found"));
        }

        $url = Horde::url('faces/custom.php');
        Horde::startBuffer();
        include ANSEL_TEMPLATES . '/faces/image.inc';

        return new Horde_Core_Ajax_Response_Raw(Horde::endBuffer(), 'text/html');
    }

}
