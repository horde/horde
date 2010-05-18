<?php
/**
 *
 * @package Pastie
 */

// This file is named LibGeshi to avoid recursive includes/requires.
class Pastie_Highlighter_TextHighlighter extends Pastie_Highlighter {
    public static function output($text, $syntax = 'none') {
        if ($syntax == 'none') {
            return '<pre>' . $text . '</pre>';
        } else {
            // Since we may be coming from another syntax highlighter,
            // we'll try upcasing the syntax name and hope we get lucky.
            $syntax = strtoupper($syntax);
            $highlighter = Text_Highlighter::factory($syntax);
            if ($highlighter instanceof PEAR_Error) {
                throw new Horde_Exception_Prior($highlighter);
            }
            $renderer = new Text_Highlighter_Renderer_Html(array(
                "numbers" => HL_NUMBERS_LI
            ));
            if ($renderer instanceof PEAR_Error) {
                throw new Horde_Exception_Prior($renderer);
            }
            $highlighter->setRenderer($renderer);
            return $highlighter->highlight($text);
        }
    }

    public static function getSyntaxes()
    {
        return array(
            "ABAP",
            "CPP",
            "CSS",
            "DIFF",
            "DTD",
            "Generator",
            "HTML",
            "JAVA",
            "JAVASCRIPT",
            "MYSQL",
            "PERL",
            "PHP",
            "PYTHON",
            "RUBY",
            "SH",
            "SQL",
            "VBSCRIPT",
            "XML"
        );
    }
}