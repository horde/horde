<?php

class Text_Wiki_Render_Xhtml_Heading extends Text_Wiki_Render {

    var $conf = array(
        'css_h1' => null,
        'css_h2' => null,
        'css_h3' => null,
        'css_h4' => null,
        'css_h5' => null,
        'css_h6' => null
    );

    function token($options)
    {
        static $jsOutput = false;
        // get nice variable names (id, type, level)
        extract($options);

        switch($type) {
        case 'start':
            $css = $this->formatConf(' class="%s"', "css_h$level");
            return '
<h'.$level.$css.' id="'.$id.'"'.($collapse !== null ? ' onclick="hideTOC(\''.$id.'\');"' : '').'>';

        case 'end':
            return '</h'.$level.'>
'.($collapse !== null ? '<a id="'.$id.'__link" href="javascript:void();" onclick="hideTOC(\''.$id.'\')">['.($collapse ? '+' : '-').']</a>
' : '');
        case 'startContent':
            if ($collapse !== null) {
                if ($jsOutput) {
                    $js = '';
                } else {
                    $js = '
<script language="javascript">
function hideTOC(id) {
    div = document.getElementById(id+"__content");
    link = document.getElementById(id+"__link");
    if (div.style.display == "none") {
        div.style.display = "";
        link.innerHTML = "[-]";
    } else {
        div.style.display = "none";
        link.innerHTML = "[+]";
    }
}
</script>
';
                }
            } else {
                $js = '';
            }
            return  $js.'
<div style="'.($collapse === true ? 'display: none; ' : '').'padding: 0px; margin: 0px; border: none;" id="'.$id.'__content">
';
        case 'endContent':
            return '
</div>
';
        }
    }
}
?>
