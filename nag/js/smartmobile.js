/**
 * Base smartmobile application logic for Nag.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
var NagMobile = {

    clickHandler: function(e)
    {
        var elt = $(e.target);
        while (elt && elt != window.document && elt.parent().length) {
            if (elt.hasClass('nag-toggle-complete')) {
                NagMobile.toggleComplete(elt);
                return;
            }
            if (elt.hasClass('nag-task')) {
                NagMobile.getTask(elt);
                return;
            }
            elt = elt.parent();
        }
    },

    toggleComplete: function(elt)
    {
        HordeMobile.doAction(
            'smartmobileToggle',
            {
                task: elt.parent().jqmData('task'),
                tasklist: elt.parent().jqmData('tasklist')
            },
            function(r) { NagMobile.toggleCompleteCallback(r, elt) }
        );
    },

    toggleCompleteCallback: function(r, elt)
    {
        if (r.data == 'complete') {
            if (Nag.conf.showCompleted == 'incomplete' ||
                Nag.conf.showCompleted == 'future-incomplete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'check');
                elt.find('span.ui-icon').removeClass('ui-icon-nag-unchecked').addClass('ui-icon-check');
            }
        } else {
            if (Nag.conf.showCompleted == 'complete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'minus');
                elt.find('span.ui-icon').removeClass('ui-icon-check').addClass('ui-icon-nag-unchecked');
            }
        }
    },

    getTask: function(elt)
    {
        HordeMobile.doAction(
            'getTask',
            {
                'task': elt.closest('li').jqmData('task'),
                'tasklist': elt.closest('li').jqmData('tasklist')
            },
            function(r) { NagMobile.getTaskCallback(r, elt) }
        );
    },

    getTaskCallback: function(r, t)
    {

    },

    toList: function(l)
    {
        // @TODO: Pass the [smart]list to render.
        HordeMobile.doAction('listTasks',
            {},
            function (r) { NagMobile.listTasksCallback(r); }
        );
    },

    /**
     * Insert tasklist into the view.
     */
    insertTask: function(l, t)
    {
        var item, link;

        // @TODO: Figure out the icon/completed class mess.
        item = $('<li>').attr({ 'nag-task-id': t.id, 'data-icon': t.cp ? 'check' : 'nag-unchecked' });
        item.append(
            $('<a>').attr({ 'href': '#', 'data-rel': 'dialog', 'class': 'nag-task' }).append(
                $('<h3>').text(t.n)
            ).append(
                $('<p>').attr({ 'class': 'ui-li-aside' }).text(t.dd)
            ).append(
                $('<p>').text(t.de)
            )
        );

        item.append(
            $('<a>').attr({
            'href': '#',
            'class': 'nag-toggle-complete',
            }));
        item.jqmData('task', t.id).jqmData('tasklist', t.l)
        l.append(item);
    },

    listTasksCallback: function(r)
    {
        var list = $('<ul>').attr({ 'data-role': 'listview' });
        $.each(r.tasks, function(i, t) {
            NagMobile.insertTask(list, t);
        });
        $("#nag-list :jqmData(role='content')").append(list).trigger('create');
    },

    toView: function(e, d)
    {
        switch (d.options.parsedUrl.view) {
        case 'nag-list':
            NagMobile.toList();
            break;
        }
    },

    onDocumentReady: function()
    {
        $(document).bind('vclick', NagMobile.clickHandler);
        $(document).bind('pagebeforechange', NagMobile.toView);
    }

};

$(NagMobile.onDocumentReady);
