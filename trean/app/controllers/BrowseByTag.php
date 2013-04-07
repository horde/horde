<?php
class Trean_BrowseByTag_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $path = $request->getPath();
        $pathParts = explode('/', $path);
        $tag = array_pop($pathParts);

        $tagBrowser = new Trean_TagBrowser($this->getInjector()->getInstance('Trean_Tagger'), $tag);
        $view = new Trean_View_BookmarkList(null, $tagBrowser);

        $page_output = $this->getInjector()->getInstance('Horde_PageOutput');
        $notification = $this->getInjector()->getInstance('Horde_Notification');

        if ($GLOBALS['conf']['content_index']['enabled']) {
            $topbar = $this->getInjector()->getInstance('Horde_View_Topbar');
            $topbar->search = true;
            $topbar->searchAction = Horde::url('search.php');
        }

        Trean::addFeedLink();
        $title = sprintf(_("tagged %s"), $tag);
        $page_output->header(array(
            'title' => $title
        ));
        $notification->notify(array('listeners' => 'status'));
        echo $view->render($title);
        $page_output->footer();
    }
}
