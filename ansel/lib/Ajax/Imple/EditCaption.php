<?php
/**
 * Imple for performing AJAX setting of image captions.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditCaption extends Horde_Core_Ajax_Imple_InPlaceEditor
{
    /**
     */
    protected function _handleEdit(Horde_Variables $vars)
    {
        $as = $GLOBALS['injector']->getInstance('Ansel_Storage');
        $image = $as->getImage($vars->id);

        /* Are we requesting the unformatted text? */
        if ($vars->load) {
            return $image->caption;
        }

        $g = $as->getGallery($image->gallery);
        if ($g->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $image->caption = $vars->input;
            try {
                $result = $image->save();
            } catch (Ansel_Exception $e) {
                return '';
            }
        }

        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(
            $image->caption,
            'text2html',
            array('parselevel' => Horde_Text_Filter_Text2html::MICRO
        ));
    }

}
