<?php
/**
 * Special prefs handling for the 'categorymanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Horde
 */
class Horde_Prefs_Special_Category implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output, $prefs;

        $page_output->addScriptFile('categoryprefs.js', 'horde');
        $page_output->addScriptFile('colorpicker.js', 'horde');
        $page_output->addInlineJsVars(array(
            'HordeCategoryPrefs.category_text' => _("Enter a name for the new category:")
        ));

        $cManager = new Horde_Prefs_CategoryManager();
        $categories = $cManager->get();
        $colors = $cManager->colors();
        $fgcolors = $cManager->fgColors();

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!$prefs->isLocked('category_colors')) {
            $t->set('picker_img',  Horde::img('colorpicker.png', _("Color Picker")));
        }
        $t->set('delete_img',  Horde::img('delete.png'));

        // Default Color
        $color = isset($colors['_default_'])
            ? htmlspecialchars($colors['_default_'])
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_default_'])
            ? htmlspecialchars($fgcolors['_default_'])
            : '#000000';
        $color_b = 'color_' . hash('md5', '_default_');

        $t->set('default_color', $color);
        $t->set('default_fgcolor', $fgcolor);
        $t->set('default_label', Horde::label($color_b, _("Default Color")));
        $t->set('default_id', $color_b);

        // Unfiled Color
        $color = isset($colors['_unfiled_'])
            ? htmlspecialchars($colors['_unfiled_'])
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_unfiled_'])
            ? htmlspecialchars($fgcolors['_unfiled_'])
            : '#000000';
        $color_b = 'color_' . hash('md5', '_unfiled_');

        $t->set('unfiled_color', $color);
        $t->set('unfiled_fgcolor', $fgcolor);
        $t->set('unfiled_label', Horde::label($color_b, _("Unfiled")));
        $t->set('unfiled_id', $color_b);

        $entries = array();
        foreach ($categories as $name) {
            $color = isset($colors[$name])
                ? htmlspecialchars($colors[$name])
                : '#FFFFFF';
            $fgcolor = isset($fgcolors[$name])
                ? htmlspecialchars($fgcolors[$name])
                : '#000000';
            $color_b = 'color_' . hash('md5', $name);

            $entries[] = array(
                'color' => $color,
                'fgcolor' => $fgcolor,
                'label' => Horde::label($color_b, ($name == '_default_' ? _("Default Color") : htmlspecialchars($name))),
                'id' => $color_b,
                'name' => htmlspecialchars($name)
            );
        }
        $t->set('categories', $entries);

        return $t->fetch(HORDE_TEMPLATES . '/prefs/category.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $page_output;

        $cManager = new Horde_Prefs_CategoryManager();

        /* Always save colors of all categories. */
        $colors = array();
        $categories = $cManager->get();
        foreach ($categories as $category) {
            if ($color = $ui->vars->get('color_' . hash('md5', $category))) {
                $colors[$category] = $color;
            }
        }
        if ($color = $ui->vars->get('color_' . hash('md5', '_default_'))) {
            $colors['_default_'] = $color;
        }
        if ($color = $ui->vars->get('color_' . hash('md5', '_unfiled_'))) {
            $colors['_unfiled_'] = $color;
        }
        $cManager->setColors($colors);

        switch ($ui->vars->cAction) {
        case 'add':
            $cManager->add($ui->vars->category);
            break;

        case 'remove':
            $cManager->remove($ui->vars->category);
            break;

        default:
            /* Save button. */
            $page_output->addInlineScript(array(
                'if (window.opener && window.name) window.close();'
            ));
            return true;
        }

        return false;
    }

}
