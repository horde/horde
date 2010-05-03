<?php
/**
 * Jonah application API.
 *
 * @package Kronolith
 */
class Jonah_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-cvs)';

    /**
     * Returns a list of available permissions.
     *
     * @return array
     */
    public function perms()
    {
        require_once dirname(__FILE__) . '/base.php';

        $news = Jonah_News::factory();
        $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);

        /* Loop through internal channels and add their ids to the
         * perms. */
        $perms = array();
        foreach ($channels as $channel) {
            $perms['tree']['jonah']['news']['internal_channels'][$channel['channel_id']] = false;
        }

        /* Human names and default permissions. */
        $perms['title']['jonah:admin'] = _("Administrator");
        $perms['tree']['jonah']['admin'] = false;
        $perms['title']['jonah:news'] = _("News");
        $perms['tree']['jonah']['news'] = false;
        $perms['title']['jonah:news:internal_channels'] = _("Internal Channels");
        $perms['tree']['jonah']['news']['internal_channels'] = false;
        $perms['title']['jonah:news:external_channels'] = _("External Channels");
        $perms['tree']['jonah']['news']['external_channels'] = false;

        /* Loop through internal channels and add them to the perms
         * titles. */
        foreach ($channels as $channel) {
            $perms['title']['jonah:news:internal_channels:' . $channel['channel_id']] = $channel['channel_name'];
            $perms['tree']['jonah']['news']['internal_channels'][$channel['channel_id']] = false;
        }

        $channels = $news->getChannels(JONAH_EXTERNAL_CHANNEL);

        /* Loop through external channels and add their ids to the
         * perms. */
        foreach ($channels as $channel) {
            $perms['title']['jonah:news:external_channels:' . $channel['channel_id']] = $channel['channel_name'];
            $perms['tree']['jonah']['news']['external_channels'][$channel['channel_id']] = false;
        }

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Jonah::getMenu();
    }

}