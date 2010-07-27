<?php
/**
 * The Horde_Form_VarRenderer_Xhtml:: class renders variables as Xhtml.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2005 Matt Warden <mwarden@gmail.com>
 *
 * See the enclosed file LICENSE for license information (LGPL).
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Horde_Form
 */
class Horde_Form_VarRenderer_Xhtml extends Horde_Form_VarRenderer
{
    protected $_onLoadJS = array();

    /**
     * Handles the end of rendering of variables; writes onload JavaScript.
     *
     * @access public
     * @author ?
     * @return string the javascript to execute, and its container script tags,
     *            or and empty string if there is nothing to execute onload
     */
    public function renderEnd()
    {
        if (count($this->_onLoadJS)) {
            return "<script type=\"text/javascript\">" .
                "<!--\n" .  implode("\n", $this->_onLoadJS) . "\n// -->\n" .
                "</script>";
        } else {
            return '';
        }
    }

    function _renderVarInputDefault($form, $var, $vars)
    {
        throw new Horde_Form_Exception('Unknown variable type:' . get_class($var->type));
    }

    function _renderVarInput_number($form, $var, $vars)
    {
        $value = $var->getValue($vars);
        if ($var->type->fraction) {
            $value = sprintf('%01.' . $var->type->fraction . 'f', $value);
        }
        $linfo = Horde_Nls::getLocaleInfo();
        /* Only if there is a mon_decimal_point do the
         * substitution. */
        if (!empty($linfo['mon_decimal_point'])) {
            $value = str_replace('.', $linfo['mon_decimal_point'], $value);
        }
        return sprintf('    <input type="text" class="form-input-number" name="%1$s" id="%1$s" value="%2$s"%3$s />',
                       $var->getVarName(),
                       $value,
                       $this->_getActionScripts($form, $var)
               );
    }

    function _renderVarInput_int($form, $var, $vars)
    {
        return sprintf('    <input type="text" class="form-input-int" name="%1$s" id="%1$s" value="%2$s"%3$s />',
                       $var->getVarName(),
                       $value = $var->getValue($vars),
                       $this->_getActionScripts($form, $var)
               );
    }

    function _renderVarInput_octal($form, $var, $vars)
    {
        return sprintf('<input type="text" class="form-input-octal" name="%1$s" id="%1$s" value="%2$s"%3$s />',
                       $var->getVarName(),
                       sprintf('0%o', octdec($var->getValue($vars))),
                       $this->_getActionScripts($form, $var)
               );
    }

    function _renderVarInput_intlist($form, $var, $vars)
    {
        return sprintf('<input type="text" class="form-input-intlist" name="%1$s" id="%1$s" value="%2$s"%3$s />',
                       $var->getVarName(),
                       $value = $var->getValue($vars),
                       $this->_getActionScripts($form, $var)
               );
    }

    function _renderVarInput_text($form, $var, $vars)
    {
        return sprintf(
            '<input type="text" class="form-input-text%1$s" name="%2$s" '
            . 'id="%2$s" value="%3$s"%4$s%5$s%6$s />',
            ($var->isDisabled() ? ' form-input-disabled" ' : ''),
            $var->getVarName(),
            htmlspecialchars($var->getValue($vars), ENT_QUOTES, $GLOBALS['registry']->getCharset()),
            ($var->isDisabled() ? ' disabled="disabled" ' : ''),
            ($var->type->maxlength ? ' maxlength="' . $var->type->maxlength . '"' : ''),
            $this->_getActionScripts($form, $var)
        );
    }

    function _renderVarInput_stringlist($form, $var, $vars)
    {
        return sprintf(
            '<input type="text" class="form-input-stringlist" name="%s" value="%s"%s />',
            $var->getVarName(),
            $value = $var->getValue($vars),
            $this->_getActionScripts($form, $var)
        );
    }

    function _renderVarInput_phone($form, $var, $vars)
    {
        return sprintf(
            '<input type="text" class="form-input-phone" name="%1$s" id="%1$s" value="%2$s" %3$s%4$s />',
            $var->getVarName(),
            htmlspecialchars($var->getValue($vars), ENT_QUOTES, $GLOBALS['registry']->getCharset()),
            ($var->isDisabled() ? ' disabled="disabled" ' : ''),
            $this->_getActionScripts($form, $var)
        );
    }

    function _renderVarInput_cellphone($form, $var, $vars)
    {
        return $this->_renderVarInput_phone($form, $var, $vars);
    }

    function _renderVarInput_ipaddress($form, $var, $vars)
    {
        return sprintf('    <input type="text" class="form-input-ipaddress" name="%1$s" id="%1$s" value="%2$s" %3$s%4$s />',
                       $var->getVarName(),
                       htmlspecialchars($var->getValue($vars), ENT_QUOTES, $GLOBALS['registry']->getCharset()),
                       $var->isDisabled() ? ' disabled="disabled" ' : '',
                       $this->_getActionScripts($form, $var)
               );
    }

