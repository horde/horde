<?php
/**
 * Special prefs handling for the 'categorymanagement' preference.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL
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

        $view = new Horde_View(array(
            'templatePath' => HORDE_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Text');

        $view->picker_img = !$prefs->isLocked('category_colors');

        // Default Color
        $color = isset($colors['_default_'])
            ? $colors['_default_']
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_default_'])
            ? $fgcolors['_default_']
            : '#000000';
        $color_b = 'color_' . hash('md5', '_default_');

        $view->default_color = $color;
        $view->default_fgcolor = $fgcolor;
        $view->default_id = $color_b;

        // Unfiled Color
        $color = isset($colors['_unfiled_'])
            ? $colors['_unfiled_']
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_unfiled_'])
            ? $fgcolors['_unfiled_']
            : '#000000';
        $color_b = 'color_' . hash('md5', '_unfiled_');

        $view->unfiled_color = $color;
        $view->unfiled_fgcolor = $fgcolor;
        $view->unfiled_id = $color_b;

        $entries = array();
        foreach ($categories as $name) {
            $color = isset($colors[$name])
                ? $colors[$name]
                : '#FFFFFF';
            $fgcolor = isset($fgcolors[$name])
                ? $fgcolors[$name]
                : '#000000';
            $color_b = 'color_' . hash('md5', $name);

            $entries[] = array(
                'color' => $color,
                'fgcolor' => $fgcolor,
                'id' => $color_b,
                'name' => $name
            );
        }
        $view->categories = $entries;

        return $view->render('category');
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
