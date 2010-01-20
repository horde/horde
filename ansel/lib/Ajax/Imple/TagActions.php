<?php
/**
 * Ansel_Ajax_Imple_TagActions:: class for handling adding/deleting tags via
 * Ajax calls.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_TagActions extends Horde_Ajax_Imple_Base
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
            $resource = $GLOBALS['ansel_storage']->getGallery($id);
            $parent = $resource;
        } else {
            $resource = $GLOBALS['ansel_storage']->getImage($id);
            $parent = $GLOBALS['ansel_storage']->getGallery($resource->gallery);
        }

        switch ($action) {
        case 'add':
            if (!empty($tags)) {
                $tags = explode(',', $tags);
                $tags = array_map('rawurldecode', $tags);

                /* Get current tags so we don't overwrite them */
                $etags = Ansel_Tags::readTags($id, $type);
                $tags = array_keys(array_flip(array_merge($tags, array_values($etags))));
                $resource->setTags($tags);

                /* Get the tags again since we need the newly added tag_ids */
                $newTags = $resource->getTags();
                if (count($newTags)) {
                    $newTags = Ansel_Tags::listTagInfo(array_keys($newTags));
                }

                return array('response' => 1,
                             'message' => $this->_getTagHtml($newTags,
                                                             $parent->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)));
            }
            break;

        case 'remove':
            $existingTags = $resource->getTags();
            unset($existingTags[$tags]);
            $resource->setTags($existingTags);
            if (count($existingTags)) {
                $newTags = Ansel_Tags::listTagInfo(array_keys($existingTags));
            } else {
                $newTags = array();
            }

            return array('response' => 1,
                         'message' => $this->_getTagHtml($newTags,
                                                         $parent->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)));
            break;
        }

    }

    private function _getTagHtml($tags, $hasEdit)
    {
        global $registry;
        $links = Ansel_Tags::getTagLinks($tags, 'add');
        $html = '<ul>';
        foreach ($tags as $tag_id => $taginfo) {
            $html .= '<li>' . Horde::link($links[$tag_id], sprintf(ngettext("%d photo", "%d photos", $taginfo['total']), $taginfo['total'])) . htmlspecialchars($taginfo['tag_name']) . '</a>' . ($hasEdit ? '<a href="#" onclick="removeTag(' . $tag_id . ');">' . Horde::img('delete-small.png', _("Remove Tag"), '', $registry->getImageDir('horde')) . '</a>' : '') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

}
