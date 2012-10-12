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

    /**
     * Toggle the completion status of the task.
     *
     * @param object d  The data object.
     */

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

    /**
     * Callback for the toggleComplete action
     *
     * @param object r    The response object.
     * @param object elt  The element containing the task.
     */
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

    /**
     * Get a task from the server.
     *
     * @param object d  The data object.
     */
    getTask: function(d)
    {
        var parsed = d.options.parsedUrl;

        HordeMobile.doAction(
            'getTask',
            {
                task: parsed.params.task_id,
                tasklist: parsed.params.tasklist
            },
            NagMobile.getTaskCallback
        );
        $('#nag-taskform-view a[href^="#task-delete"]').show();
        HordeMobile.changePage('nag-taskform-view', d);
    },

    /**
     * Callback for the getTask action.
     *
     * @param object r  The response object.
     */
    getTaskCallback: function(r)
    {
        var task = r.task,
            f = $('form')[0];

        f.reset();
        $("#task_title").val(task.n);
        $("#task_desc").val(task.de);
        $("#task_assignee").val(task.as);
        $("task_private").prop("checked", task.pr).checkboxradio("refresh");
        if (task.dd) {
            $("#task_due").val(Date.parse(task.dd).toString('yyyy-MM-dd'));
        }
        if (task.s) {
            $("#task_start").val(Date.parse(task.s).toString('yyyy-MM-dd'));
        }
        $("#task_priority").val(task.pr);
        $("#task_completed").prop("checked", task.cp).checkboxradio("refresh");
        $("#task_estimate").val(task.e);
        $("#task_id").val(task.id);
    },

    /**
     * Get a list of tasklists from the server and display the nag-lists view.
     *
     * @param object d  The data object.
     */
    toLists: function(d)
    {
        HordeMobile.changePage('nag-lists', d);
        HordeMobile.doAction(
            'getTaskLists',
            {},
            NagMobile.getTasklistsCallback
        );
    },

    /**
     * Callback for the getTaskLists action
     *
     * @param object r  The response object.
     */
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

     /**
      * Insert a tasklist element into the tasklist list.
      *
      * @param object el    The UL element.
      * @param object l     The list hash.
      * @param boolean top  Place new list at top of list if true.
      */
    insertTasklist: function(el, l, top)
    {
        var url = HordeMobile.createUrl('nag-list', { tasklist: l.id }),
            list;

        NagMobile.tasklists[l.id] = l;

        list = $('<li>').append($('<a>').attr({ href: url }).addClass('nag-tasklist')
            .append($('<img>').attr({ 'src': Nag.conf.icons[(l.smart ? 'smartlist' : 'tasklist')] }).addClass('ui-li-icon'))
            .append($('<h3>').text(l.name))
            .append($('<span>').addClass('ui-li-count' + (l.overdue ? ' overdue' : '')).text(l.count))
        );

        if (top) {
            el.prepend(list);
        } else {
            el.append(list);
        }
    },

    /**
     * Retrieve a tasklist from the server and display the nag-list view.
     *
     * @param object d  The data object.
     */
    toList: function(d)
    {
        var params = d.options.parsedUrl.params;

        HordeMobile.doAction(
            'listTasks',
            { tasklist: params.tasklist },
            NagMobile.listTasksCallback
        );
        $('#nag-list .smartmobile-title')
            .text(NagMobile.tasklists[params.tasklist].name);
        NagMobile.currentList = params.tasklist;
        HordeMobile.changePage('nag-list', d);
    },

    /**
     * Callback for the listTasks action.
     *
     * @param object r  The response object.
     */
    listTasksCallback: function(r)
    {
        NagMobile.tasks = {};
        $.each(r.tasks, function(i, t) {
            NagMobile.tasks[t.id] = t;
        });
        NagMobile.buildTaskList();
        if (NagMobile.tasklists[NagMobile.currentList].smart == 1) {
            $('#nag-list :jqmData(role="footer") a[href^="#nag-taskform-view"]').hide();
        } else {
            $('#nag-list :jqmData(role="footer") a[href^="#nag-taskform-view"]').show();
        }
    },

    /**
     * Build the complete tasklist
     */
    buildTaskList: function()
    {
        var list = $('#nag-list :jqmData(role="listview")'),
            count = 0;
        list.empty();
        $.each(NagMobile.tasks, function (i, t) {
            count++;
            NagMobile.insertTask(list, t);
        });
        if (count > 0) {
            $('#nag-notasks').hide();
        } else {
            $('#nag-notasks').show();
        }
        list.listview('refresh');
    },

    /**
     * Insert task into the view.
     *
     * @param object l  The UL element.
     * @param object t  The task hash.
     */
    insertTask: function(l, t)
    {
        var params = {
            task_id: t.id,
            tasklist: t.l
        }, item;

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

    /**
     * Handler for pageBeforeChange event
     *
     * @param object e     The event object.
     * @param object data  The data object.
     */
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

    /**
     * Add the appropriate CSS classes to the task element based on the task's
     * completion, due date etc...
     *
     * @param object l  The tasks's LI element.
     * @param object t  The task hash.
     */
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

    /**
     * Prepare the nag-taskform-view for entering a new task.
     */
    prepareFormForNew: function()
    {
        var f = $('form')[0];
        f.reset();
        $('#nag-taskform-view a[href^="#task-delete"]').hide();
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
        NagMobile.tasks[r.task.id] = r.task;
        NagMobile.buildTaskList();
        HordeMobile.changePage('nag-list');
    },

    handleCancel: function(e)
    {
        HordeMobile.changePage('nag-list');
    },

    handleDelete: function(e)
    {
        var taskid = $('#nag-taskform-view #task_id').val();
        if (taskid) {
            HordeMobile.doAction('deleteTask',
                { 'task_id': taskid },
                NagMobile.handleDeleteCallback
            );
        }
    },

    handleDeleteCallback: function(r)
    {
        if (r.deleted) {
            delete NagMobile.tasks[r.deleted];
        }
        NagMobile.buildTaskList();
        HordeMobile.changePage('nag-list');
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
        $('#nag-list :jqmData(role="footer") a[href^="#nag-taskform-view"]').on('click', NagMobile.prepareFormForNew);
        $('#nag-taskform-view a[href^="#task-submit"]').on('click', NagMobile.handleSubmit);
        $('#nag-taskform-view a[href^="#task-cancel"]').on('click', NagMobile.handleCancel);
        $('#nag-taskform-view a[href^="#task-delete"]').on('click', NagMobile.handleDelete);

        NagMobile.tasklists = Nag.tasklists;
        NagMobile.tasklists[undefined] = { 'name': Nag.strings.all };
    }

};

$(NagMobile.onDocumentReady);
