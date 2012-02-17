<?php
/**
 * Display summary information on top level galleries.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 */
class Ansel_Block_MyGalleries extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        $this->_name = _("My Galleries");
   }

    /**
     */
    protected function _params()
    {
        return array(
            'limit' => array(
                'name' => _("Maximum number of galleries"),
                'type' => 'int',
                'default' => 0
            )
        );
    }

    /**
     */
    protected function _title()
    {
        return Ansel::getUrlFor(
          'view',
          array(
            'groupby' => 'owner',
            'owner' => $GLOBALS['registry']->getAuth(),
            'view' => 'List'))
          ->link() . _("My Galleries") . '</a>';
    }

    /**
     */
    protected function _content()
    {
        Horde::addScriptFile('block.js');

        /* Get the top level galleries */
        try {
            $galleries = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->listGalleries(
                  array(
                    'perm' => Horde_Perms::EDIT,
                    'attributes' => $GLOBALS['registry']->getAuth(),
                    'all_levels' => false,
                    'count' => empty($this->_params['limit']) ? 0 : $this->_params['limit'],
                    'sort_by' => 'last_modified',
                    'direction' => Ansel::SORT_DESCENDING));
        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }

        $header = array(_("Gallery Name"), _("Last Modified"), _("Photo Count"));
        $html = <<<HEADER
<table class="linedRow" cellspacing="0" style="width:100%">
 <thead><tr class="item nowrap">
  <th class="item leftAlign">$header[0]</th>
  <th class="item leftAlign">$header[1]</th>
  <th class="item leftAlign">$header[2]</th>
 </tr></thead>
 <tbody>
HEADER;

        foreach ($galleries as $gallery) {
            $url = Ansel::getUrlFor(
              'view',
              array(
                  'view' => 'Gallery',
                  'slug' => $gallery->get('slug'),
                  'gallery' => $gallery->id),
              true);

            $html .= '<tr><td>'
                . $url->link(array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");',
                                   'onmouseover' => 'Ansel.previewImage(event, ' . $gallery->getKeyImage(Ansel::getStyleDefinition('ansel_default')) . ');'))
                . htmlspecialchars($gallery->get('name')) . '</a></td><td>'
                . strftime($GLOBALS['prefs']->getValue('date_format'), $gallery->get('last_modified'))
                . '</td><td>' . (int)$gallery->countImages(true) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

}
