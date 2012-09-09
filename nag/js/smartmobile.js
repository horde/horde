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
        switch (r.data) {
        case 'complete':
            if (Nag.conf.showCompleted == 'incomplete' ||
                Nag.conf.showCompleted == 'future-incomplete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'check')
                    .find('span.ui-icon')
                    .removeClass('ui-icon-nag-unchecked')
                    .addClass('ui-icon-check');
            }
            break;

        default:
            if (Nag.conf.showCompleted == 'complete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'minus')
                    .find('span.ui-icon')
                    .removeClass('ui-icon-check')
                    .addClass('ui-icon-nag-unchecked');
            }
        }
    },

    getTask: function(d)
    {
        var parsed = d.options.parsedUrl;
        HordeMobile.changePage('nag-task-view', d);

        HordeMobile.doAction(
            'getTask',
            {
                'task': parsed.params.task_id,
                'tasklist': parsed.params.tasklist
            },
            function(r) { NagMobile.getTaskCallback(r) }
        );
    },

    getTaskCallback: function(r)
    {
        var task = r.task,
            f = $('form')[0];

        f.reset();
        $("#task_title").val(task.n);
        $("#task_desc").val(task.de);
        $("#task_assignee").val(task.as);
        $("task_private").prop("checked", task.pr).checkboxradio("refresh");
        // @TODO: Style differently if overdue?
        $("#task_due").val(Date.parse(task.dd).toString('yyyy-MM-dd'));
        $("#task_start").val(task.s);
        $("#task_priority").val(task.pr);
        $("#task_completed").prop("checked", task.cp).checkboxradio("refresh");
        $("#task_estimate").val(task.e);
    },

    toLists: function(d)
    {
        $("#nag-lists :jqmData(role='content') ul").remove();
        HordeMobile.changePage('nag-lists', d);
        HordeMobile.doAction('getTaskLists',
            {},
            function(r) { NagMobile.getTasklistsCallback(r); }
        );
    },

    getTasklistsCallback: function(r)
    {
        var list = $('<ul>').attr({ 'data-role': 'listview' });

        $.each(r.tasklists, function(i, l) {
            NagMobile.insertTasklist(list, l);
        });

        $("#nag-lists :jqmData(role='content')").append(list).trigger('create');
    },

    insertTasklist: function(el, l)
    {
        var item, url;

        url = HordeMobile.createUrl('nag-list', { 'tasklist': l.id });
        list = $('<li>').append(
            $('<a>').attr({ 'href': url, 'class': 'nag-tasklist' }).append(
                $('<h3>').text(l.name)
            )
        );

        el.append(list);
    },

    toList: function(d)
    {
        var params = d.options.parsedUrl.params;

        $("#nag-list :jqmData(role='content') ul").remove();
        if (!params.tasklist) {
            params.tasklist = null;
        }
        // @TODO: Pass the [smart]list to render.
        HordeMobile.doAction(
            'listTasks',
            { 'tasklist': params.tasklist },
            function(r) { NagMobile.listTasksCallback(r); }
        );
        HordeMobile.changePage('nag-list', d);
    },

    listTasksCallback: function(r)
    {
        var list = $('<ul>').attr({ 'data-role': 'listview' });

        $.each(r.tasks, function(i, t) {
            NagMobile.insertTask(list, t);
        });

        $("#nag-list :jqmData(role='content')").append(list).trigger('create');
    },

    /**
     * Insert task into the view.
     */
    insertTask: function(l, t)
    {
        var url = HordeMobile.createUrl('nag-task-view', {
            'task_id': t.id,
            'tasklist': t.l
        });

        l.append($('<li>').jqmData('icon', t.cp ? 'check' : 'nag-unchecked')
            .append(
                $('<a>').attr({ href: url })
                    .addClass('nag-task')
                    .append(
                        $('<h3>').text(t.n)
                    ).append(
                        $('<p>').addClass('ui-li-aside')
                            .text(t.dd)
                    ).append(
                        $('<p>').text(t.de)
                    )
            ).append(
                $('<a>').attr({ 'href': '#' })
                    .addClass('nag-toggle-complete')
            ));
    },

    toView: function(e, d)
    {
        switch (d.options.parsedUrl.view) {
        case 'nag-list':
            NagMobile.toList(d);
            e.preventDefault();
            break;

        case 'nag-task-view':
            NagMobile.getTask(d);
            e.preventDefault();
            break;

        case 'nag-lists':
            NagMobile.toLists(d);
            e.preventDefault();
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
