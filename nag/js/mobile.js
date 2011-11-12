var NagMobile = {
    toggleComplete: function()
    {
        var elt = $(this);
        var task = elt.data('task');
        var tasklist = elt.data('tasklist');

        $.ajax({
            url: NagConf.completeUrl,
            type: 'POST',
            data: { task: elt.data('task'), tasklist: elt.data('tasklist') },
            context: elt,
            success: NagMobile.toggleCompleteCallback
        });
    },

    toggleCompleteCallback: function(data, textStatus, jqXHR)
    {
        if (data.data) {
            if (data.data == 'complete') {
                if (NagMobile.showCompleted() == 'incomplete' || NagMobile.showCompleted() == 'future-incomplete') {
                    // Hide the task
                    this.closest('li').remove();
                } else {
                    this.data('icon', 'check');
                    this.find('span.ui-icon').removeClass('ui-icon-nag-unchecked');
                    this.find('span.ui-icon').addClass('ui-icon-check');
                }
            } else {
                if (NagMobile.showCompleted() == 'complete') {
                    // Hide the task
                    this.closest('li').remove();
                } else {
                    this.data('icon', 'nag-unchecked');
                    this.find('span.ui-icon').removeClass('ui-icon-check');
                    this.find('span.ui-icon').addClass('ui-icon-nag-unchecked');
                }
            }
        }
    },

    showCompleted: function()
    {
        switch (NagConf.showCompleted) {
        case 0:
            return 'incomplete';
        case 1:
            return 'all';
        case 2:
            return 'complete';
        case 3:
            return 'future';
        case 4:
            return 'future-incomplete';
        }
    }
};

$(document).ready(function() {
    $('.toggleable').click(NagMobile.toggleComplete);
});
