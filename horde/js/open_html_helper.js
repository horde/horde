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

var Horde_Html_Helper = {

    iconlist: [],
    targetElement: null,

    open: function(type, target)
    {
        var cell, row, table, tbody,
            lay = $('htmlhelper_' + target);
        this.targetElement = $(target);

        if (lay.getStyle('display') == 'block') {
            lay.hide();
            return false;
        }

        if (lay.firstChild) {
            lay.removeChild(lay.firstChild);
        }

        tbody = new Element('TBODY');
        table = new Element('TABLE', { border: 0, cellSpacing: 0 }).insert(tbody);

        if (type == 'emoticons') {
            row = new Element('TR');
            cell = new Element('TD');

            iconlist.each(function(i) {
                var link =
                    new Element('A', { href: '#' }).insert(
                        new Element('IMG', { align: 'middle', border: 0, src: i[0] })
                    );
                cell.appendChild(link);

                link.observe('click', function(e) {
                    this.targetElement.setValue($F(this.targetElement) + i[1] + ' ');
                    e.stop();
                }.bindAsEventListener(this));
            });

            row.insert(cell);
            tbody.insert(row);
            table.insert(tbody);
        }

        lay.insert(table).setStyle({ display: 'block' });
    }
};
