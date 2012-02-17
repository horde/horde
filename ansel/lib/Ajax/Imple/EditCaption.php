<?php
/**
 * Ansel_Ajax_Imple_EditCaption:: class for performing Ajax setting of image
 * captions
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditCaption extends Horde_Core_Ajax_Imple
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

        $url = $this->_getUrl('EditCaption', 'ansel', $params);
        $loadTextUrl = $this->_getUrl('EditCaption', 'ansel', array_merge($params, array('action' => 'load')));
        $js = array();

        $js[] = "new InPlaceEditor('" . $this->_params['domid'] . "', '" . $url . "', {"
                . "   callback: function(form, value) {"
                . "       return 'value=' + encodeURIComponent(value);},"
                . "   loadTextURL: '". $loadTextUrl . "',"
                . "   rows:" . $this->_params['rows'] . ","
                . "   width:" . $this->_params['width'] . ","
                . "   emptyText: '" . _("Click to add caption...") . "',"
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
                $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($id);
                $caption = $image->caption;

                return $caption;
            }
            if (empty($args['input']) ||
                is_null($pref_value = Horde_Util::getPost($args['input'], null)) ||
                empty($args['id']) || !is_numeric($args['id'])) {

                    return '';
            }
            $id = $args['id'];
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($id);
            $g = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
            if ($g->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $image->caption = $pref_value;
                try {
                    $result = $image->save();
                } catch (Ansel_Exception $e) {
                    return '';
                }
            }
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(
                $image->caption,
                'text2html',
                array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }
    }

}
