<?php
class Trean_SaveBookmark_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $id = Horde_Util::getFormData('bookmark');
        $gateway = $this->getInjector()->getInstance('Trean_Bookmarks');
        $notification = $this->getInjector()->getInstance('Horde_Notification');

        try {
            $bookmark = $gateway->getBookmark($id);

            $old_url = $bookmark->url;
            $bookmark->url = Horde_Util::getFormData('bookmark_url');
            $bookmark->title = Horde_Util::getFormData('bookmark_title');
            $bookmark->description = Horde_Util::getFormData('bookmark_description');
            $bookmark->tags = Horde_Util::getFormData('bookmark_tags');

            if ($old_url != $bookmark->url) {
                $bookmark->http_status = '';
            }

            $bookmark->save();
            $result = array('data' => 'saved');
        } catch (Trean_Exception $e) {
            $notification->push(sprintf(_("There was an error saving the bookmark: %s"), $e->getMessage()), 'horde.error');
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
