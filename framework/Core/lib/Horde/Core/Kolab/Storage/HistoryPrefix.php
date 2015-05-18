<?php
/**
 *
 */
class Horde_Core_Kolab_Storage_HistoryPrefix
implements Horde_Kolab_Storage_HistoryPrefix
{
    protected static $_mapping;

    /**
     * Generate a prefix for the History system for the given Kolab data.
     *
     * @param  Horde_Kolab_Storage_Data $data  The data object.
     *
     * @return string  The History prefix.
     */
    public static function getPrefix(Horde_Kolab_Storage_Data $data)
    {
        $app = self::_type2app($data->getType());
        if (empty($app)) {
            Horde::log(sprintf(
                'Unsupported app type: %s', $data->getType()), 'WARN');
            return false;
        }

        // Determine share id
        $user = $data->getAuth();
        $folder = $data->getPath();
        $share_id = '';
        $all_shares = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Share')
            ->create($app)
            ->listAllShares();

        foreach($all_shares as $id => $share) {
            if ($folder == $share->get('folder')) {
                $share_id = $id;
                break;
            }
        }

        // Bail out if we are unable to determine the share id.
        if (empty($share_id)) {
            Horde::log(
                sprintf('HISTORY: share_id not found. Can\'t compute history prefix for user: %s, folder: %s', $user, $folder),
                'WARN'
            );
            return false;
        }

        return $app . ':' . $share_id . ':';
    }

    /**
     * Map Kolab object type to horde application name.
     *
     * @param string $type  Kolab object type
     *
     * @return string The horde application name of false if not known.
     */
    protected function _type2app($type)
    {
        global $registry;

        if (!isset(self::$_mapping)) {
            self::$_mapping = array(
                'contact' => $registry->hasInterface('contacts'),
                'event' => $registry->hasInterface('calendar'),
                'note' => $registry->hasInterface('notes'),
                'task' => $registry->hasInterface('tasks')
            );
        }

        return !empty(self::$_mapping[$type])
            ? self::$_mapping[$type]
            : false;
    }

}