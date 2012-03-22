<?php
class Trean_DeleteBookmark_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $id = Horde_Util::getFormData('bookmark');
        $gateway = $this->getInjector()->getInstance('Trean_Bookmarks');
        $notification = $this->getInjector()->getInstance('Horde_Notification');

        try {
            $bookmark = $gateway->getBookmark($id);
            $gateway->removeBookmark($bookmark);
            $notification->push(_("Deleted bookmark: ") . $bookmark->title, 'horde.success');
            $result = array('data' => 'deleted');
        } catch (Trean_Exception $e) {
            $notification->push(sprintf(_("There was a problem deleting the bookmark: %s"), $e->getMessage()), 'horde.error');
            $result = array('error' => $e->getMessage());
        }

        if (Horde_Util::getFormData('format') == 'json') {
            $response->setContentType('application/json');
            $response->setBody(json_encode($result));
        } else {
            $response->setRedirectUrl(Horde_Util::getFormData('url', Horde::url('browse.php', true)));
        }
    }
}
