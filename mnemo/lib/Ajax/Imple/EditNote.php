<?php
/**
 * Mnemo_Ajax_Imple_EditNote:: class for performing Ajax note editing.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Mnemo
 */
class Mnemo_Ajax_Imple_EditNote extends Horde_Core_Ajax_Imple
{
    public function __construct($params)
    {
        /* Set up some defaults */
        if (empty($params['rows'])) {
            $params['rows'] = 2;
        }
        if (empty($params['cols'])) {
            $params['cols'] = 20;
        }
        parent::__construct($params);
    }

    public function attach()
    {
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('inplaceeditor.js', 'horde');

        $params = array('input' => 'value',
                        'id' => $this->_params['id']);

        $url = $this->_getUrl('EditNote', 'mnemo', $params);
        $loadTextUrl = $this->_getUrl('EditNote', 'mnemo', array_merge($params, array('action' => 'load')));
        $js = array();

        $js[] =
            "new InPlaceEditor('" . $this->_params['domid'] . "', '" . $url . "', {"
            . "   callback: function(form, value) {"
            . "       return 'value=' + encodeURIComponent(value);},"
            . "   loadTextURL: '". $loadTextUrl . "',"
            . "   rows: " . (int)$this->_params['rows'] . ", "
            . "   autoWidth: true,"
            . "   emptyText: '" . _("Click to add text...") . "',"
            . "   onComplete: function(ipe, opts) { ipe.checkEmpty() },"
            . "   cancelText: '" . _("Cancel") . "',"
            . "   okText: '" . _("Ok") . "',"
            . "   cancelClassName: ''"
            . "  });";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args, $post)
    {
        if ($GLOBALS['registry']->getAuth()) {
            /* Are we requesting the unformatted text? */
            if (!empty($args['action']) && $args['action'] == 'load') {
                $id = $args['id'];
                $storage = Mnemo_Driver::singleton();
                $memo = $storage->getByUID($id);
                return $memo['body'];
            }
            if (empty($args['input']) ||
                is_null($pref_value = Horde_Util::getPost($args['input'], null)) ||
                empty($args['id'])) {

                    return '';
            }

            $storage = Mnemo_Driver::singleton();
            $memo = $storage->getByUID($args['id']);
            $share = $GLOBALS['mnemo_shares']->getShare($memo['memolist_id']);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                throw new Horde_Exception_PermissionDenied(_("You do not have permission to edit this note."));
            }

            $storage->modify($memo['memo_id'], $memo['desc'], $pref_value);
            return $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter(
                $pref_value,
                'text2html',
                array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }
    }
}
