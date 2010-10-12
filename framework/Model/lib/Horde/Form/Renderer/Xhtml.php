<?php
/**
 */
class Horde_Form_Renderer_Xhtml extends Horde_Form_Renderer {

    protected $_enctype = 'multipart/form-data';

    function _renderSectionTabs($form)
    {
        /* If javascript is not available, do not render tabs. */
        if (!$GLOBALS['browser']->hasFeature('javascript')) {
            return;
        }

        $open_section = $form->getOpenSection();

        /* Add the javascript for the toggling the sections. */
        Horde::addScriptFile('form_sections.js', 'horde');
        echo '<script type="text/javascript">' . "\n" .
            sprintf('var sections_%1$s = new Horde_Form_Sections(\'%1$s\', \'%2$s\');',
                    $form->getName(),
                    $open_section) .
            '</script>';

        /* Loop through the sections and print out a tab for each. */
        echo "<div class=\"tabset\">\n";
        $js = array();
        foreach ($form->_sections as $section => $val) {
            $class = ($section == $open_section) ? ' class="activeTab"' : '';
            $tabid = htmlspecialchars($form->getName() . '_tab_' . $section);
            $js[$linkid] = sprintf('sections_%s.toggle(\'%s\'); return false;"',
                                   $form->getName(),
                                   $section);
            printf('<div%s id="%s"><a href="#" id="%s">%s%s</a></div>' . "\n",
                   $class,
                   $tabid,
                   '_tablink_' . $section,
                   $form->getSectionImage($section),
                   $form->getSectionDesc($section));
        }
        echo "</div>\n";

        // This doesn't help a whole lot now, but if there is a way to
        // buffer output of JS, then we can keep JS separated from
        // markup, whereas before the onclicks were assigned as an
        // HTML attribute.
        echo '<script type="text/javascript">' . "\n";
        echo 'if (document.getElementById) {' . "\n";
        echo '    addEvent(window, \'load\', function() {' . "\n";
        foreach ($js as $id => $onclick) {
            $line = '
if (document.getElementById(%1$s)){
    document.getElementById(%1$s).onclick = function() {
        %2$s
    };
}';
            printf($line, $id, $onclick);
        }
        echo '    });}</script>' . "\n";
    }

    function _renderSectionBegin($form, $section)
    {
        // Stripe alternate rows if that option is turned on.
        if ($this->_stripedRows) {
            Horde::addScriptFile('stripe.js', 'horde');
            $class = 'striped';
        } else {
            $class = '';
        }

        $open_section = $form->getOpenSection();
        if (empty($open_section)) {
            $open_section = '__base';
        }

        // include a general class name for styling purposes. also helps select
        // ULs, which only get a className currently if they are striped.
        printf('<fieldset id="%s" class="%s form-section %s">',
               htmlspecialchars($form->getName() . '_section_' . $section),
               ($open_section == $section ? 'form-sectionshown' : 'form-sectionhidden'),
               $class);
    }

    function _renderSectionEnd()
    {
        echo '</fieldset>';
    }

    function preserveVarByPost($vars, $varname, $alt_varname = '')
    {
        $value = $vars->getExists($varname, $wasset);

        if ($alt_varname) {
            $varname = $alt_varname;
        }

        if ($wasset) {
            $this->_preserveVarByPost($varname, $value);
        }
    }

