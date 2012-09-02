<?php
/**
 *
 */
class Nag_View_Helper_List extends Horde_View_Helper_Base
{

    public function headerWidget($baseurl, $sortdir, $sortby, $by, $content)
    {
        return Horde::widget(array(
            'url' => $baseurl->add(array(
                'sortby' => $by,
                'sortdir' => $sortby == $by ? 1 - $sortdir : $sortdir)),
            'class' => 'sortlink',
            'title' => $content
        ))
        . '&nbsp;';
    }

}