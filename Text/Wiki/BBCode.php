<?php
require_once(dirname(__FILE__).'/../Wiki.php');

require_once(dirname(__FILE__).'/BBCode/Parse.php');

class Text_Wiki_BBCode extends Text_Wiki {
    var $rules = array(
        'Prefilter',
        'Delimiter',
        'Code',
        'Plugin',
        'Function',
        'Html',
        'Raw',
        'Preformatted',
        'Include',
        'Embed',
        'Page',
        'Anchor',
        'Heading',
        'Toc',
        'Titlebar',
        'Horiz',
        'Break',
        'Blockquote',
        'List',
        'Deflist',
        'Table',
        'Box',
        'Image',
        'Phplookup',
        'Center',
        'Newline',
        'Paragraph',
        'Url',
        //'Freelink',
        'Colortext',
        'Font',
        'Strong',
        'Bold',
        'Emphasis',
        'Italic',
        'Underline',
        'Tt',
        'Superscript',
        'Subscript',
        'Specialchar',
        'Revise',
        'Interwiki',
        'Tighten'
    );

    function Text_Wiki_BBCode($rules = null) {
        parent::Text_Wiki($rules);
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)).'BBCode/Parse');
        $this->addPath('render', $this->fixPath(dirname(__FILE__)).'BBCode/Render');
    }

    function getTokens($rules = null, $originalIndex = false)
    {
        if (is_null($rules)) {
            return $this->tokens;
        } else {
            settype($rules, 'array');
            $result = array();
            foreach ($this->tokens as $key => $val) {
                if (in_array($val[0], $rules)) {
                    if ($originalIndex) {
                        $result[$key] = $val;
                    } else {
                        $result[] = $val;
                    }
                }
            }
            return $result;
        }
    }
}

?>
