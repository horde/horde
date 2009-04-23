var KronolithTagger = Class.create({
        initialize: function(params)
        {
            this.p = params;

            // Array to hold the currently selected tags to ease with removing
            // them, assuring no duplicates etc..
            this.selectedTags = [];

            // The outter most box, the "fake" input element.
            if (typeof this.p.box == 'undefined') {
                this.p.box = this.p.box = 'HordeACBox';
            }
            if (typeof this.p.boxClass == 'undefined') {
                this.p.boxClass = 'hordeACBox';
            }

            // The class for the ul. li will have a *Item and *Member class
            // added to them.
            if (typeof this.p.listClass == 'undefined') {
                this.p.listClass = 'hordeACList';
            }

            // Class for the actual input element.
            if (typeof this.p.growingInputClass == 'undefined') {
                this.p.growingInputClass = 'hordeACTrigger';
            }
            if (typeof this.p.triggerContainer == 'undefined') {
                this.p.triggerContainer = 'hordeACTriggerContainer';
            }

            // p.tags is now the hidden input field, while p.trigger will
            // contain the "real" input element's name attribute.
            this.p.tags = this.p.trigger;
            this.p.trigger = this.p.trigger + 'real';

            this.buildStructure();

            var trigger = $(this.p.trigger);
            trigger.observe('keydown', this._onKeyDown.bindAsEventListener(this));

            // Bind this object to the trigger so we can call it's methods
            // from client code.
            //trigger.kronolithTagger = this;
            $(this.p.tags).tagger = this;

            // Make sure the p.tags element is hidden
            if (!this.p.debug) {
                $(this.p.tags).hide();
            }

            // Set the updateElement callback
            this.p.params.updateElement = this._updateElement.bind(this);

            // Look for clicks on the box to simulate clicking in an input box
            $(this.p.box).observe('click', function() {$(this.p.trigger).focus()}.bindAsEventListener(this));

            // Create the underlaying Autocompleter
            this.p.uri = this.p.uri + '/input=' + this.p.trigger;
            new Ajax.Autocompleter(this.p.trigger, this.p.trigger + '_results', this.p.uri, this.p.params);

            // Prepopulate the tags and the container elements?
            if (typeof this.p.existing != 'undefined') {
                this.init(this.p.existing);
            }

        },

        init: function(existing)
        {
            // TODO: Resize the trigger field to fill the current line?
            // Clear any existing values
            if (this.selectedTags.length) {
                $(this.p.box).select('li.' + this.p.listClass + 'Item').each(function(item) {this.removeTagNode(item) }.bind(this));
            }

            // Clear the hidden tags field
            $(this.p.tags).value = '';

            // Add any initial values
            if (typeof existing != 'undefined' && existing.length) {
                for (var i = 0, l = existing.length; i < l; i++) {
                    this.addNewTagNode(existing[i]);
                }
            }

        },

        buildStructure: function()
        {
            // Build the outter box
            var box = new Element('div', {id: this.p.box, 'class': this.p.boxClass});

            // The results div - where the autocomplete choices are placed.
            var results = new Element('div', {id: this.p.trigger + '_results', 'class': 'autocomplete'});

            // The list - where the choosen items are placed as <li> nodes
            var list = new Element('ul', {'class':   this.p.listClass});

            // The input element and the <li> wraper
            var inputListItem = new Element('li', {'class': this.p.listClass + 'Member',
                                                   'id': this.p.triggerContainer});

            var growingInput = new Element('input', {'class': this.p.growingInputClass,
                                                     'id': this.p.trigger,
                                                     'name': this.p.trigger,
                                                     'autocomplete':'off'});

            inputListItem.update(growingInput);
            list.update(inputListItem);
            box.update(list);
            box.insert(results);

            // Replace the single input element with the new structure and
            // move the old element into the structure while making sure it's
            // hidden. (Use the long form to play nice with Opera)
            oldTrigger = Element.replace($(this.p.tags), box);
            box.insert(oldTrigger);

        },

        _onKeyDown: function(e)
        {
            // Check for a comma
            if (e.keyCode == 188) {
                //Strip off leading commas
                value = $F(this.p.trigger).replace(/^,/, '');
                if (value.length) {
                    if (value.match(/^[^"]?"[^"]+$/)) {
                        // Unclosed quote
                        return;
                    }
                    this.addNewTagNode(value);
                }
                e.stop();
            }
        },

        // Used as the updateElement callback.
        _updateElement: function(item)
        {
            var value = item.collectTextNodesIgnoreClass('informal');
            this.addNewTagNode(value);
        },

        addNewTagNode: function(value)
        {
            // Don't add if it's already present.
            for (var x = 0, len = this.selectedTags.length; x < len; x++) {
                if (this.selectedTags[x] == value) {
                    return;
                }
            }

            var newTag = new Element('li', {class: this.p.listClass + 'Member ' + this.p.listClass + 'Item'}).update(value);
            var x = new Element('img', {class: 'hordeACItemRemove', src:this.p.URI_IMG_HORDE + "/delete-small.png"});
            x.observe('click', this._removeTagHandler.bindAsEventListener(this));
            newTag.insert(x);
            $(this.p.triggerContainer).insert({before: newTag});
            $(this.p.trigger).value = '';

            // Add to hidden input field.
            if ($(this.p.tags).value) {
                $(this.p.tags).value = $(this.p.tags).value + ', ' + value;
            } else {
                $(this.p.tags).value = value;
            }

            // ...and keep the selectedTags array up to date.
            this.selectedTags.push(value);
        },

        removeTagNode: function(item)
        {
            var value = item.collectTextNodesIgnoreClass('informal');
            for (var x = 0, len = this.selectedTags.length; x < len; x++) {
                if (this.selectedTags[x] == value) {
                    this.selectedTags.splice(x, 1);
                }
            }
            item.remove();
        },

        _removeTagHandler: function(e)
        {
            item = Event.element(e).up();
            this.removeTagNode(item);
            $(this.p.tags).value = this.selectedTags.join(',');
        }
});