    function _renderVarInput_file($form, $var, $vars)
    {
        $file = $var->getValue($vars);
        return sprintf('    <input type="file" class="form-input-file" name="%1$s" id="%1$s"%2$s />',
                       $var->getVarName(),
                       $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_longtext($form, $var, $vars)
    {
        global $browser;

        $html = sprintf('<textarea class="form-input-longtext" id="%1$s" name="%1$s" '
                            .'cols="%2$s" rows="%3$s"%4$s%5$s>%6$s</textarea>',
                        $var->getVarName(),
                        $var->type->cols,
                        $var->type->rows,
                        $this->_getActionScripts($form, $var),
                        $var->isDisabled() ? ' disabled="disabled"' : '',
                        htmlspecialchars($var->getValue($vars)));

        if ($var->type->hasHelper('rte')) {
            $GLOBALS['injector']->getInstance('Horde_Editor')->getEditor('ckeditor', array('id' => $var->getVarName()));
        }

        if ($var->type->hasHelper() && $browser->hasFeature('javascript')) {
            $html .= '<div class="form-html-helper">';
            Horde::addScriptFile('open_html_helper.js', 'horde');
            $imgId = $var->getVarName() . 'ehelper';
            if ($var->type->hasHelper('emoticons')) {
                $filter = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->getFilter('emoticons');
                $icon_list = array();

                foreach (array_flip($filter->getIcons()) as $icon => $string) {
                    $icon_list[] = array(
                        $filter->getIcon($icon),
                        $string
                    );
                }

                Horde::addInlineScript(array(
                    'Horde_Html_Helper.iconlist = ' . Horde_Serialize::serialize($icon_list, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
                ));

                $html .= Horde::link('#', _("Emoticons"), '', '', 'Horde_Html_Helper.open(\'emoticons\', \'' . $var->getVarName() . '\'); return false;')
                    . Horde::img('emoticons/smile.png', _("Emoticons"), 'id="' . $imgId . '" align="middle"')
                    . '</a>'."\n";
            }
            $html .= '</div><div id="htmlhelper_' . $var->getVarName()
                    . '" class="form-control"></div>'."\n";
        }

        return $html;
    }

    function _renderVarInput_countedtext($form, $var, $vars)
    {
        return sprintf('<textarea class="form-input-countedtext" id="%1$s" name="%1$s" '
                        .'cols="%2$s" rows="%3$s"%4$s%5$s>%6$s</textarea>',
                       $var->getVarName(),
                       $var->type->cols,
                       $var->type->rows,
                       $this->_getActionScripts($form, $var),
                       $var->isDisabled() ? ' disabled="disabled"' : '',
                       $var->getValue($vars));
    }

    function _renderVarInput_address($form, $var, $vars)
    {
        return sprintf('<textarea class="form-input-address" id="%1$s" name="%1$s" '
                        .'cols="%2$s" rows="%3$s"%4$s%5$s>%6$s</textarea>',
                       $var->getVarName(),
                       $var->type->cols,
                       $var->type->rows,
                       $this->_getActionScripts($form, $var),
                       $var->isDisabled() ? ' disabled="disabled"' : '',
                       $var->getValue($vars));
    }

    function _renderVarInput_date($form, $var, $vars)
    {
        return sprintf('    <input type="text" class="form-input-date" name="%1$s" id="%1$s" '
                            .'value="%2$s"%3$s />',
                        $var->getVarName(),
                        $value = $var->getValue($vars),
                        $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_time($form, $var, $vars)
    {
        return sprintf('    <input type="text" class="form-input-time" name="%1$s" id="%1$s" '
                            .'value="%2$s"%3$s />',
                       $var->getVarName(),
                       $value = $var->getValue($vars),
                       $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_hourminutesecond($form, $var, $vars)
    {
        $varname = $var->getVarName();
        $time = $var->type->getTimeParts($var->getValue($vars));

        /* Output hours. */
        $hours = array('' => _("hh"));
        for ($i = 0; $i <= 23; $i++) {
            $hours[sprintf('%02d', $i)] = $i;
        }
        $html = sprintf('<select name="%1$s[hour]" id="%1$s[hour]"%2$s>%3$s    </select>',
                        $varname,
                        $this->_selectOptions($hours, $time['hour']),
                        $this->_getActionScripts($form, $var));

        /* Output minutes. */
        $minutes = array('' => _("mm"));
        for ($i = 0; $i <= 59; $i++) {
            $minutes[sprintf('%02d', $i)] = $i;
        }
        $html .= sprintf('<select name="%1$s[minute]" id="%1$s[minute]"%2$s>%3$s    </select>',
                         $varname,
                         $this->_selectOptions($minutes, $time['minute']),
                         $this->_getActionScripts($form, $var));

        /* Return if seconds are not required. */
        if ($var->type->show_seconds) {
            /* Output seconds. */
            $seconds = array('' => _("ss"));
            for ($i = 0; $i <= 59; $i++) {
                $seconds[sprintf('%02d', $i)] = $i;
            }
            $html .= sprintf('<select name="%1$s[second]" id="%1$s[second]"%2$s>%3$s    </select>',
                            $varname,
                            $this->_getActionScripts($form, $var),
                            $this->_selectOptions($seconds, $time['second']));
        }

        return $html;
    }

    function _renderVarInput_monthyear($form, $var, $vars)
    {
        $dates = array();
        $dates['month'] = array('' => _("MM"),
                                1 => _("January"),
                                2 => _("February"),
                                3 => _("March"),
                                4 => _("April"),
                                5 => _("May"),
                                6 => _("June"),
                                7 => _("July"),
                                8 => _("August"),
                                9 => _("September"),
                                10 => _("October"),
                                11 => _("November"),
                                12 => _("December"));
        $dates['year'] = array('' => _("YYYY"));
        if ($var->type->start_year > $var->type->end_year) {
            for ($i = $var->type->start_year; $i >= $var->type->end_year; $i--) {
                $dates['year'][$i] = $i;
            }
        } else {
            for ($i = $var->type->start_year; $i <= $var->type->end_year; $i++) {
                $dates['year'][$i] = $i;
            }
        }
        $html = sprintf('<select name="%1$s" id="%1$s"%2$s>%3$s    </select>',
               $var->type->getMonthVar($var),
               $this->_getActionScripts($form, $var),
               $this->_selectOptions($dates['month'], $vars->get($var->type->getMonthVar($var))));

        $html .= sprintf('<select name="%1$s" id="%1$s"%2$s>%3$s    </select>',
               $var->type->getYearVar($var),
               $this->_getActionScripts($form, $var),
               $this->_selectOptions($dates['year'], $vars->get($var->type->getYearVar($var))));

        return $html;
    }

    function _renderVarInput_monthdayyear($form, $var, $vars)
    {
        $dates = array();
        $dates['month'] = array(''   => _("MM"),
                                '1'  => _("January"),
                                '2'  => _("February"),
                                '3'  => _("March"),
                                '4'  => _("April"),
                                '5'  => _("May"),
                                '6'  => _("June"),
                                '7'  => _("July"),
                                '8'  => _("August"),
                                '9'  => _("September"),
                                '10' => _("October"),
                                '11' => _("November"),
                                '12' => _("December"));
        $dates['day'] = array('' => _("DD"));
        for ($i = 1; $i <= 31; $i++) {
            $dates['day'][$i] = $i;
        }
        $dates['year'] = array('' => _("YYYY"));
        if ($var->type->start_year > $var->type->end_year) {
            for ($i = $var->type->start_year; $i >= $var->type->end_year; $i--) {
                $dates['year'][$i] = $i;
            }
        } else {
            for ($i = $var->type->start_year; $i <= $var->type->end_year; $i++) {
                $dates['year'][$i] = $i;
            }
        }
        $date = $var->type->getDateParts($var->getValue($vars));

        // TODO: use NLS to get the order right for the Rest Of The
        // World.
        $html = '';
        $date_parts = array('month', 'day', 'year');
        foreach ($date_parts as $part) {
            $varname = $var->getVarName() . '[' . $part . ']';
            $html .= sprintf('<select name="%1$s" id="%1$s"%2$s>%3$s    </select>',
                             $varname,
                             $this->_getActionScripts($form, $var),
                             $this->_selectOptions($dates[$part], $date[$part]));
        }

        return $html;
    }

    function _renderVarInput_datetime($form, $var, $vars)
    {
        return parent::_renderVarInput_monthdayyear($form, $var, $vars) .
            parent::_renderVarInput_hourminutesecond($form, $var, $vars);
    }

    function _renderVarInput_colorpicker($form, $var, $vars)
    {
        $html = '<div class="form-colorpicker">'
            . '<input type="text" maxlength="7" name="'
            . $var->getVarName() . '" id="' . $var->getVarName()
            . '" value="' . $var->getValue($vars) . '" />';

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            Horde::addScriptFile('open_colorpicker.js', 'horde');
            $html .= Horde::img('blank.gif', '', array('class' => 'form-colorpicker-preview',
                                                       'id' => 'colordemo_' . $var->getVarName(),
                                                       'style' => 'background:' . $var->getValue($vars)))
                . Horde::link('#', _("Color Picker"), '', '', 'openColorPicker(\''. $var->getVarName() .'\'); return false;')
                . Horde::img('colorpicker.png', _("Color Picker")) . '</a>'
                . '<div id="colorpicker_' . $var->getVarName() . '" class="form-colorpicker-palette"></div>';
        }

        return $html . '</div>';
    }

    function _renderVarInput_sorter($form, $var, $vars)
    {
        global $registry;

        $varname = $var->getVarName();
        $instance = $var->type->instance;

        Horde::addScriptFile('sorter.js', 'horde');

        return '    <input type="hidden" name="'. $varname
            . '[array]" value="" id="'. $varname .'-array-" />'."\n"
            . '    <select class="leftFloat" multiple="multiple" size="'
            . $var->type->size . '" name="' . $varname
            . '[list]" onchange="' . $instance . '.deselectHeader();" '
            . ' id="'. $varname . '-list-">'
            . $var->type->getOptions($var->getValue($vars)) . '    </select><div class="leftFloat">'
            . Horde::link('#', _("Move up"), '', '', $instance . '.moveColumnUp(); return false;')
                . Horde::img('nav/up.png', _("Move up"))
                . '</a><br />'
            . Horde::link('#', _("Move up"), '', '', $instance . '.moveColumnDown(); return false;')
                . Horde::img('nav/down.png', _("Move down"))
                . '</a></div>'
            . '<script type="text/javascript">' . "\n"
            . sprintf('%1$s = new Horde_Form_Sorter(\'%1$s\', \'%2$s\', \'%3$s\');' . "\n",
                    $instance, $varname, $var->type->header)
            . sprintf("%s.setHidden();\n</script>\n", $instance);
    }

    function _renderVarInput_assign($form, $var, $vars)
    {
        global $registry;

        Horde::addScriptFile('form_assign.js', 'horde');

        $name = $var->getVarName();
        $fname = $form->getName() . '.' . $name;
        $width = $var->type->width;
        $lhdr = (bool)$var->type->getHeader(0);
        $rhdr = (bool)$var->type->getHeader(1);
        $this->_onLoadJS[] = 'Horde_Form_Assign.setField(\'' . $fname . '\');';

        $html = '<div class="form-input-assign">'
             . '    <input type="hidden" name="' . $name . '__values" id="' . $name . '__values" />'
             . sprintf('    <select name="%1$s__left" id="%1$s__left" multiple="multiple" '
                         .'size="%2$d" style="width:%3$s"%4$s>',
                     $name, $var->type->size, $width,
                     $lhdr ? ' onchange="Horde_Form_Assign.deselectHeaders(\'' . $fname . '\', 0);"' : '')
             . $var->type->getOptions(0, $fname)
             . '    </select>'
             . '<div><a href="" onclick="Horde_Form_Assign.move(\''. $fname .'\', 0); return false;">'
             . Horde::img('rhand.png', _("Add column"))
             . '</a><br /><a href="" onclick="Horde_Form_Assign.move(\''
             . $fname . '\', 1); return false;">'
             . Horde::img('lhand.png', _("Remove column"))
             . '</a></div>'
             . sprintf('    <select name="%s__right" multiple="multiple" size="%d" style="width:%s"%s>',
                     $name, $size, $width,
                     $rhdr ? ' onchange="Horde_Form_Assign.deselectHeaders(\'' . $fname . '\', 1);"' : '')
             . $var->type->getOptions(1, $fname)
             . '    </select></div>';

        return $html;
    }

    function _renderVarInput_invalid($form, $var, $vars)
    {
        return $this->_renderVarDisplay_invalid($form, $var, $vars);
    }

    function _renderVarInput_enum($form, $var, $vars)
    {
        $values = $var->getValues();
        $prompt = $var->type->prompt;
        $htmlchars = $var->getOption('htmlchars');
        if ($prompt) {
            $prompt = '<option value="">' . ($htmlchars ? htmlspecialchars($prompt, ENT_QUOTES, $GLOBALS['registry']->getCharset()) : $prompt) . '</option>';
        }
        return sprintf('    <select name="%1$s" id="%1$s"%2$s>%3$s%4$s    </select>',
               $var->getVarName(),
               $this->_getActionScripts($form, $var),
               $prompt,
               $this->_selectOptions($values, $var->getValue($vars), $htmlchars));
    }

    function _renderVarInput_mlenum($form, $var, $vars)
    {
        $varname = $var->getVarName();
        $values = $var->getValues();
        $prompts = $var->type->prompts;
        $selected = $var->getValue($vars);

        /* If passing a non-array value need to get the keys. */
        if (!is_array($selected)) {
            foreach ($values as $key_1 => $values_2) {
                if (isset($values_2[$selected])) {
                    $selected = array('1' => $key_1, '2' => $selected);
                    break;
                }
            }
        }

        /* Hidden tag to store the current first level. */
        $html = sprintf('    <input type="hidden" name="%1$s[old]" id="%1$s[old]" value="%2$s" />',
                        $varname,
                        htmlspecialchars($selected['1'], ENT_QUOTES, $GLOBALS['registry']->getCharset()));

        /* First level. */
        $values_1 = Horde_Array::valuesToKeys(array_keys($values));
        $html .= sprintf('    <select id="%1$s[1]" name="%1$s[1]" onchange="%2$s"%3$s>',
                         $varname,
                         'if (this.value) { document.' . $form->getName() . '.formname.value=\'\';' . 'document.' . $form->getName() . '.submit() }',
                         ($var->hasAction() ? ' ' . $this->_genActionScript($form, $var->_action, $varname) : ''));
        if (!empty($prompts)) {
            $html .= '<option value="">' . htmlspecialchars($prompts[0], ENT_QUOTES, $GLOBALS['registry']->getCharset()) . '</option>';
        }
        $html .= $this->_selectOptions($values_1, $selected['1']);
        $html .= '    </select>';

        /* Second level. */
        $html .= sprintf('    <select id="%1$s[2]" name="%1$s[2]"%2$s>',
                         $varname,
                         ($var->hasAction() ? ' ' . $this->_genActionScript($form, $var->_action, $varname) : ''));
        if (!empty($prompts)) {
            $html .= '<option value="">' . htmlspecialchars($prompts[1], ENT_QUOTES, $GLOBALS['registry']->getCharset()) . '</option>';
        }
        $values_2 = array();
        if (!empty($selected['1'])) {
            $values_2 = $values[$selected['1']];
        }
        return $html . $this->_selectOptions($values_2, $selected['2']) . '    </select>';
    }

    function _renderVarInput_multienum($form, $var, $vars)
    {
        $values = $var->getValues();
        $selected = $vars->getExists($var->getVarName(), $wasset);
        if (!$wasset) {
            $selected = $var->getDefault();
        }
        $html = sprintf('    <select multiple="multiple" size="%1$s" name="%2$s[]" id="%2$s[]" %3$s>%4$s    </select>',
                        $var->type->size,
                        $var->getVarName(),
                        $this->_getActionScripts($form, $var),
                        $this->_multiSelectOptions($values, $selected));
        return $html . '<p class="form-hint">'
            . _("To select multiple items, hold down the Control (PC) or Command (Mac) key while clicking.")
            . "</p>\n";
    }

    function _renderVarInput_keyval_multienum($form, $var, $vars)
    {
        return $this->_renderVarInput_multienum($form, $var, $vars);
    }

    function _renderVarInput_radio($form, $var, $vars)
    {
        return $this->_radioButtons($var->getVarName(),
                                    $var->getValues(),
                                    $var->getValue($vars),
                                    $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_set($form, $var, $vars)
    {
        $html = $this->_checkBoxes($var->getVarName(),
                                   $var->getValues(),
                                   $var->getValue($vars),
                                   $this->_getActionScripts($form, $var));

        if ($var->type->checkAll) {
            $form_name = $form->getName();
            $var_name = $var->getVarName() . '[]';
            $function_name = 'select'  . $form_name . $var->getVarName();
            $enable = _("Select all");
            $disable = _("Select none");
            $invert = _("Invert selection");
            $html .= <<<EOT
<script type="text/javascript">
function $function_name()
{
    for (var i = 0; i < document.$form_name.elements.length; i++) {
        f = document.$form_name.elements[i];
        if (f.name != '$var_name') {
            continue;
        }
        if (arguments.length) {
            f.checked = arguments[0];
        } else {
            f.checked = !f.checked;
        }
    }
}
</script>
<a href="#" onclick="$function_name(true); return false;">$enable</a>,
<a href="#" onclick="$function_name(false); return false;">$disable</a>,
<a href="#" onclick="$function_name(); return false;">$invert</a>
EOT;
        }

        return $html;
    }

    function _renderVarInput_link($form, $var, $vars)
    {
        return $this->_renderVarDisplay_link($form, $var, $vars);
    }

    function _renderVarInput_html($form, $var, $vars)
    {
        return $this->_renderVarDisplay_html($form, $var, $vars);
    }

    function _renderVarInput_email($form, $var, $vars)
    {
        return sprintf('    <input type="text" id="%1$s" name="%1$s" value="%2$s"%3$s />',
               $var->getVarName(),
               $value = $var->getValue($vars),
               $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_matrix($form, $var, $vars)
    {
        $varname   = $var->getVarName();
        $var_array = $var->getValue($vars);
        $cols      = $var->type->cols;
        $rows      = $var->type->rows;
        $matrix    = $var->type->matrix;
        $new_input = $var->type->new_input;

        $html = '<table cellspacing="0"><tr>';

        $html .= '<td align="right" width="20%"></td>';
        foreach ($cols as $col_title) {
            $html .= sprintf('<td align="center" width="1%%">%s</td>', $col_title);
        }
        $html .= '<td align="right" width="60%"></td></tr>';

        /* Offer a new row of data to be added to the matrix? */
        if ($new_input) {
            $html .= '<tr><td>'."\n";
            if (is_array($new_input)) {
                $html .= sprintf('    <select%s name="%s[n][r]"><option value="">%s</option>%s    </select><br />'."\n",
                       ' id="'. $varname .'-n--r-"',
                       $varname,
                       _("-- select --"),
                       $this->_selectOptions($new_input, $var_array['n']['r']));
            } elseif ($new_input == true) {
                $html .= sprintf('    <input%s type="text" name="%s[n][r]" value="%s" />',
                       ' id="'. $varname .'-n--r-',
                       $varname,
                       $var_array['n']['r']);
            }
            $html .= ' </td>';
            foreach ($cols as $col_id => $col_title) {
                $html .= sprintf('<td align="center"><input type="checkbox" class="checkbox" name="%s[n][v][%s]" /></td>', $varname, $col_id);
            }
            $html .= '<td>&nbsp;</td></tr>'."\n";
        }

        /* Loop through the rows and create checkboxes for each column. */
        foreach ($rows as $row_id => $row_title) {
            $html .= sprintf('<tr><td>%s</td>', $row_title);
            foreach ($cols as $col_id => $col_title) {
                $html .= sprintf('<td align="center"><input type="checkbox" class="checkbox" name="%s[r][%s][%s]"%s /></td>', $varname, $row_id, $col_id, (!empty($matrix[$row_id][$col_id]) ? ' checked="checked"' : ''));
            }
            $html .= '<td>&nbsp;</td></tr>'."\n";
        }

        $html .= '</table>'."\n";
        return $html;
    }

    function _renderVarInput_password($form, $var, $vars)
    {
        return sprintf('<input type="password" id="%1$s" name="%1$s" value="%2$s"%3$s />',
               $var->getVarName(),
               $value = $var->getValue($vars),
               $this->_getActionScripts($form, $var));
    }

    function _renderVarInput_emailconfirm($form, $var, $vars)
    {
        $email = $var->getValue($vars);
        return '<ul><li>' . sprintf('<input type="text" class="form-input-emailconfirm"' .
                                    ' id="%1$s" name="%1$s[original]" value="%2$s"%3$s />',
                                    $var->getVarName(),
                                    $value = $email['original'],
                                    $this->_getActionScripts($form, $var)) . '</li><li>' .
            sprintf('<input type="text" class="form-input-emailconfirm"' .
                    ' id="%1$s-confirm-" name="%1$s[confirm]" value="%2$s"%3$s />',
                    $var->getVarName(),
                    $value = $email['confirm'],
                    $this->_getActionScripts($form, $var)) . '</li></ul>';
    }

    function _renderVarInput_passwordconfirm($form, $var, $vars)
    {
        $password = $var->getValue($vars);
        return '<ul><li>' . sprintf('<input type="password" class="form-input-passwordconfirm"'
                                    .' id="%1$s" name="%1$s[original]" value="%2$s"%3$s />',
                                    $var->getVarName(),
                                    $value = $password['original'],
                                    $this->_getActionScripts($form, $var)) . '</li><li>' .
            sprintf('<input type="password" class="form-input-passwordconfirm"'
                    .' id="%1$s-confirm-" name="%1$s[confirm]" value="%2$s"%3$s />',
                    $var->getVarName(),
                    $value = $password['confirm'],
                    $this->_getActionScripts($form, $var)) . '</li></ul>';
    }

    function _renderVarInput_boolean($form, $var, $vars)
    {
        $varName = $var->getVarName();

        $html = '    <input type="checkbox" class="form-input-checkbox" id="' .  $varName . '"'
            .  ' name="' .  $varName . '"'
            . ($var->getValue($vars) ? ' checked="checked"' : '');
        if ($var->hasAction()) {
            $html .= $this->_genActionScript($form, $var->_action,
                                             $var->getVarName());
        }
        $html .= ' />';
        return $html;
    }

    function _renderVarInput_creditcard($form, $var, $vars)
    {
        $varName = $var->getVarName();

        $html = '    <input type="text" class="form-input-creditcard" id="' .  $varName . '"'
            .  ' name="' .  $varName . '"'
            .$var->getValue($vars);
        if ($var->hasAction()) {
            $html .= $this->_genActionScript($form, $var->_action,
                                             $var->getVarName());
        }

        return $html . ' />';
    }

    function _renderVarInput_obrowser($form, $var, $vars)
    {
        $varname = $var->getVarName();
        $varvalue = $vars->get($varname);
        $fieldId = 'obrowser_' . hash('md5', uniqid(rand(), true));
        $html = '
            <script type="text/javascript">
            var obrowserWindowName;
            function obrowserCallback(name, oid)
            {
                if (name == obrowserWindowName) {
                    document.getElementById(\'' . $fieldId . '\').value = oid;
                    return false;
                } else {
                    return "Invalid window name supplied";
                }
            }
            </script>
            ';
        $html .= sprintf('<input type="hidden" name="%s" id="%s"%s value="%s" />',
                         $varname,
                         $fieldId,
                         $this->_getActionScripts($form, $var),
                         $varvalue);
        if (!empty($varvalue)) {
            $html .= $varvalue;
        }

        if ($GLOBALS['browser']->hasFeature('javascript')) {
            $imgId = $varname .'goto';
            $html .= '<div id="goto" class="headerbox" style="position:absolute;visibility:hidden;padding:0"></div>';
            $html .= Horde::link('#', _("Select an object"), '', '', 'obrowserWindow = ' . Horde::popupJs($GLOBALS['registry']->get('webroot', 'horde') . '/services/obrowser/') . 'obrowserWindowName = obrowserWindow.name; return false;') . Horde::img('tree/leaf.png', _("Object"), 'id="' . $imgId . '" align="middle"') . "</a>\n";
        }

        return $html;
    }

    function _renderVarInput_dblookup($form, $var, $vars)
    {
        return $this->_renderVarInput_enum($form, $var, $vars);
    }

    function _renderVarInput_figlet($form, $var, $vars)
    {
        return sprintf('    <input type="text" class="form-input-figlet" id="%1$s" name="%1$s" size="%2$s" value="%3$s" />',
                       $var->getVarName(),
                       strlen($var->type->text),
                       htmlspecialchars($var->getValue($vars))) .
            '<p class="form-input-figlet">' . _("Enter the letters below:") . '</p>' .
            $this->_renderVarDisplay_figlet($form, $var, $vars);
    }

    function _renderVarDisplayDefault($form, $var, $vars)
    {
        return nl2br(htmlspecialchars($var->getValue($vars), ENT_QUOTES,
            $GLOBALS['registry']->getCharset()));
    }

    function _renderVarDisplay_html($form, $var, $vars)
    {
        return $var->getValue($vars);
    }

    function _renderVarDisplay_email($form, $var, $vars)
    {
        $display_email = $email = $var->getValue($vars);

        if ($var->type->strip_domain && strpos($email, '@') !== false) {
            $display_email = str_replace(array('@', '.'),
                                         array(' (at) ', ' (dot) '),
                                         $email);
        }

        if ($var->type->link_compose) {
            $email_val = trim($email);

            // Format the address according to RFC822.
            $mailbox_host = explode('@', $email_val);
            if (!isset($mailbox_host[1])) {
                $mailbox_host[1] = '';
            }

            $name = $var->type->link_name;

            require_once 'Horde/MIME.php';
            $address = MIME::rfc822WriteAddress($mailbox_host[0], $mailbox_host[1], $name);

            // Get rid of the trailing @ (when no host is included in
            // the email address).
            $address = str_replace('@>', '>', $address);
            try {
                $mail_link = $GLOBALS['registry']->call('mail/compose', array(array('to' => addslashes($address))));
            } catch (Horde_Exception $e) {
                $mail_link = 'mailto:' . urlencode($address);
            }

            return Horde::link($mail_link, $email_val)
                . htmlspecialchars($display_email) . '</a>';
        } else {
            return nl2br(htmlspecialchars($display_email, ENT_QUOTES, $GLOBALS['registry']->getCharset()));
        }
    }

    function _renderVarDisplay_password($form, $var, $vars)
    {
        return '********';
    }

    function _renderVarDisplay_passwordconfirm($form, $var, $vars)
    {
        return '********';
    }

    function _renderVarDisplay_octal($form, $var, $vars)
    {
        return sprintf('0%o', octdec($var->getValue($vars)));
    }

    function _renderVarDisplay_boolean($form, $var, $vars)
    {
        return $var->getValue($vars) ? _("Yes") : _("No");
    }

    function _renderVarDisplay_enum($form, $var, $vars)
    {
        $values = $var->getValues();
        $value = $var->getValue($vars);
        if (count($values) == 0) {
            return _("No values");
        } elseif (isset($values[$value]) && $value != '') {
            return htmlspecialchars($values[$value], ENT_QUOTES, $GLOBALS['registry']->getCharset());
        }
    }

    function _renderVarDisplay_radio($form, $var, $vars)
    {
        $values = $var->getValues();
        if (count($values) == 0) {
            return _("No values");
        } elseif (isset($values[$var->getValue($vars)])) {
            return htmlspecialchars($values[$var->getValue($vars)], ENT_QUOTES, $GLOBALS['registry']->getCharset());
        }
    }

    function _renderVarDisplay_multienum($form, $var, $vars)
    {
        $values = $var->getValues();
        $on = $var->getValue($vars);
        if (!count($values) || !count($on)) {
            return _("No values");
        } else {
            $display = array();
            foreach ($values as $value => $name) {
                if (in_array($value, $on)) {
                    $display[] = $name;
                }
            }
            return htmlspecialchars(implode(', ', $display), ENT_QUOTES, $GLOBALS['registry']->getCharset());
        }
    }

    function _renderVarDisplay_set($form, $var, $vars)
    {
        $values = $var->getValues();
        $on = $var->getValue($vars);
        if (!count($values) || !count($on)) {
            return _("No values");
        } else {
            $display = array();
            foreach ($values as $value => $name) {
                if (in_array($value, $on)) {
                    $display[] = $name;
                }
            }
            return htmlspecialchars(implode(', ', $display), ENT_QUOTES, $GLOBALS['registry']->getCharset());
        }
    }

    function _renderVarDisplay_phone($form, &$var, &$vars)
    {
        global $registry;

        $number = $var->getValue($vars);
        $html = htmlspecialchars($number, ENT_QUOTES, $this->_charset);

        if ($number && $registry->hasMethod('telephony/dial')) {
            $url = $registry->call('telephony/dial', array($number));
            $label = sprintf(_("Dial %s"), $number);
            $html .= ' ' . Horde::link($url, $label) . Horde::img('phone.png', $label) . '</a>';
        }

        return $html;
    }

    function _renderVarDisplay_cellphone($form, &$var, &$vars)
    {
        global $registry;

        $html = $this->_renderVarDisplay_phone($form, $var, $vars);

        $number = $var->getValue($vars);
        if ($number && $registry->hasMethod('sms/compose')) {
            $url = $registry->link('sms/compose', array('to' => $number));
            $html .= ' ' . Horde::link($url, _("Send SMS")) . Horde::img('mobile.png', _("Send SMS")) . '</a>';
        }

        return $html;
    }

    function _renderVarDisplay_address($form, $var, $vars)
    {
        global $registry;

        $address = $var->getValue($vars);

        if (preg_match('/((?:A[BL]|B[ABDHLNRST]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[CHNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTWY]?|T[ADFNQRSW]|UB|W[ACDFNRSV]?|YO|ZE)\d(?:\d|[A-Z])? \d[A-Z]{2})/', $address, $postcode)) {
            /* UK postcode detected. */
            /* Multimap.co.uk generated map */
            $mapurl = 'http://www.multimap.com/map/browse.cgi?pc=' . urlencode($postcode[1]);
            $desc = _("Multimap UK map");
            $icon = 'map.png';
        } elseif (preg_match('/ACT|NSW|NT|QLD|SA|TAS|VIC|WA/', $address)) {
            /* Australian state detected. */
            /* Whereis.com.au generated map */
            $mapurl = 'http://www.whereis.com.au/whereis/mapping/geocodeAddress.do?';
            $desc = _("Whereis Australia map");
            $icon = 'map.png';
            /* Split out the address, line-by-line. */
            $addressLines = explode("\n", $address);
            for ($i = 0; $i < count($addressLines); $i++) {
                /* See if it's the street number & name. */
                if (preg_match('/(\d+\s*\/\s*)?(\d+|\d+[a-zA-Z])\s+([a-zA-Z ]*)/', $addressLines[$i], $lineParts)) {
                    $mapurl .= '&streetNumber=' . urlencode($lineParts[2]);
                    $mapurl .= '&streetName=' . urlencode($lineParts[3]);
                }
                /* Look for "Suburb, State". */
                if (preg_match('/([a-zA-Z ]*),?\s+' . $aus_state_regexp . '/', $addressLines[$i], $lineParts)) {
                    $mapurl .= '&suburb=' . urlencode($lineParts[1]);
                }
                /* Look for "State <4 digit postcode>". */
                if (preg_match('/(' . $aus_state_regexp . ')\s+(\d{4})/', $addressLines[$i], $lineParts)) {
                    $mapurl .= '&state=' . urlencode($lineParts[1]);
                }
            }
        } elseif (preg_match('/(.*)\n(.*)\s*,\s*(\w+)\.?\s+(\d+|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d)/', $address, $addressParts)) {
            /* American/Canadian address style. */
            /* Mapquest generated map */
            $mapurl = 'http://www.mapquest.com/maps/map.adp?size=big&zoom=7';
            $desc = _("MapQuest map");
            $icon = 'map.png';
            $country = null;
            if (!empty($addressParts[4]) && preg_match('|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d|', $addressParts[4])) {
                $country = 'CA';
            }
            if (!empty($addressParts[1])) {
                $mapurl .= '&address=' . urlencode($addressParts[1]);
            }
            if (!empty($addressParts[2])) {
                $mapurl .= '&city=' . urlencode($addressParts[2]);
            }
            if (!empty($addressParts[3])) {
                $mapurl .= '&state=' . urlencode($addressParts[3]);
            }
            if (!empty($addressParts[4])) {
                if ($country == 'CA') {
                    $mapurl .= '&country=CA';
                }
                $mapurl .= '&zipcode=' . urlencode($addressParts[4]);
            }

            /* Yahoo! generated map. */
            $mapurl2 = 'http://us.rd.yahoo.com/maps/home/submit_a/*-http://maps.yahoo.com/maps?srchtype=a&getmap=Get+Map&';
            $desc2 = _("Yahoo! map");
            $icon2 = 'map.png';
            if (!empty($addressParts[1])) {
                $mapurl2 .= '&addr=' . urlencode($addressParts[1]);
            }
            /* Give precedence to zipcode over city/state */
            if (empty($addressParts[4]) && !empty($addressParts[2]) && !empty($addressParts[3])) {
                $mapurl2 .= '&csz=' . urlencode($addressParts[2] . ' ' . $addressParts[3]);
            }
            if (!empty($addressParts[4])) {
                if (preg_match('|([a-zA-Z]\d[a-zA-Z])\s?(\d[a-zA-Z]\d)|', $addressParts[4], $pcParts)) {
                    $mapurl2 .= '&country=ca';
                    /* make sure the postal-code has a space */
                    $addressParts[4] = $pcParts[1] . ' ' . $pcParts[2];
                }
                $mapurl2 .= '&csz=' . urlencode($addressParts[4]);
            }

            /* Google generated map. */
            $mapurl3 = 'http://maps.google.com/maps?q=' . urlencode($addressParts[0]) . '&hl=en';
            $desc3 = _("Google Maps");
            $icon3 = 'map.png';

        } elseif (preg_match('/(.*?)\r?\n([A-Z]{1,3})-(\d{5})\s+(.*)/i', $address, $addressParts)) {
            /* European address style. */
            include 'Horde/Nls/Carsigns.php';
            $country = array_search(Horde_String::upper($addressParts[2]), $carsigns);

            /* Map24 generated map. */
            if (in_array($country, array('al', 'ad', 'am', 'az', 'be', 'ba',
                                         'bg', 'de', 'dk', 'ee', 'fo', 'fi',
                                         'fr', 'ge', 'gr', 'gb', 'ie', 'is',
                                         'it', 'hr', 'lv', 'li', 'lt', 'lu',
                                         'mt', 'mk', 'md', 'mc', 'nl', 'no',
                                         'pl', 'pt', 'ro', 'ru', 'se', 'ch',
                                         'cs', 'sk', 'si', 'es', 'cz', 'tr',
                                         'ua', 'hu', 'by', 'cy', 'at'))) {
                if (in_array($country, array('at', 'be', 'ch', 'de', 'dk',
                                             'es', 'fi', 'fr', 'it', 'nl',
                                             'no', 'se'))) {
                    $mirror = $country;
                } else {
                    $mirror = 'uk';
                }
                $mapurl = 'http://www.' . $mirror . '.map24.com/source/address/v2.0.0/cnt_nav_maplet.php?cid=validateaddr&country=' . $country;
                $desc = _("Map24 map");
                $icon = 'map_eu.png';
                if (!empty($addressParts[1])) {
                    $mapurl .= '&street=' . urlencode($addressParts[1]);
                }
                if (!empty($addressParts[3])) {
                    $mapurl .= '&zip=' . urlencode($addressParts[3]);
                }
                if (!empty($addressParts[4])) {
                    $mapurl .= '&city=' . urlencode($addressParts[4]);
                }
            }

            /* Mapquest generated map. */
            $mapurl2 = 'http://www.mapquest.com/maps/map.adp?country=' . Horde_String::upper($country);
            $desc2 = _("MapQuest map");
            $icon2 = 'map_eu.png';
            if (!empty($addressParts[1])) {
                $mapurl2 .= '&address=' . urlencode($addressParts[1]);
            }
            if (!empty($addressParts[3])) {
                $mapurl2 .= '&zipcode=' . urlencode($addressParts[3]);
            }
            if (!empty($addressParts[4])) {
                $mapurl2 .= '&city=' . urlencode($addressParts[4]);
            }
        }

        $html = nl2br(htmlspecialchars($var->getValue($vars), ENT_QUOTES, $GLOBALS['registry']->getCharset()));
        if (!empty($mapurl)) {
            $html .= '&nbsp;&nbsp;' . Horde::link(Horde::externalUrl($mapurl), $desc, null, '_blank') . Horde::img($icon, $desc) . '</a>';
        }
        if (!empty($mapurl2)) {
            $html .= '&nbsp;' . Horde::link(Horde::externalUrl($mapurl2), $desc2, null, '_blank') . Horde::img($icon2, $desc2) . '</a>';
        }
        if (!empty($mapurl3)) {
            $html .= '&nbsp;' . Horde::link(Horde::externalUrl($mapurl3), $desc3, null, '_blank') . Horde::img($icon3, $desc3) . '</a>';
        }

        return $html;
    }

    function _renderVarDisplay_date($form, $var, $vars)
    {
        return $var->type->getFormattedTime($var->getValue($vars));
    }

    function _renderVarDisplay_monthyear($form, $var, $vars)
    {
        return $vars->get($var->getVarName() . '[month]') . ', ' . $vars->get($var->getVarName() . '[year]');
    }

    function _renderVarDisplay_monthdayyear($form, $var, $vars)
    {
        $date = $var->getValue($vars);
        if ((is_array($date) && !empty($date['year']) &&
             !empty($date['month']) && !empty($date['day']))
            || (!is_array($date) && !empty($date))) {
            return $var->type->formatDate($date);
        }
        return '';
    }

    function _renderVarDisplay_invalid($form, $var, $vars)
    {
        return '<p class="form-error form-inline">'
                . htmlspecialchars($var->type->message, ENT_QUOTES, $GLOBALS['registry']->getCharset())
                . '</p>';
    }

    function _renderVarDisplay_link($form, $var, $vars)
    {
        $values = $var->getValues();
        if (!isset($values[0])) {
            $values = array($values);
        }


        $count = count($values);
        $html = '';
        for ($i = 0; $i < $count; $i++) {
            if (empty($values[$i]['url']) || empty($values[$i]['text'])) {
                continue;
            }
            if (!isset($values[$i]['target'])) {
                $values[$i]['target'] = '';
            }
            if (!isset($values[$i]['onclick'])) {
                $values[$i]['onclick'] = '';
            }
            if (!isset($values[$i]['title'])) {
                $values[$i]['title'] = '';
            }
            if (!isset($values[$i]['accesskey'])) {
                $values[$i]['accesskey'] = '';
            }
            if ($i > 0) {
                $html .= ' | ';
            }
            $html .= Horde::link($values[$i]['url'], $values[$i]['text'],
                        'widget', $values[$i]['target'], $values[$i]['onclick'],
                        $values[$i]['title'], $values[$i]['accesskey'])
                    . $values[$i]['text'] . '</a>';
        }

        return $html;
    }

    function _renderVarDisplay_dblookup($form, $var, $vars)
    {
        return $this->_renderVarDisplay_enum($form, $var, $vars);
    }

    function _renderVarDisplay_figlet($form, $var, $vars)
    {
        $figlet = new Text_Figlet();
        $result = $figlet->loadFont($var->type->font);
        if (is_a($result, 'PEAR_Error')) {
            return $result->getMessage();
        }

        return '<pre>' . $figlet->lineEcho($var->type->text) . '</pre>';
    }

    function _renderVarInput_selectFiles($form, $var, $vars)
    {
        /* Needed for gollem js calls */
        $html = sprintf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
                        'selectlist_selectid',
                        $var->type->selectid)
            . sprintf('<input type="hidden" id="%1$s" name="%1$s" />', 'actionID');

        /* Form field. */
        $html .= sprintf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
                         $var->getVarName(),
                         $var->type->selectid);

        /* Open window link. */
        $param = array($var->type->link_text,
                       $var->type->link_style,
                       $form->getName(),
                       $var->type->icon,
                       $var->type->selectid);
        $html .= "<p>\n" . $GLOBALS['registry']->call('files/selectlistLink', $param) . "</p>\n";

        if ($var->type->selectid) {
            $param = array($var->type->selectid);
            $files = $GLOBALS['registry']->call('files/selectlistResults', $param);
            if ($files) {
                $html .= '<ol>';
                foreach ($files as $id => $file) {
                    $dir = key($file);
                    $filename = current($file);
                    if ($GLOBALS['registry']->hasMethod('files/getViewLink')) {
                        $filename = basename($filename);
                        $url = $GLOBALS['registry']->call('files/getViewLink', array($dir, $filename));
                        $filename = Horde::link($url, _("Preview"), null, 'form_file_view') . htmlspecialchars(Horde_Util::realPath($dir . '/' . $filename), ENT_QUOTES, $this->_charset) . '</a>';
                    } else {
                        if (!empty($dir) && ($dir != '.')) {
                            $filename = $dir . '/' . $filename;
                        }
                        $filename = htmlspecialchars($filename, ENT_QUOTES, $this->_charset);
                    }
                    $html .= '<li>' . $filename . "</li>\n";
                }
                $html .= '</ol>';
            }
        }

        return $html;
    }

    function _selectOptions($values, $selectedValue = false, $htmlchars = true)
    {
        $result = '';
        $sel = false;
        foreach ($values as $value => $display) {
            if (!is_null($selectedValue) && !$sel && $value == $selectedValue
                && strlen($value) == strlen($selectedValue)) {
                $selected = ' selected="selected"';
                $sel = true;
            } else {
                $selected = '';
            }
            $result .= '        <option value="';
            $result .= ($htmlchars) ? htmlspecialchars($value, ENT_QUOTES, $GLOBALS['registry']->getCharset()) : $value;
            $result .= '"' . $selected . '>';
            $result .= ($htmlchars) ? htmlspecialchars($display) : $display;
            $result .= "</option>\n";
        }

        return $result;
    }

    function _multiSelectOptions($values, $selectedValues)
    {
        $result = '';
        $sel = false;
        foreach ($values as $value => $display) {
            if (@in_array($value, $selectedValues)) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $result .= " <option value=\""
                . htmlspecialchars($value, ENT_QUOTES, $GLOBALS['registry']->getCharset())
                . "\"$selected>" . htmlspecialchars($display) . "</option>\n";
        }

        return $result;
    }

    function _checkBoxes($name, $values, $checkedValues, $actions = '')
    {
        $result = '';
        if (!is_array($checkedValues)) {
            $checkedValues = array();
        }

        if (count($values) > 0) {
            $result .= "    <ul>\n";
        }

        $i = 0;
        foreach ($values as $value => $display) {
            $checked = in_array($value, $checkedValues) ? ' checked="checked"' : '';
            $result .= sprintf('        <li>'
                                .'<input id="%1$s%2$s" type="checkbox"'
                                    .' class="form-input-checkbox" name="%1$s[]"'
                                    .' value="%3$s"%4$s%5$s />'
                                .'&nbsp;<label class="form-inline" for="%1$s%2$s">'
                                    .'%6$s</label></li>'."\n",
                            $name,
                            $i,
                            $value,
                            $checked,
                            $actions,
                            $display);
            $i++;
        }

        if (count($values) > 0) {
            $result .= "    </ul>";
        }


        return $result;
    }

    function _radioButtons($name, $values, $checkedValue = null, $actions = '')
    {
        $result = '';

        if (count($values) > 0) {
            $result .= "    <ul>\n";
        }

        $i = 0;
        foreach ($values as $value => $display) {
            $checked = (!is_null($checkedValue) && $value == $checkedValue) ? ' checked="checked"' : '';
            $result .= sprintf('        <li>'
                                .'<input id="%1$s%2$s" type="radio"'
                                    .' class="form-input-checkbox" name="%1$s"'
                                    .' value="%3$s"%4$s%5$s />'
                                .'&nbsp;<label class="form-inline" for="%1$s%2$s">'
                                    .'%6$s</label></li>'."\n",
                            $name,
                            $i,
                            $value,
                            $checked,
                            $actions,
                            $display);
            $i++;
        }

        if (count($values) > 0) {
            $result .= "    </ul>";
        }

        return $result;
    }

    /**
     *
     * @access private
     * @author ?
     * @deprecated
     */
    function _genID($name, $fulltag = true)
    {
        return $fulltag ? 'id="' . htmlspecialchars($name) . '"' : $name;
    }

    /**
     * Returns script for an rendered variable. TODO: make this unobtrusive.
     *
     * @access private
     * @author ?
     * @return string html representing an attribute with action script as value,
     *         or and empty string, if the action is to happen window.onload
     */
    function _genActionScript($form, $action, $varname)
    {
        $html = '';
        $triggers = $action->getTrigger();
        if (!is_array($triggers)) {
            $triggers = array($triggers);
        }
        $js = $action->getActionScript($form, $this, $varname);
        foreach ($triggers as $trigger) {
            if ($trigger == 'onload') {
                $this->_onLoadJS[] = $js;
            } else {
                $html .= ' ' . $trigger . '="' . $js . '"';
            }
        }
        return $html;
    }

    /**
     * Returns scripts for an rendered variable. TODO: make this unobtrusive.
     *
     * @access private
     * @author ?
     * @return string html representing attributes with action script as values,
     *         or and empty string, if the actions are all to happen window.onload
     */
    function _getActionScripts($form, $var)
    {
        $actions = '';
        if ($var->hasAction()) {
            $varname = $var->getVarName();
            $action = &$var->_action;
            $triggers = $action->getTrigger();
            if (!is_array($triggers)) {
                $triggers = array($triggers);
            }
            $js = $action->getActionScript($form, $this, $varname);
            foreach ($triggers as $trigger) {
                if ($trigger == 'onload') {
                    $this->_onLoadJS[] = $js;
                } else {
                    $actions .= ' ' . $trigger . '="' . $js . '"';
                }
            }
        }
        return $actions;
    }

}
