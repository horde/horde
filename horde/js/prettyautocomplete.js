var PrettyAutocompleter = Class.create({
        initialize: function(element, params)
        {
            this.p = Object.extend({
                // Outer div/fake input box and CSS class
                box: 'HordeACBox',
                boxClass: 'hordeACBox',
                // <ul> CSS class
                listClass: 'hordeACList',
                //CSS class for real input field
                growingInputClass: 'hordeACTrigger',
                // Dom id for <li> that holds the input field.
                triggerContainer: 'hordeACTriggerContainer',
                // Min pixel width of input field
                minTriggerWidth: 100,
                // Allow for a function that filters the display value
                // This function should *always* return escaped HTML
                displayFilter: function(t) { return t.escapeHTML() },
                filterCallback: this._filterChoices.bind(this)
            }, params);

            // Array to hold the currently selected items to ease with removing
            // them, assuring no duplicates etc..
            this.selectedItems = [];

            // The original input element is transformed into the hidden input
            // field that hold the text values (p.items), while p.trigger is
            // the borderless input field located in p.box
            this.p.items = element;
            this.p.trigger = element + 'real';

            // Build the DOM structure
            this.buildStructure();

            var trigger = $(this.p.trigger);
            trigger.observe('keydown', this._onKeyDown.bindAsEventListener(this));

            // Bind this object to the trigger so we can call it's methods
            // from client code.
            $(this.p.items).autocompleter = this;

            // Make sure the p.items element is hidden
            if (!this.p.debug) {
                $(this.p.items).hide();
            }

            // Set the updateElement callback
            this.p.onSelect = this._updateElement.bind(this);

            // Look for clicks on the box to simulate clicking in an input box
            $(this.p.box).observe('click', function() {trigger.focus()}.bindAsEventListener(this));
            trigger.observe('blur', this._resize.bindAsEventListener(this));
            trigger.observe('keydown', this._resize.bindAsEventListener(this));
            trigger.observe('keypress', this._resize.bind(this));
            trigger.observe('keyup', this._resize.bind(this));

            // Create the underlaying Autocompleter
            this.p.uri = this.p.uri + '/input=' + this.p.trigger;

            // Make sure the knl is contained in the overlay
            this.p.domParent = this.p.box;
            new Ajax.Autocompleter(this.p.trigger, this.p.uri, this.p);

            // Prepopulate the items and the container elements?
            if (typeof this.p.existing != 'undefined') {
                this.init(this.p.existing);
            }
        },

        init: function(existing)
        {
            // TODO: Resize the trigger field to fill the current line?
            // Clear any existing values
            if (this.selectedItems.length) {
                $(this.p.box).select('li.' + this.p.listClass + 'Item').each(function(item) {this.removeItemNode(item) }.bind(this));
            }

            // Clear the hidden items field
            $(this.p.items).value = '';

            // Add any initial values
            if (typeof existing != 'undefined' && existing.length) {
                for (var i = 0, l = existing.length; i < l; i++) {
                    this.addNewItemNode(existing[i]);
                }
            }

        },

        buildStructure: function()
        {
            // Build the outter box
            var box = new Element('div', {id: this.p.box, 'class': this.p.boxClass});

            // The list - where the choosen items are placed as <li> nodes
            var list = new Element('ul', {'class':   this.p.listClass});

            // The input element and the <li> wraper
            var inputListItem = new Element('li', {'class': this.p.listClass + 'Member',
                                                   'id': this.p.triggerContainer});

            var growingInput = new Element('input', {'class': this.p.growingInputClass,
                                                     'id': this.p.trigger,
                                                     'name': this.p.trigger,
                                                     'autocomplete':'off'});
            // Create a hidden span node to help calculate the needed size
            // of the input field.
            this.sizer = new Element('span', {'style': 'float:left;display:inline-block;position:absolute;left:-1000px;'});

            inputListItem.update(growingInput);
            list.update(inputListItem);
            box.update(list);
            box.insert(this.sizer);

            // Replace the single input element with the new structure and
            // move the old element into the structure while making sure it's
            // hidden. (Use the long form to play nice with Opera)
            box.insert(Element.replace($(this.p.items), box));
        },

        shutdown: function()
        {
            this._processValue();
        },

        _onKeyDown: function(e)
        {
            // Check for a comma
            if (e.keyCode == 188) {
                this._processValue();
                e.stop();
            }
        },

        _processValue: function()
        {
            var value = $F(this.p.trigger).replace(/^,/, '');
            if (value.length) {
                this.addNewItemNode(value);
            }
        },

        _resize: function(e)
        {
            this.sizer.update($(this.p.trigger).value);
            newSize = Math.min(this.sizer.getWidth(), $(this.p.box).getWidth());
            newSize = Math.max(newSize, this.p.minTriggerWidth);
            $(this.p.trigger).setStyle({'width': newSize + 'px'});
        },

        // Used as the updateElement callback.
        _updateElement: function(item)
        {
            this.addNewItemNode(item);
        },

        addNewItemNode: function(value)
        {
            // Don't add if it's already present.
            for (var x = 0, len = this.selectedItems.length; x < len; x++) {
                if (this.selectedItems[x].rawValue == value) {
                    $(this.p.trigger).value = '';
                    return;
                }
            }

            var displayValue = this.p.displayFilter(value);
            var newItem = new Element('li', {'class': this.p.listClass + 'Member ' + this.p.listClass + 'Item'}).update(displayValue);
            var x = new Element('img', {'class': 'hordeACItemRemove', src:this.p.URI_IMG_HORDE + "/delete-small.png"});
            x.observe('click', this._removeItemHandler.bindAsEventListener(this));
            newItem.insert(x);
            $(this.p.triggerContainer).insert({before: newItem});
            $(this.p.trigger).value = '';

            // Add to hidden input field.
            if ($(this.p.items).value) {
                $(this.p.items).value = $(this.p.items).value + ', ' + value;
            } else {
                $(this.p.items).value = value;
            }

            // ...and keep the selectedItems array up to date.
            this.selectedItems.push({'rawValue':value, 'displayValue':displayValue});
        },

        removeItemNode: function(item)
        {
            var value = item.collectTextNodesIgnoreClass('informal');
            for (var x = 0, len = this.selectedItems.length; x < len; x++) {
                if (this.selectedItems[x].displayValue.unescapeHTML() == value) {
                   this.selectedItems.splice(x, 1);
                   break;
                }
            }
            item.remove();
        },

        disable: function()
        {
          this._enabled = false;
          $(this.p.box).select('.hordeACItemRemove').each(function(e) {e.toggle()});
          $(this.p.trigger).disable();
        },

        enable: function()
        {
            this._enabled = true;
            $(this.p.box).select('.hordeACItemRemove').each(function(e) {e.toggle()});
            $(this.p.trigger).enable();
        },
        _removeItemHandler: function(e)
        {
            var item = Event.element(e).up();
            this.removeItemNode(item);
            var realValues = [];
            for (var x = 0, len = this.selectedItems.length; x < len; x++) {
                realValues.push(this.selectedItems[x].rawValue);
            }
            $(this.p.items).value = realValues.join(',');
        },

        _filterChoices: function(c)
        {
            this.selectedItems.each(function(item) {
                c = c.without(item.displayValue);
            });
            return c;
        }
});