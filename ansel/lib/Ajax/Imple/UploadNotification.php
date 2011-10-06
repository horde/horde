<?php
/**
 * Ansel_Ajax_Imple_UploadNotification:: class provides an API for sending
 * notification to various services after uploading images to a gallery.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Imple_UploadNotification extends Horde_Core_Ajax_Imple
{
    public function attach()
    {
        // noop
    }

    public function getUrl()
    {
        return $this->_getUrl('UploadNotification', 'ansel');
    }

    public function handle($args, $post)
    {
        $images = explode(',', $post['i']);
        $gallery = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getGallery($post['g']);

        switch ($post['s']) {
        case 'twitter':
            $url = Ansel::getUrlFor('view', array('gallery' => $gallery->id));
            try {
                $url = $GLOBALS['injector']
                    ->getInstance('Horde_Service_UrlShortener')
                    ->shorten($url);
            } catch (Horde_Service_UrlShortener_Exception $e) {
                Horde::logMessage($e, 'ERR');
                header('HTTP/1.1 500');
            }
            $text = sprintf(_("New images uploaded to %s. %s"), $gallery->get('name'), $url);
            $twitter = $this->_getTwitterObject();

            try {
                return $twitter->statuses->update($text);
            } catch (Horde_Service_Twitter_Exception $e) {
                Horde::logMessage($e, 'ERR');
                header('HTTP/1.1 500');
            }
        }
    }

    protected function _getTwitterObject()
    {
        $token = unserialize($GLOBALS['prefs']->getValue('twitter'));
        if (empty($token['key']) && empty($token['secret'])) {
            $pref_link = Horde::getServiceLink('prefs', 'horde')->add('group', 'twitter')->link();
            throw new Horde_Exception(sprintf(_("You have not properly connected your Twitter account with Horde. You should check your Twitter settings in your %s."), $pref_link . _("preferences") . '</a>'));
        }

        $twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
        $twitter->auth->setToken($auth_token);

        return $twitter;
    }

}
