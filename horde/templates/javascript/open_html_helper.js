/**
 * Horde Html Helper Javascript Class
 *
 * Provides the javascript class insert html tags by clicking on icons.
 *
 * The helpers available:
 *      emoticons - for inserting emoticons strings
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Horde
 * @todo Add handling for font tags, tables, etc.
 */

var targetElement;

function openHtmlHelper(type, target)
{
    var lay = document.getElementById('htmlhelper_' + target);
    targetElement = document.getElementById(target);

    if (lay.style.display == 'block') {
        lay.style.display = 'none';
        return false;
    }

    if (lay.firstChild) {
        lay.removeChild(lay.firstChild);
    }

    var table = document.createElement('TABLE');
    var tbody = document.createElement('TBODY');
    table.appendChild(tbody);
    table.cellSpacing = 0;
    table.border = 0;

    if (type == 'emoticons') {
        row = document.createElement('TR');
        cell = document.createElement('TD');
        <?php $filter = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->getFilter('emoticons'); $icons = array_flip($filter->getIcons()); foreach ($icons as $icon => $string): ?>
        link = document.createElement('A');
        link.href = '#';
        link.onclick = function() {
            targetElement.value = targetElement.value + '<?php echo $string ?>' + ' ';
            return false;
        }
        cell.appendChild(link);
        img = document.createElement('IMG')
        img.src = '<?php echo Horde_Themes::img('emoticons/' . $icon . '.png') ?>';
        img.align = 'middle';
        img.border = 0;
        link.appendChild(img);
        <?php endforeach; ?>
        row.appendChild(cell);
        tbody.appendChild(row);
        table.appendChild(tbody);
    }

    lay.appendChild(table);
    lay.style.display = 'block';
}
