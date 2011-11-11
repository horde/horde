var NagTasks = {
    knl: {},

    /**
     * Attaches a KeyNavList drop down to one of the time fields.
     *
     * @param string|Element field  A time field (id).
     *
     * @return KeyNavList  The drop down list object.
     */
    attachTimeDropDown: function(field, time_format)
    {
        var list = [], d = new Date(), time, opts;

        d.setHours(0);
        d.setMinutes(0);
        do {
            time = d.toString(time_format);
            list.push({ l: time, v: time });
            d.add(30).minutes();
        } while (d.getHours() !== 0 || d.getMinutes() !== 0);

        field = $(field);
        opts = {
            list: list,
            domParent: field.up('#nag_form_task'),
            onChoose: function(value) {
                field.setValue(value);
            }.bind(this)
        };

        this.knl[field.id] = new KeyNavList(field, opts);

        return this.knl[field.id];
    }
}

document.observe('dom:loaded', function() {
    var dropDown = NagTasks.attachTimeDropDown('due_time', Nag.conf.time_format);
    $('due_time').observe('click', function() { dropDown.show(); });
});
