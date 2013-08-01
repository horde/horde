<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class Trean_Ajax_Imple_TopTags extends Horde_Core_Ajax_Imple
{

    /**
     * Attach the object to a javascript event.
     *
     * @param boolean $init  Is this the first time this imple has been
     *                       initialized?
     *
     * @return mixed  An array of javascript parameters. If false, the imple
     *                handler will ignore this instance (calling code will be
     *                responsible for calling imple endpoint).
     */
    protected function _attach($init)
    {
        if ($init) {
            $this->_jsOnComplete('TreanTopTags.loadTags(e.memo)');
            $GLOBALS['page_output']->addScriptFile('toptags.js');
            $GLOBALS['page_output']->addScriptFile('scriptaculous/effects.js', 'horde');
        }

        return array('imple' => 'TopTags');
    }

    /**
     * Imple handler.
     *
     * @param Horde_Variables $vars  A variables object.
     *
     * @return stdClass  The top 10 most popular tags for the current user.
     */
    protected function _handle(Horde_Variables $vars)
    {
        $tagger = new Trean_Tagger();
        $result = new stdClass();
        $result->tags = array();
        $tags = $tagger->getCloud(
            $GLOBALS['registry']->getAuth(), 10, true);
        foreach ($tags as $tag) {
            $results->tags[] = $tag['tag_name'];
        }

        return $results;
    }

}
