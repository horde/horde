<?php
/**
 *
 */
class Kronolith_Imple_TagActions extends Kronolith_Imple
{
    public function attach()
    {
        // TODO: HACK! attach is called in the contructor which means that even
        // if we are only handling the request, we try to attach().
        if (count($this->_params) == 0) {
            return;
        }
        Horde::addScriptFile('tagactions.js');
        $dom_id = $this->_params['triggerId'];
        $action = $this->_params['action'];
        $content_id = $this->_params['resource'];
        $content_type = $this->_params['type'];
        $tag_id = $this->_params['tagId'];
        $endpoint = Horde::url('imple.php', true);

        if ($action == 'add') {
            $js = "Event.observe('" . $dom_id . "', 'click', function(event) {addTag(); Event.stop(event)});";
        } elseif ($action == 'delete') {
            $js = "Event.observe('" . $dom_id . "', 'click', function(event) {removeTag('" . $content_id . "', '" . $content_type . "', " . $tag_id . ", '" . $endpoint . "'); Event.stop(event)});";
        }
        Kronolith::addInlineScript($js, 'window');
    }

    public function handle($args)
    {
        global $ansel_storage;

        $request = $args['action'];
        $content = array('id' => $args['resource'], 'type' => $args['type']);
        $tags = $args['tags'];

        /* Get the resource owner */
        /* Perms checks */

        switch ($request) {
        case 'add':
            break;
        case 'remove':
            $tagger = new Kronolith_Tagger();
            //$tagger->


            break;
        }
    }

    private function _getTagHtml($tags, $hasEdit)
    {
    }

}
