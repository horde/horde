<?php
/**
 * Class for handling a list of categories stored in a user's
 * preferences.
 *
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_CategoryManager
{
    /**
     * Get all categories.
     */
    static public function get()
    {
        $string = $GLOBALS['prefs']->getValue('categories');
        if (empty($string)) {
            return array();
        }

        $categories = explode('|', $string);
        asort($categories);

        return $categories;
    }

    /**
     * TODO
     */
    static public function getSelect($id, $current = null)
    {
        $categories = self::get();
        $colors = self::colors();
        $fgcolors = self::fgColors();

        $id_html = htmlspecialchars($id);
        $html = '<select id="' . preg_replace('/[^A-Za-z0-9-_:.]+/', '_', $id_html) . '" name="' . $id_html . '">';

        if (!in_array($current, $categories) && !empty($current)) {
            $curr_html = htmlspecialchars($current);
            $html .= '<option value="*new*' . $curr_html . '">'
                . sprintf(Horde_Prefs_Translation::t("Use Current: %s"), $curr_html)
                . '</option>'
                . '<option value="" disabled="disabled">- - - - - - - - -</option>';
        }

        if (!$GLOBALS['prefs']->isLocked('categories')) {
            $html .= '<option value="*new*">' . Horde_Prefs_Translation::t("New Category")
                . "</option>\n"
                . '<option value="" disabled="disabled">- - - - - - - - -</option>';
        }

        // Always add an Unfiled option.
        $html .= '<option value="" style="background:'
            . $colors['_unfiled_'] . ';color:' . $fgcolors['_unfiled_'] . '"'
            . (empty($current) ? ' selected="selected">' : '>')
            . htmlspecialchars(Horde_Prefs_Translation::t("Unfiled")) . '</option>';

        foreach ($categories as $name) {
            $name_html = htmlspecialchars($name);
            $html .= '<option value="' . $name_html
                . '" style="background:' . (isset($colors[$name]) ? $colors[$name] : '#fff')
                . ';color:' . (isset($fgcolors[$name]) ? $fgcolors[$name] : '#000') . '"'
                . ($name === $current ? ' selected="selected">' : '>')
                . $name_html . '</option>';
        }

        return $html . '</select>';
    }

    /**
     * TODO
     */
    static public function getJavaScript($formname, $elementname)
    {
        $prompt = addslashes(Horde_Prefs_Translation::t("Please type the new category name:"));
        $error = addslashes(Horde_Prefs_Translation::t("You must type a new category name."));

        return <<<JAVASCRIPT

<script type="text/javascript">
<!--
function checkCategory()
{
    if (document.${formname}['$elementname'].value == '*new*') {
        var category = window.prompt('$prompt', '');
        if (category != null && category != '') {
            document.$formname.new_category.value = category;
        } else {
            window.alert('$error');
            return false;
        }
    } else if (document.${formname}['$elementname'].value.indexOf('*new*') != -1) {
        document.$formname.new_category.value = document.${formname}['$elementname'].value.substr(5, document.${formname}['$elementname'].value.length);
    }

    return true;
}
//-->
</script>
JAVASCRIPT;
    }

    /**
     * Add a new category.
     *
     * @param string $category  The name of the category to add.
     *
     * @return mixed  False on failure, or the new category's name.
     */
    static public function add($category)
    {
        if ($GLOBALS['prefs']->isLocked('categories') || empty($category)) {
            return false;
        }

        $categories = self::get();
        if (in_array($category, $categories)) {
            return $category;
        }

        $categories[] = $category;
        $GLOBALS['prefs']->setValue('categories', implode('|', $categories));

        return $category;
    }

    /**
     * Delete a category.
     *
     * @param string $category  The category to remove.
     *
     * @return boolean  True on success, false on failure.
     */
    static public function remove($category)
    {
        if ($GLOBALS['prefs']->isLocked('categories')) {
            return false;
        }

        $categories = self::get();

        $key = array_search($category, $categories);
        if ($key === false) {
            return $key;
        }

        unset($categories[$key]);
        $GLOBALS['prefs']->setValue('categories', implode('|', $categories));

        // Remove any color preferences for $category.
        $colors = self::colors();
        unset($colors[$category]);
        self::setColors($colors);

        return true;
    }

    /**
     * Returns the color for each of the user's categories.
     *
     * @return array  A list of colors, key is the category name, value is the
     *                HTML color code.
     */
    static public function colors()
    {
        /* Default values that can be overridden but must always be
         * present. */
        $colors['_default_'] = '#FFFFFF';
        $colors['_unfiled_'] = '#DDDDDD';

        $pairs = explode('|', $GLOBALS['prefs']->getValue('category_colors'));
        foreach ($pairs as $pair) {
            if (!empty($pair)) {
                list($category, $color) = explode(':', $pair);
                $colors[$category] = $color;
            }
        }

        $colors[''] = $colors['_unfiled_'];

        return $colors;
    }

    /**
     * Returns the foreground color for each of the user's categories.
     *
     * @return array  A list of colors, key is the category name, value is the
     *                HTML color code.
     */
    static public function fgColors()
    {
        $colors = self::colors();
        $fgcolors = array();
        foreach ($colors as $name => $color) {
            $fgcolors[$name] = Horde_Image::brightness($color) < 128 ? '#f6f6f6' : '#000';
        }

        return $fgcolors;
    }

    /**
     * TODO
     */
    static public function setColor($category, $color)
    {
        $colors = self::colors();
        $colors[$category] = $color;
        self::setColors($colors);
    }

    /**
     * TODO
     */
    static public function setColors($colors)
    {
        $pairs = array();
        foreach ($colors as $category => $color) {
            if ($color[0] != '#') {
                $color = '#' . $color;
            }
            if (!empty($category)) {
                $pairs[] = $category . ':' . $color;
            }
        }

        $GLOBALS['prefs']->setValue('category_colors', implode('|', $pairs));
    }

}
