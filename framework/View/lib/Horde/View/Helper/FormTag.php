<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_FormTag extends Horde_View_Helper_Base
{
    public function formTag($urlForOptions = array(), $options = array()) // , *parameters_for_url
    {
        $htmlOptions = $this->htmlOptionsForForm($urlForOptions, $options );  // , *parameters_for_url
        return $this->formTagHtml($htmlOptions);
    }

    public function endFormTag()
    {
        return '</form>';
    }

    public function selectTag($name, $optionTags = null, $options = array())
    {
        return $this->contentTag('select', $optionTags,
                                 array_merge(array('name' => $name, 'id' => $name), $options));
    }

    public function textFieldTag($name, $value = null, $options = array())
    {
        return $this->tag('input', array_merge(array('type'  => 'text',
                                                     'name'  => $name,
                                                     'id'    => $name,
                                                     'value' => $value),
                                               $options));
    }

    public function hiddenFieldTag($name, $value = null, $options = array())
    {
        return $this->textFieldTag($name, $value, array_merge($options, array('type' => 'hidden')));
    }

    public function fileFieldTag($name, $options = array())
    {
        return $this->textFieldTag($name, null, array_merge($options, array('type' => 'file')));
    }

    public function passwordFieldTag($name = 'password', $value = null, $options = array())
    {
        return $this->textFieldTag($name, $value, array_merge($options, array('type' => 'password')));
    }

    public function textAreaTag($name, $content = null, $options = array())
    {
        if (isset($options['size'])) {
            $size = $options['size'];
            unset($options['size']);
            if (strpos($size, 'x') !== false) {
                list($options['cols'], $options['rows']) = explode('x', $size);
            }
        }

        return $this->contentTag('textarea', $content,
                                 array_merge(array('name' => $name, 'id' => $name), $options));
    }

    public function checkBoxTag($name, $value = '1', $checked = false, $options = array())
    {
        $htmlOptions = array_merge(array('type'  => 'checkbox',
                                         'name'  => $name,
                                         'id'    => $name,
                                         'value' => $value,
                                         'checked' => $checked), $options);

        return $this->tag('input', $htmlOptions);
    }

    public function radioButtonTag($name, $value, $checked = false, $options = array())
    {
        $prettyTagValue = preg_replace('/\s/', '_', $value);
        $prettyTagValue = strtolower(preg_replace('/(?!-)\W/', '', $prettyTagValue));

        $htmlOptions = array_merge(array('type'  => 'radio',
                                         'name'  => $name,
                                         'id'    => "{$name}_{$prettyTagValue}",
                                         'value' => $value,
                                         'checked' => $checked), $options);

        return $this->tag('input', $htmlOptions);
    }

    public function submitTag($value = 'Save changes', $options = array())
    {
        if (isset($options['disableWith'])) {
            $disableWith = $options['disableWith'];
            unset($options['disableWith']);

            $options['onclick'] = implode(';', array(
                "this.setAttribute('originalValue', this.value)",
                "this.disabled=true",
                "this.value='$disableWith'",
                "{$options['onclick']}",
                "result = (this.form.onsubmit ? (this.form.onsubmit() ? this.form.submit() : false) : this.form.submit())",
                "if (result == false) { this.value = this.getAttribute('originalValue'); this.disabled = false }",
                "return result"
            ));
        }

        return $this->tag('input', array_merge(array('type' => 'submit', 'name' => 'commit', 'value' => $value),
                                               $options));
    }

    public function imageSubmitTag($source, $options = array())
    {
        // source is passed to Horde_View_Helper_Asset->imagePath
        return $this->tag('input', array_merge(array('type' => 'image',
                                                     'src'  => $this->imagePath($source)),
                                               $options));
    }

    private function extraTagsForForm($htmlOptions)
    {
        $method = isset($htmlOptions['method']) ? strtolower($htmlOptions['method']) : '';
        if ($method == 'get') {
            $htmlOptions['method'] = 'get';
            return array('', $htmlOptions);
        } else if ($method == 'post' || $method == '') {
            $htmlOptions['method'] = 'post';
            return array('', $htmlOptions);
        } else {
            $htmlOptions['method'] = 'post';
            $extraTags = $this->contentTag('div',
                             $this->tag('input', array('type'  => 'hidden', 'name'  => '_method',
                                                       'value' => $method)), array('style' => 'margin:0;padding:0'));
            return array($extraTags, $htmlOptions);
        }

    }

    private function formTagHtml($htmlOptions)
    {
        list($extraTags, $htmlOptions) = $this->extraTagsForForm($htmlOptions);
        return $this->tag('form', $htmlOptions, true) . $extraTags;
    }

    /** @todo url_for */
    private function htmlOptionsForForm($urlForOptions, $options)
    {
        if (isset($options['multipart'])) {
            unset($options['multipart']);
            $options['enctype'] = 'multipart/form-data';
        }

        $options['action'] = $this->urlFor($urlForOptions); // , *parameters_for_url
        // @todo :
        // html_options["action"]  = url_for(url_for_options, *parameters_for_url)

        return $options;
    }

}
