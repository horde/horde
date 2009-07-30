<?php
/**
 * Ansel_Ajax_Imple_EditCaption:: class for performing Ajax setting of image
 * captions
 *
 * $Horde: ansel/lib/Ajax/Imple/EditCaption.php,v 1.2 2009/07/30 18:02:14 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_EditCaption extends Horde_Ajax_Imple_Base
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
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('controls.js', 'horde', true);
        Horde::addScriptFile('editcaption.js', 'ansel', true);

        $params = array('input' => 'value',
                        'id' => $this->_params['id']);

        $url = $this->_getUrl('EditCaption', 'ansel', $params);
        $loadTextUrl = $this->_getUrl('EditCaption', 'ansel', array_merge($params, array('action' => 'load')));
        $js = array();

        $js[] = "new Ajax.InPlaceEditor('" . $this->_params['domid'] . "', '" . $url . "', {"
                . "    callback: function(form, value) {"
                . "      return 'value=' + encodeURIComponent(value);},"
                . "   loadTextURL: '". $loadTextUrl . "',"
                . "   rows:" . $this->_params['rows'] . ","
                . "   cols:" . $this->_params['cols'] . ","
                . "   emptyText: '" . _("Click to add caption...") . "',"
                . "   onComplete: function(transport, element) {tileExit(this);}"
                . "  });";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args)
    {
        include_once dirname(__FILE__) . '/../../base.php';

        if (Horde_Auth::getAuth()) {
            /* Are we requesting the unformatted text? */
            if (!empty($args['action']) && $args['action'] == 'load') {
                $id = $args['id'];
                $image = $GLOBALS['ansel_storage']->getImage($id);
                $caption = $image->caption;

                return $caption;
            }
            if (empty($args['input']) ||
                is_null($pref_value = Horde_Util::getPost($args['input'], null)) ||
                empty($args['id']) || !is_numeric($args['id'])) {

                    return '';
            }
            $id = $args['id'];
            $image = $GLOBALS['ansel_storage']->getImage($id);
            $g = $GLOBALS['ansel_storage']->getGallery($image->gallery);
            if ($g->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
                $image->caption = $pref_value;
                $result = $image->save();
                if (is_a($result, 'PEAR_Error')) {
                    return '';
                }
            }
            $imageCaption = Horde_Text_Filter::filter(
                $image->caption, 'text2html',
                array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
            return $imageCaption;
        }
    }

}
