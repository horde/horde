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
                if (value) {
                    field.setValue(value);
                }
            }.bind(this)
        };

        this.knl[field.id] = new KeyNavList(field, opts);

        return this.knl[field.id];
    },

    /**
     * Keypress handler for time fields.
     */
    timeSelectKeyHandler: function(e)
    {
        switch(e.keyCode) {
        case Event.KEY_UP:
        case Event.KEY_DOWN:
        case Event.KEY_RIGHT:
        case Event.KEY_LEFT:
            return;
        default:
            var dt = $('due_time');
            if ($F(dt) !== this.knl[dt.identify()].getCurrentEntry()) {
                this.knl[dt.identify()].markSelected(null);
            }
        }
    }
}

document.observe('dom:loaded', function() {
    var dropDown = NagTasks.attachTimeDropDown('due_time', Nag.conf.time_format);
    var dt = $('due_time');
    dt.observe('click', function() { dropDown.show(); });
    dt.observe('keyup', NagTasks.timeSelectKeyHandler.bind(NagTasks));
});
