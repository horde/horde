<?php
/**
 * Imple for performing Ajax note editing.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Mnemo
 */
class Mnemo_Ajax_Imple_EditNote extends Horde_Core_Ajax_Imple_InPlaceEditor
{
    /**
     */
    protected function _handleEdit(Horde_Variables $vars)
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        $memo = $storage->getByUID($vars->id);

        /* Are we requesting the unformatted text? */
        if ($vars->action == 'load') {
            return $memo['body'];
        }

        $share = $GLOBALS['mnemo_shares']->getShare($memo['memolist_id']);
        if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(_("You do not have permission to edit this note."));
        }

        $storage->modify($memo['memo_id'], $memo['desc'], $vars->{$vars->input});

        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(
            $vars->{$vars->input},
            'text2html',
            array('parselevel' => Horde_Text_Filter_Text2html::MICRO)
        );
    }

}
