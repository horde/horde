<?php

$block_name = _("My Galleries");

/**
 * Display summary information on top level galleries.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_my_galleries extends Horde_Block
{
    /**
     *
     * @var string
     */
    protected $_app = 'ansel';

    /**
     *
     * @return array
     */
    protected function _params()
    {
        $params = array('limit' => array(
                            'name' => _("Maximum number of galleries"),
                            'type' => 'int',
                            'default' => 0));
        return $params;
    }

    /**
     *
     * @return string
     */
    protected function _title()
    {
        return Ansel::getUrlFor('view',
                                array('groupby' => 'owner',
                                      'owner' => $GLOBALS['registry']->getAuth(),
                                      'view' => 'List'))->link()
            . _("My Galleries") . '</a>';
    }

    /**
     *
     * @return string
     */
    protected function _content()
    {
        Horde::addScriptFile('tooltips.js', 'horde');

        /* Get the top level galleries */
        try {
            $galleries = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getScope()->listGalleries(array('perm' => Horde_Perms::EDIT,
                                                  'filter' => $GLOBALS['registry']->getAuth(),
                                                  'allLevels' => false,
                                                  'count' => empty($this->_params['limit']) ? 0 : $this->_params['limit'],
                                                  'sort_by' => 'last_modified',
                                                  'direction' => Ansel::SORT_DESCENDING));

        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }

        $preview_url = Horde::applicationUrl('preview.php');
        $header = array(_("Gallery Name"), _("Last Modified"), _("Photo Count"));
        $html = <<<HEADER
<div id="ansel_preview"></div>
<script type="text/javascript">
function previewImageMg(e, image_id)
{
    $('ansel_preview').style.left = Event.pointerX(e) + 'px';
    $('ansel_preview').style.top = Event.pointerY(e) + 'px';
    new Ajax.Updater({success: 'ansel_preview'}, '$preview_url', {method: 'post', parameters: '?image=' + image_id, onsuccess: $('ansel_preview').show()});
}
</script>
<table class="linedRow" cellspacing="0" style="width:100%">
 <thead><tr class="item nowrap">
  <th class="item leftAlign">$header[0]</th>
  <th class="item leftAlign">$header[1]</th>
  <th class="item leftAlign">$header[2]</th>
 </tr></thead>
 <tbody>
HEADER;

        foreach ($galleries as $gallery) {
            $style = $gallery->getStyle();
            $url = Ansel::getUrlFor('view', array('view' => 'Gallery',
                                                  'slug' => $gallery->get('slug'),
                                                  'gallery' => $gallery->id),
                                    true);
            $html .= '<tr><td>'
                . $url->link(array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");',
                                   'onmouseover' => 'previewImageMg(event, ' . $gallery->getKeyImage('ansel_default') . ');'))
                . @htmlspecialchars($gallery->get('name'), ENT_COMPAT, $GLOBALS['registry']->getCharset()) . '</a></td><td>'
                . strftime($GLOBALS['prefs']->getValue('date_format'), $gallery->get('last_modified'))
                . '</td><td>' . (int)$gallery->countImages(true) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

}
