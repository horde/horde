<?php
/**
 * Ansel_Widget_Tags:: class to display a tags widget in the image and gallery
 * views.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Tags extends Ansel_Widget_Base
{
    protected $_resourceType;

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
        if ($this->_resourceType == 'image') {
            $image_id = $this->_view->resource->id;
        } else {
            $image_id = null;
        }

        /* Build the tag widget */
        $html = $this->_htmlBegin();
        try {
            $html .= '<div id="tags">' . $this->_getTagHTML() . '</div>';
        } catch (Ansel_Exception $e) {
            return $html . sprintf(_("There was an error fetching tags: %s"), $e->getMessage()) . $this->_htmlEnd();
        }
        if ($this->_view->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            Horde::startBuffer();
            /* Attach the Ajax action */
            $GLOBALS['injector']->getInstance('Horde_Ajax_Imple_Factory')->getImple(array('ansel', 'TagActions'), array(
                'bindTo' => array('add' => 'tagbutton'),
                'gallery' => $this->_view->gallery->id,
                'image' => $image_id
            ));
            $html .= Horde::endBuffer();

            $actionUrl = Horde::url('image.php')->add(
                array('image' => $this->_view->resource->id,
                      'gallery' => $this->_view->gallery->id));

            $html .= '<form name="tagform" action="' . $actionUrl . '" onsubmit="return !addTag();" method="post">';
            $html .= '<input id="addtag" name="addtag" type="text" size="15" /> <input name="tagbutton" id="tagbutton" class="button" value="' . _("Add") . '" type="submit" />';
            $html .= '</form>';
        }
        $html .= $this->_htmlEnd();

        return $html;
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
        $html = '<ul>';
        foreach ($tags as $taginfo) {
            $tag_id = $taginfo['tag_id'];
            $html .= '<li>' . $links[$tag_id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos", $taginfo['count']), $taginfo['count']))) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

}