    function _preserveVarByPost($varname, $value)
    {
        if (is_array($value)) {
            foreach ($value as $id => $val) {
                $this->_preserveVarByPost($varname . '[' . $id . ']', $val);
            }
        } else {
            $varname = htmlspecialchars($varname);
            $value = htmlspecialchars($value);
            printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />'."\n",
                   $varname,
                   $value);
        }
    }

    function listFormVars($form)
    {
        $variables = $form->getVariables(true, true);
        $vars = array();
        if ($variables) {
            foreach ($variables as $var) {
                if (is_object($var)) {
                    if (!$var->isReadonly()) {
                        $vars[$var->getVarName()] = 1;
                    }
                } else {
                    $vars[$var] = 1;
                }
            }
        }
        echo '<input type="hidden" name="_formvars" value="'
            . htmlspecialchars(serialize($vars))
            . '" />';
    }

    public function renderActive($form, $action, $method = 'get', $enctype = null, $focus = true)
    {
        $this->_name = $form->getName();

        echo "<form class=\"horde-form\" action=\"$action\" method=\"$method\""
            . (empty($this->_name) ? '' : ' id="' . $this->_name. '"')
            . (is_null($this->_enctype) ? '' : ' enctype="' . $this->_enctype . '"')
            . ">\n";
        echo Horde_Util::formInput();

        $this->listFormVars($form);

        if (!empty($this->_name)) {
            $this->_preserveVarByPost('formname', $this->_name);
        }

        if ($form->useToken()) {
            $this->_preserveVarByPost($this->_name . '_formToken', Horde_Token::generateId($this->_name));
        }

        if (count($form->getSections())) {
            $this->_preserveVarByPost('__formOpenSection', $form->getOpenSection());
        }

        $vars = $form->getVars();

        $variables = $form->getVariables();
        foreach ($variables as $var) {
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                $this->preserveVarByPost($vars, $varname, '__old_' . $varname);
            }
        }

        foreach ($form->getHiddenVariables() as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }

        $this->_renderBeginActive($form->getTitle());
        $this->_renderForm($form, true);
        $this->submit($this->_submit, $this->_reset);

        echo "\n</fieldset>\n</form>\n";
        if ($focus && !empty($this->_firstField)) {
            echo '<script type="text/javascript">
try {
    document.getElementById("'. $this->_firstField .'").focus();
} catch (e) {}
</script>
';
        }
    }

    function renderInactive($form)
    {
        $this->_name = $form->getName();
        $this->_renderBeginInactive($form->getTitle());
        $this->_renderForm($form, false);
    }

    function _renderForm($form, $active)
    {
        $vars = $form->getVars();

        /* If help is present 3 columns are needed. */
        $this->_cols = $form->hasHelp() ? 3 : 2;

        $variables = $form->getVariables(false);

        /* Check for a form token error. */
        if (($tokenError = $form->getError('_formToken')) !== null) {
            printf('<p class="form-error">%s</p>'."\n", $tokenError);
        }

        $error_section = null;
        reset($variables);
        if (count($variables) > 1 || key($variables) != '__base') {
            $this->_renderSectionTabs($form);
        }

        foreach ($variables as $section_id => $section) {
            $this->_renderSectionBegin($form, $section_id);
            foreach ($section as $var) {
                switch (get_class($var->type)) {
                case 'Horde_Form_Type_header':
                    $this->_renderHeader($var->getHumanName(), $form->getError($var->getVarName()));
                    break;

                case 'Horde_Form_Type_description':
                    $this->_renderDescription($var->getHumanName());
                    break;

                case 'Horde_Form_Type_spacer':
                    $this->_renderSpacer();
                    break;

                default:
                    $isInput = ($active && !$var->isReadonly());
                    $format = $isInput ? 'Input' : 'Display';
                    $begin = "_renderVar${format}Begin";
                    $end = "_renderVar${format}End";

                    $this->$begin($form, $var);
                    echo $this->_varRenderer->render($form, $var, $vars, $isInput);
                    $this->$end($form, $var);

                    /* Print any javascript if actions present. */
                    if ($var->hasAction()) {
                        $var->_action->printJavaScript();
                    }

                    /* Keep first field. */
                    if ($active && empty($this->_firstField) && !$var->isReadonly()
                        && !$var->isHidden()) {
                        $this->_firstField = $var->getVarName();
                    }

                    /* Keep section with first error. */
                    if (is_null($error_section) && $form->getError($var)) {
                        $error_section = $section_id;
                    }
                }
            }

            $this->_renderSectionEnd();
        }

        if (!is_null($error_section)) {
            echo '<script type="text/javascript">' .
                "\n" . sprintf('sections_%s.toggle(\'%s\');',
                               $form->getName(),
                               $error_section) .
                "\n</script>\n";
        }

        echo '</fieldset>' . $this->_varRenderer->renderEnd();
    }

    function submit($submit = null, $reset = false)
    {
        if (is_null($submit) || empty($submit)) {
            $submit = $this->_dict->t("Submit");
        }
        if ($reset === true) {
            $reset = $this->_dict->t("Reset");
        }
        $this->_renderSubmit($submit, $reset);
    }

    /**
     * Implementation specific begin function.
     */
    function _renderBeginActive($name)
    {
        echo '<fieldset class="horde-form" id="fieldset_' . htmlspecialchars($this->_name) . '">'."\n";
        if ($this->_showHeader) {
            $this->_renderSectionHeader($name);
        }
        if ($this->_requiredLegend) {
            echo '<div class="form-error-example">' . $this->_requiredMarker
                . ' &#61; ' . $this->_dict->t("Required Field") . '</div>'."\n";
        }
    }

    /**
     * Implementation specific begin function.
     */
    function _renderBeginInactive($name)
    {
        echo '<fieldset class="horde-form" id="fieldset_' . htmlspecialchars($this->_name) . '">';
        if ($this->_showHeader) {
            $this->_renderSectionHeader($name);
        }
    }

    function _renderHeader($header, $error = '')
    {
        echo '<div class="form-header">'. $header . '</div>';
        if (!empty($error)) {
            echo '<div class="form-error">'. $error . '</div>';
        }
    }

    function _renderDescription($description)
    {
        echo '<div class="form-description">'. $description . '</div>';
    }

    function _renderSpacer()
    {
        // TODO: fix this later so we're not inserting nonsemantic elements just for spacing
        // ... maybe append form-spacer to class of next or previous element
        echo '<div class="form-spacer">&nbsp;</div>';
    }

    function _renderSubmit($submit, $reset)
    {
        echo '<fieldset class="form-buttons">'."\n";
        if (!is_array($submit)) $submit = array($submit);
        foreach ($submit as $submitbutton) {
            echo '<input class="button" name="submitbutton" type="submit"';
            // allow for default-value submit buttons (e.g. _renderSubmit(""))
            if (!empty($submitbutton)) {
                echo ' value="'. $submitbutton .'"';
            }
            echo ' />'."\n";
        }
        if (!empty($reset)) {
            echo '<input class="button" name="resetbutton" type="reset"
                value="'. $reset .'" />'."\n";
        }
    }

    /**
     * Renders the beginning of an writeable form entry, including the label
     * and any form error related to this variable.
     *
     * @access private
     * @author Matt Warden <mwarden@gmail.com>
     * @author  Robert E. Coyle <robertecoyle@hotmail.com>
     */
    function _renderVarInputBegin($form, $var, $readonly = false)
    {
        // get error message for variable, if any
        $message = $form->getError($var);
        // if no message, then no error
        $isvalid = empty($message);

        $classnames = 'form-input'
            . (!$isvalid ? ' form-error' : '')
            . ($var->isRequired() ? ' form-required' : '');

        echo '<div class="', $classnames, '">';

        if (!$isvalid) {
            echo '<p class="form-error">', $message, '</p>', "\n";
        }

        printf('<label%s>%s</label>',
            ($readonly ? '' : ' for="'. $var->getVarName() .'"'),
            $var->getHumanName());
    }

    /**
     * Renders the end of an writeable form entry, including any form notes
     * and help info.
     *
     * @access private
     * @author Matt Warden <mwarden@gmail.com>
     * @author  Robert E. Coyle <robertecoyle@hotmail.com>
     */
    function _renderVarInputEnd($form, $var)
    {
        /* Display any help for the field. */
        if ($var->hasHelp()) {
            global $registry;
            if (isset($registry) && is_a($registry, 'Registry')) {
                $help = Horde_Help::link($GLOBALS['registry']->getApp(), $var->getHelp());
            } else {
                $help = @htmlspecialchars($var->getHelp());
            }
            echo '<p class="form-hint">', $help, '</p>';
        }

        /* Display any description for the field. */
        if ($var->hasDescription()) {
            echo '<div class="form-note"><p>', $var->getDescription(), '</p></div>';
        } else {
            echo '<br class="clear" />';
        }

        echo '</div>';
    }

    /**
     * Renders the beginning of a readonly form entry.
     *
     * @access private
     * @author Matt Warden <mwarden@gmail.com>
     * @author  Robert E. Coyle <robertecoyle@hotmail.com>
     */
    function _renderVarDisplayBegin($form, $var)
    {
        return $this->_renderVarInputBegin($form, $var, true);
    }

    /**
     * Renders the end of a readonly form entry. Help and notes are not
     * applicable.
     *
     * @access private
     * @author Matt Warden <mwarden@gmail.com>
     * @author  Robert E. Coyle <robertecoyle@hotmail.com>
     */
    function _renderVarDisplayEnd()
    {
        echo '</div>';
    }

    /**
     * Renders the header for the section.
     *
     * @access private
     * @author Matt Warden <mwarden@gmail.com>
     * @author  Robert E. Coyle <robertecoyle@hotmail.com>
     * @param string $title section header title
     */
    function _renderSectionHeader($title)
    {
        if (!empty($title)) {
            echo "\n".'<legend>';
            echo $this->_encodeTitle ? htmlspecialchars($title) : $title;
            echo '</legend>'."\n";
        }
    }

}
