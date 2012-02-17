<?php
/**
 * Ansel_Ajax_Imple_TagActions:: class for handling adding/deleting tags via
 * Ajax calls.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_TagActions extends Horde_Core_Ajax_Imple
{
    public function attach()
    {
        // Include the js
        Horde::addScriptFile('tagactions.js');

        $url = $this->_getUrl('TagActions', 'ansel', array('gallery' => $this->_params['gallery'],
                                                           'image' =>  (isset($this->_params['image']) ? $this->_params['image'] : 0)));
        $params = array('url' => (string)$url,
                        'gallery' => $this->_params['gallery'],
                        'image' => (isset($this->_params['image']) ? $this->_params['image'] : 0),
                        'bindTo' => $this->_params['bindTo'],
                        'input' => 'tags');
        $js = array();
        $js[] = "Ansel.ajax['tagActions'] = " . Horde_Serialize::serialize($params, Horde_Serialize::JSON) . ";";
        $js[] = "Event.observe(Ansel.ajax.tagActions.bindTo.add, 'click', function(event) {addTag(); Event.stop(event)});";

        Horde::addInlineScript($js, 'dom');
    }

    public function handle($args, $post)
    {
        $action = $args['action'];
        $tags = $post['tags'];
        if (empty($action) || empty($tags)) {
            return array('response' => '0');
        }

        $gallery = $args['gallery'];
        $image = isset($args['image']) ? $args['image'] : null;
        if ($image) {
            $id = $image;
            $type = 'image';
        } else {
            $id = $gallery;
            $type = 'gallery';
        }

        if (!is_numeric($id)) {
            return array('response' => 0,
                         'message' => sprintf(_("Invalid input %s"), htmlspecialchars($id)));
        }

        /* Get the resource owner */
        if ($type == 'gallery') {
            $resource = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($id);
            $parent = $resource;
        } else {
            $resource = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($id);
            $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($resource->gallery);
        }

        switch ($action) {
        case 'add':
            if (!empty($tags)) {
                $tags = rawurldecode($post['tags']);
                $tags = explode(',', $tags);
                $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($id, $tags, $GLOBALS['registry']->getAuth(), $type);

                /* Get the tags again since we need the newly added tag_ids */
                $newTags = $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTags($id, $type);
                if (count($newTags)) {
                    $newTags = $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTagInfo(array_keys($newTags));
                }

                return array('response' => 1,
                             'message' => $this->_getTagHtml($newTags,
                                                             $parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)));
            }
            break;

        case 'remove':
            $GLOBALS['injector']->getInstance('Ansel_Tagger')->untag($resource->id, (int)$tags, $type);
            $existingTags = $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTags($resource->id, $type);
            if (count($existingTags)) {
                $newTags = $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTagInfo(array_keys($existingTags));
            } else {
                $newTags = array();
            }
            return array('response' => 1,
                         'message' => $this->_getTagHtml($newTags,
                                                         $parent->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)));
            break;
        }

    }

    private function _getTagHtml($tags, $hasEdit)
    {
        global $registry;
        $links = Ansel::getTagLinks($tags, 'add');
        $html = '<ul>';
        foreach ($tags as $taginfo) {
            $tag_id = $taginfo['tag_id'];
            $html .= '<li>' . $links[$tag_id]->link(array('title' => sprintf(ngettext("%d photo", "%d photos", $taginfo['count']), $taginfo['count']))) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

}
