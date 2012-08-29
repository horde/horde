<?php
/**
 * Horde Modal Form Renderer
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Ui_ModalFormRenderer extends Horde_Form_Renderer
{
    var $_stripedRows = false;

    /**
     * Constructor
     *
     * @param array $params  This is a hash of renderer-specific parameters.
     *                       Possible keys:
     *                       - 'varrenderer_driver': specifies the driver
     *                         parameter to Horde_Core_Ui_VarRenderer::factory().
     *                       - 'encode_title': @see $_encodeTitle
     */
    public function __construct($params = array())
    {
        parent::Horde_Form_Renderer($params);
    }

    function _renderSectionBegin(&$form, $section)
    {
        $open_section = $form->getOpenSection();
        if (is_null($open_section)) {
            $open_section = '__base';
        }
        printf('<div id="%s" style="display:%s;">',
               htmlspecialchars($form->getName() . '_section_' . $section),
               ($open_section == $section ? 'block' : 'none'));
    }

    function _renderSectionEnd()
    {
        echo '</div>';
    }

    /**
     * Implementation specific end function.
     */
    function _renderEnd()
    {
        echo '</div>' . $this->_varRenderer->renderEnd();
    }

    function _renderHeader($header, $error = '')
    {
        echo '<div class="horde-form-header">';
        echo $header;
        if (!empty($error)) {
            echo '<br /><span class="horde-form-error">' . $error . '</span>';
        }
        echo '</div>';
    }

    function _renderDescription($text)
    {
?><div><p class="horde-form-description" style="padding:8px"><?php echo $text ?></p></div>
<?php
    }

    function _renderSpacer()
    {
?><div>&nbsp;</div>
<?php
    }

    function _renderSubmit($submit, $reset)
    {
?><div>
  <?php if (!is_array($submit)) $submit = array($submit); foreach ($submit as $submitbutton): ?>
    <input class="button submit-button" name="submitbutton" type="submit" value="<?php echo $submitbutton ?>" />
  <?php endforeach; ?>
</div>
<?php
    }

    protected function _genId($var)
    {
        return htmlspecialchars(preg_replace('/[^A-Za-z0-9-_:.]+/', '_', $var->getVarName()));
    }

    function _renderVarInputBegin(&$form, &$var, &$vars)
    {
        $message = $form->getError($var);
        $isvalid = empty($message);
        echo "<div>\n";
        printf('  <label for="%s">%s</label>%s' . "\n",
               $this->_genId($var),
               $var->getHumanName(),
               $isvalid ? '' : ' <span class="horde-form-error">' . $message . '</span>');
        printf('</div><div%s>',
               ($var->isDisabled() ? ' class="horde-form-disabled"' : ''));
    }

    function _renderVarInputEnd(&$form, &$var, &$vars)
    {
        /* Display any description for the field. */
        if ($var->hasDescription()) {
            echo '<p class="horde-form-field-description">' . $var->getDescription() . '</p>';
        }

        echo "</div>\n";
    }

    function _sectionHeader($title, $extra = '')
    {
        if (strlen($title)) {
            echo '<div class="horde-form-header">';
            if (!empty($extra)) {
                echo '<span class="rightFloat">' . $extra . '</span>';
            }
            echo $this->_encodeTitle ? htmlspecialchars($title) : $title;
            echo '</div>';
        }
    }
}
