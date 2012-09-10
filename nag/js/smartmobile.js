/**
 * Base smartmobile application logic for Nag.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
var NagMobile = {

    tasklists: {},

    tasks: {},

    currentList: undefined,

    toggleComplete: function(d)
    {
        var parsed = d.options.parsedUrl;

        HordeMobile.doAction(
            'smartmobileToggle',
            {
                task: parsed.params.task_id,
                tasklist: parsed.params.tasklist
            },
            function(r) { NagMobile.toggleCompleteCallback(r, d.options.data) }
        );
    },

    toggleCompleteCallback: function(r, elt)
    {
        switch (r.data) {
        case 'complete':
            NagMobile.tasks[elt.jqmData('task_id')].cp = true;
            if (Nag.conf.showCompleted == 'incomplete' ||
                Nag.conf.showCompleted == 'future-incomplete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'check')
                    .find('span.ui-icon')
                    .removeClass('ui-icon-nag-unchecked')
                    .addClass('ui-icon-check');
                NagMobile.styleTask(elt, NagMobile.tasks[elt.jqmData('task_id')]);
            }
            break;

        default:
            NagMobile.tasks[elt.jqmData('task_id')].cp = false;
            if (Nag.conf.showCompleted == 'complete') {
                // Hide the task
                elt.parent().remove();
            } else {
                elt.jqmData('icon', 'minus')
                    .find('span.ui-icon')
                    .removeClass('ui-icon-check')
                    .addClass('ui-icon-nag-unchecked');
                NagMobile.styleTask(elt, NagMobile.tasks[elt.jqmData('task_id')]);
            }
        }
    },

    getTask: function(d)
    {
        var parsed = d.options.parsedUrl;
        HordeMobile.changePage('nag-taskform-view', d);

        HordeMobile.doAction(
            'getTask',
            {
                task: parsed.params.task_id,
                tasklist: parsed.params.tasklist
            },
            NagMobile.getTaskCallback
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
        HordeMobile.changePage('nag-lists', d);
        HordeMobile.doAction(
            'getTaskLists',
            {},
            NagMobile.getTasklistsCallback
        );
    },

    getTasklistsCallback: function(r)
    {
        var list = $('#nag-lists :jqmData(role="listview")'),
            count = 0;

        list.empty();

        $.each(r.tasklists, function(i, l) {
            count = count + l.count;
            NagMobile.insertTasklist(list, l, false);
        });

        NagMobile.insertTasklist(
            list,
            {
                'name': Nag.strings.all,
                'count': count
            },
            true
        );
        list.listview('refresh');
    },

    insertTasklist: function(el, l, top)
    {
        var url = HordeMobile.createUrl('nag-list', { tasklist: l.id }),
            list;

        NagMobile.tasklists[l.id] = l.name;
        list = $('<li>').append(
            $('<a>').attr({ href: url })
                .addClass('nag-tasklist')
                .append($('<h3>').text(l.name))
                .append($('<span>').addClass('ui-li-count' + (l.overdue ? ' overdue' : '')).text(l.count))
        );

        if (top) {
            el.prepend(list);
        } else {
            el.append(list);
        }
    },

    toList: function(d)
    {
        var params = d.options.parsedUrl.params;

        HordeMobile.doAction(
            'listTasks',
            { tasklist: params.tasklist },
            NagMobile.listTasksCallback
        );
        $('#nag-list .smartmobile-title')
            .text(NagMobile.tasklists[params.tasklist]);
        NagMobile.currentList = params.tasklist;
        HordeMobile.changePage('nag-list', d);
    },

    listTasksCallback: function(r)
    {
        var list = $('#nag-list :jqmData(role="listview")');

        list.empty();
        NagMobile.tasks = {};
        $.each(r.tasks, function(i, t) {
            NagMobile.insertTask(list, t);
        });

        list.listview('refresh');
    },

    /**
     * Insert task into the view.
     */
    insertTask: function(l, t)
    {
        var params = {
            task_id: t.id,
            tasklist: t.l
        }, item;

        NagMobile.tasks[t.id] = t;

        item = $('<li>').jqmData('icon', t.cp ? 'check' : 'nag-unchecked')
            .append(
                $('<a>').attr({
                    href: HordeMobile.createUrl('nag-taskform-view', params)
                }).addClass('nag-task')
                .append(
                    $('<h3>').text(t.n)
                ).append(
                    $('<p>').addClass('ui-li-aside')
                        .text(t.dd)
                ).append(
                    $('<p>').text(t.de)
                )
            ).append(
                $('<a>').attr({
                    href: HordeMobile.createUrl('nag-toggle', params)
                })
            );
        item.jqmData('task_id', t.id);
        NagMobile.styleTask(item, t);
        l.append(item);
    },

    toPage: function(e, data)
    {
        switch (data.options.parsedUrl.view) {
        case 'nag-list':
            NagMobile.toList(data);
            e.preventDefault();
            break;

        case 'nag-taskform-view':
            if (data.options.parsedUrl.params.task_id) {
                NagMobile.getTask(data);
            } else {
                HordeMobile.changePage('nag-taskform-view', data);
                $('#nag-taskform-view .smartmobile-title').text(Nag.strings.newTask);
            }
            e.preventDefault();
            break;

        case 'nag-lists':
            NagMobile.toLists(data);
            e.preventDefault();
            break;

        case 'nag-toggle':
            NagMobile.toggleComplete(data);
            e.preventDefault();
            break;
        }
    },

    styleTask: function(l, t)
    {
        var task_due = Date.parse(t.dd),
            task_overdue = task_due ? (task_due.compareTo(new Date()) < 0 ? true : false) : false;

        if (!t.cp) {
            l.removeClass('closed');
            if (!task_overdue) {
                l.removeClass('overdue');
            } else {
                l.addClass('overdue');
            }
        } else {
            l.addClass('closed');
            l.removeClass('overdue');
        }
    },

    prepareFormForNew: function(e)
    {
        var f = $('form')[0];
        f.reset();
    },

    handleSubmit: function(e)
    {
        var form = $('#nag-task-form'),
            data = HordeJquery.formToObject(form);

        data.tasklist = NagMobile.currentList;
        HordeMobile.doAction('saveTask', data, NagMobile.handleSubmitCallback);
    },

    handleSubmitCallback: function(r)
    {

    },

    handleCancel: function(e)
    {
        HordeMobile.changePage('nag-lists');
    },

    onDocumentReady: function()
    {
        $(document).bind('pagebeforechange', NagMobile.toPage);

        // Capture task completed clicks to add the current LI element to
        // the page change data.
        $('#nag-list :jqmData(role="listview")').on('click', 'li', function(e) {
            var a = $(e.target).closest('a[href^="#nag-toggle"]');
            if (a.length) {
                $.mobile.changePage(a.attr('href'), { data: $(e.currentTarget) });
                return false;
            }
        });

        // Capture new task clicks.
        $('#nag-list :jqmData(role="footer")').on('click', NagMobile.prepareFormForNew);

        $('#nag-taskform-view a[href^="#task-submit"]').on('click', NagMobile.handleSubmit);
        $('#nag-taskform-view a[href^="#task-cancel"]').on('click', NagMobile.handleCancel);
        NagMobile.tasklists = { undefined: Nag.strings.all };
    }

};

$(NagMobile.onDocumentReady);
