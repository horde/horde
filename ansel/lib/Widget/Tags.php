<?php
 /**
  * Copyright 2008-2016 Horde LLC (http://www.horde.org/)
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
 * Copyright 2008-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
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

        // Handle any incoming tag changes from non-script browsers.
        $tags = Horde_Util::getFormData('addtag');
        if (!is_null($tags) && strlen($tags)) {
            $tagger = $GLOBALS['injector']->getInstance('Ansel_Tagger');
            $this->_view->resource->setTags($tags, $tagger->split($tags));
        } elseif (Horde_Util::getFormData('actionID') == 'deleteTags') {
            $tag = Horde_Util::getFormData('tag');
            $this->_view->resource->removeTag($tag);
        }
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
        $view->action_url = Horde::url('gallery.php');
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
               'AnselTagActions.image' => $image_id,
               'AnselTagActions.remove_image' => strval(Horde_Themes::img('delete-small.png'))
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

        // Clear the tag cache?
        if (Horde_Util::getFormData('havesearch', 0) == 0) {
            $tag_browser = new Ansel_TagBrowser($GLOBALS['injector']->getInstance('Ansel_Tagger'));
            $tag_browser->clearSearch();
        }

        $tagger = $GLOBALS['injector']->getInstance('Ansel_Tagger');
        $hasEdit = $this->_view->gallery->hasPermission(
            $GLOBALS['registry']->getAuth(),
            Horde_Perms::EDIT);
        $owner = $this->_view->gallery->get('owner');
        $tags = $tagger->getTags((int)$this->_view->resource->id, $this->_resourceType);

        if (count($tags)) {
            $tags = $tagger->getTagInfo(array_keys($tags), 500, $this->_resourceType);
        }
        if ($this->_resourceType != 'image') {
            $removeLink = Horde::url('gallery.php')->add(array(
                'actionID' => 'removeTags',
                'gallery' => $this->_view->gallery->id));
        } else {
            $removeLink = Horde::url('image.php')->add(array(
                'actionID' => 'removeTags',
                'gallery' => $this->_view->gallery->id,
                'image' => $this->_view->resource->id));
        }
        $links = Ansel::getTagLinks($tags, 'add', $owner);
        $html = '<ul class="horde-tags">';
        foreach ($tags as $taginfo) {
            $tag_id = $taginfo['tag_id'];
            $html .= '<li>' . $links[$tag_id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos", $taginfo['count']), $taginfo['count']))) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="' . strval($removeLink) . '" onclick="return AnselTagActions.remove(' . $tag_id . ');"> ' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

}
