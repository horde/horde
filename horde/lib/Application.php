<?php
/**
 * Horde application API.
 *
 * @package Horde
 */
class Horde_Application extends Horde_Registry_Application
{
    public $version = '4.0-git';

    /**
     * Returns a list of available permissions.
     */
    public function perms()
    {
        $perms = array();

        $perms['tree']['horde']['max_blocks'] = false;
        $perms['title']['horde:max_blocks'] = _("Maximum Number of Portal Blocks");
        $perms['type']['horde:max_blocks'] = 'int';

        return $perms;
    }

    /**
     * Code to run when viewing prefs for this application.
     *
     * @param string $group  The prefGroup name.
     */
    public function prefsInit($group)
    {
        $out = array();

        /* Assign variables for select lists. */
        if (!$GLOBALS['prefs']->isLocked('timezone')) {
            $out['timezone_options'] = Horde_Nls::getTimezones();
            array_unshift($out['timezone_options'], _("Default"));
        }

        if (!$GLOBALS['prefs']->isLocked('initial_application')) {
            $out['initial_application_options'] = array();
            $apps = $GLOBALS['registry']->listApps(array('active'));
            foreach ($apps as $a) {
                if (file_exists($GLOBALS['registry']->get('fileroot', $a)) &&
                    (($GLOBALS['perms']->exists($a) && ($GLOBALS['perms']->hasPermission($a, Horde_Auth::getAuth(), Horde_Perms::READ) || Horde_Auth::isAdmin())) ||
                     !$GLOBALS['perms']->exists($a))) {
                    $out['initial_application_options'][$a] = $GLOBALS['registry']->get('name', $a);
                }
            }
            asort($out['initial_application_options']);
        }

        if (!$GLOBALS['prefs']->isLocked('theme')) {
            $out['theme_options'] = array();
            $theme_base = $GLOBALS['registry']->get('themesfs', 'horde');
            $dh = @opendir($theme_base);
            if (!$dh) {
                $GLOBALS['notification']->push("Theme directory can't be opened", 'horde.error');
            } else {
                while (($dir = readdir($dh)) !== false) {
                    if ($dir == '.' || $dir == '..') {
                        continue;
                    }

                    $theme_name = null;
                    @include $theme_base . '/' . $dir . '/info.php';
                    if (!empty($theme_name)) {
                        $out['theme_options'][$dir] = $theme_name;
                    }
                }
            }

            asort($out['theme_options']);
        }

        return $out;
    }

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsHandle($item, $updated)
    {
        switch ($item) {
        case 'showsummaryselect':
            $show_summaries = Horde_Util::getFormData('show_summaries');
            if (!is_null($show_summaries)) {
                $GLOBALS['prefs']->setValue('show_summaries', $show_summaries);
                return true;
            }
            break;

        case 'themeselect':
            $theme = Horde_Util::getFormData('theme');
            if (!is_null($theme)) {
                $GLOBALS['prefs']->setValue('theme', $theme);
                return true;
            }
            break;

        case 'categorymanagement':
            $cManager = new Horde_Prefs_CategoryManager();

            /* Always save colors of all categories. */
            $colors = array();
            $categories = $cManager->get();
            foreach ($categories as $category) {
                if ($color = Horde_Util::getFormData('color_' . md5($category))) {
                    $colors[$category] = $color;
                }
            }
            if ($color = Horde_Util::getFormData('color_' . md5('_default_'))) {
                $colors['_default_'] = $color;
            }
            if ($color = Horde_Util::getFormData('color_' . md5('_unfiled_'))) {
                $colors['_unfiled_'] = $color;
            }
            $cManager->setColors($colors);

            $action = Horde_Util::getFormData('cAction');
            $category = Horde_Util::getFormData('category');

            switch ($action) {
            case 'add':
                $cManager->add($category);
                break;

            case 'remove':
                $cManager->remove($category);
                break;

            default:
                /* Save button. */
                $updated = true;
                Horde::addInlineScript(
                    'if (window.opener && window.name) window.close();', 'javascript'
                );
            }
            break;

        case 'credentialsui':
            $credentials = Horde_Util::getFormData('credentials');
            if (!is_null($credentials)) {
                $GLOBALS['prefs']->setValue('credentials', serialize($credentials));
                return true;
            }
            break;
        }

        return $updated;
    }

    /**
     * Do anything that we need to do as a result of certain preferences
     * changing.
     */
    public function prefsCallback()
    {
        $need_reload = false;
        $old_sidebar = $GLOBALS['prefs']->getValue('show_sidebar');

        if ($GLOBALS['prefs']->isDirty('language')) {
            if ($GLOBALS['prefs']->isDirty('language')) {
                Horde_Nls::setLanguageEnvironment($GLOBALS['prefs']->getValue('language'));
                foreach ($GLOBALS['registry']->listAPIs() as $api) {
                    if ($GLOBALS['registry']->hasMethod($api . '/changeLanguage')) {
                        $GLOBALS['registry']->call($api . '/changeLanguage');
                    }
                }
            }

            $need_reload = true;
        } else {
            /* Do reload on change of any of these variables. */
            $need_reload = (
                $GLOBALS['prefs']->isDirty('sidebar_width') ||
                $GLOBALS['prefs']->isDirty('theme') ||
                $GLOBALS['prefs']->isDirty('menu_view') ||
                $GLOBALS['prefs']->isDirty('menu_refresh_time'));
        }

        if ($GLOBALS['prefs']->isDirty('show_sidebar')) {
            $need_reload = true;
            $old_sidebar = !$old_sidebar;
        }

        if ($need_reload) {
            $url = $GLOBALS['registry']->get('webroot', 'horde');
            if (substr($url, -1) != '/') {
                $url .= '/';
            }

            $url = str_replace('&amp;', '&', Horde::url(Horde_Util::addParameter($url . 'index.php', array('force_sidebar' => true, 'url' => Horde::selfUrl(true, false, true)), null, false)));

            /* If the old view was with sidebar, need to reload the entire
             * frame. */
            if ($old_sidebar) {
                Horde::addInlineScript(
                    'window.parent.frames.location = ' . Horde_Serialize::serialize($url, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ';'
                );
            } else {
                Horde::redirect($url);
            }
        }
    }

}
