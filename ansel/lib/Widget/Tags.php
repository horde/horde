<?php
 /**
  * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
  *
  * See the enclosed file COPYING for license information (GPL). If you
  * did not receive this file, see http://www.horde.org/licenses/gpl.
  *
  * @author Michael J Rubinsky <mrubinsk@horde.org>
  * @package Ansel
  */
/**
 * Ansel_Widget_Tags:: class to display a tags widget in the image and gallery
 * views.
 *
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 *  @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
*/
class Ansel_Widget_Tags extends Ansel_Widget_Base
{
    /**
     * The type of resource the widget is connected to.
     * i.e., image or gallery
     *
     * @var string
     */
    protected $_resourceType;

    /**
     *
     * @var array $params  The parameters:
     *   - view:  The view we are attaching to (image, gallery).
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_resourceType = $params['view'];
        $this->_title = _("Tags");
    }

    /**
     * Build the HTML for this widget
     *
     * @return string  The HTML representing this widget.
     */
    public function html()
    {
        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/widgets');
        $view->title = _("Tags");
        $view->background = $this->_style->background;

        $image_id = ($this->_resourceType == 'image')
            ? $this->_view->resource->id
            : null;

        try {
            $view->tag_html = $this->_getTagHTML();
        } catch (Ansel_Exception $e) {
            $view->error_text = sprintf(_("There was an error fetching tags: %s"), $e->getMessage());
        }

        if ($this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $view->have_edit = true;
            $GLOBALS['page_output']->addScriptFile('widgets/tagactions.js');
            $GLOBALS['page_output']->addInlineJsVars(array(
               'AnselTagActions.gallery' => $this->_view->gallery->id,
               'AnselTagActions.image' => $image_id
            ));
        }

        return $view->render('tags');
    }


    /**
     * Helper function to build the list of tags
     *
     * @return string  The HTML representing the tag list.
     */
    protected function _getTagHTML()
    {
        global $registry;

        /* Clear the tag cache? */
        if (Horde_Util::getFormData('havesearch', 0) == 0) {
            Ansel_Search_Tag::clearSearch();
        }

        $tagger = $GLOBALS['injector']->getInstance('Ansel_Tagger');
        $hasEdit = $this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(),
                                                        Horde_Perms::EDIT);
        $owner = $this->_view->gallery->get('owner');
        $tags = $tagger->getTags((int)$this->_view->resource->id, $this->_resourceType);

        if (count($tags)) {
            $tags = $tagger->getTagInfo(array_keys($tags), 500, $this->_resourceType);
        }

        $links = Ansel::getTagLinks($tags, 'add', $owner);
        $html = '<ul class="horde-tags">';
        foreach ($tags as $taginfo) {
            $tag_id = $taginfo['tag_id'];
            $html .= '<li>' . $links[$tag_id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos", $taginfo['count']), $taginfo['count']))) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="#" onclick="AnselTagActions.remove(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

}
