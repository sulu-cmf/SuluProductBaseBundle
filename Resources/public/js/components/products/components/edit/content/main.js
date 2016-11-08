/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/product/product-content-manager',
    'services/product/product-manager',
    'suluproduct/util/header'
], function(ProductContentManager, ProductManager, HeaderUtil) {

    'use strict';

    var defaults = {
            data: {}
        },

        constants = {
            formId: 'content-form'
        },

        selectors = {
            form: '#' + constants.formId
        },

        templates = {
            /**
             * Content template.
             */
            content: [
                '<form id="' + constants.formId + '">',
                '   <div class="highlight-section fixed-width">',
                '       <div class="grid-row content">',
                '           <div class="form-group floating grid-col-12">',
                '               <label for="title" class="pointer "><%= translate(\'public.title\') %></label>',
                '               <input id="title" data-mapper-property="title" type="text" class="form-element input-large"/>',
                '           </div>',
                '           <div class="floating grid-col-12">',
                '           <div class="form-group grid-col-6">',
                '               <label for="routePath" class="pointer "><%= translate(\'sulu_product.route-path\') %></label>',
                '               <input id="routePath" data-mapper-property="routePath" type="text" class="form-element"/>',
                '           </div>',
                '           </div>',
                '       </div>',
                '   </div>',
                '</form>'
            ].join('')
        },

        /**
         * Bind custom events.
         */
        bindCustomEvents = function() {
            this.sandbox.on('sulu.toolbar.save', onProductSaveClicked.bind(this));
        },

        /**
         * Triggered when toolbar save button was clicked.
         */
        onProductSaveClicked = function() {
            if (!this.sandbox.form.validate(selectors.form)) {
                return;
            }

            // Get data of form.
            var data = this.sandbox.form.getData(selectors.form);

            // Save content.
            var contentSaved = ProductContentManager.save(this.options.data.id, this.options.locale, data);

            // Check if product status was changed and save.
            var statusSaved = true;
            var changedStatus = HeaderUtil.retrieveChangedStatus();
            if (!!changedStatus) {
                statusSaved = ProductManager.saveStatus(this.options.data.id, changedStatus);
            }

            this.sandbox.util.when(contentSaved, statusSaved).then(onProductSaved.bind(this));
        },

        /**
         * Triggered when content has been saved to product.
         */
        onProductSaved = function() {
            alert("all data saved, show label");

            // Disable save button.
            HeaderUtil.setSaveButton(false);
        },

        /**
         * Sets data to form.
         *
         * @param {Object} data
         *
         * @returns {Object} Promise
         */
        setFormData = function(data) {
            var deferred = $.Deferred();

            // Initialize form.
            if (!this.formObject) {
                this.formObject = this.sandbox.form.create(selectors.form);
            }

            // When form is initialized set data.
            this.formObject.initialized.then(function() {
                this.sandbox.form.setData(selectors.form, data).then(function() {
                    deferred.resolve();
                })
            }.bind(this));

            return deferred.promise();
        },

        /**
         * Listens for changes of form.
         *
         * @returns {Bool}
         */
        listenForFormChange = function() {
            this.sandbox.dom.on(this.$el, 'keyup', HeaderUtil.setSaveButton.bind(this, true));

            return true;
        },

        /**
         * Called when data has been set to form.
         */
        onFormDataSet = function() {
            if (!this.listenerEnabled) {
                this.listenerEnabled = listenForFormChange.call(this);
            }
        },

        /**
         * Renders component ui.
         */
        render = function(contentData) {
            // Render template.
            this.sandbox.dom.html(this.$el, _.template(templates.content, {
                'translate': this.sandbox.translate
            }));

            // Set data to form.
            setFormData.call(this, contentData).then(onFormDataSet.bind(this));
        };

    return {
        /**
         * Defines page layout.
         *
         * @return {Object}
         */
        layout: function() {
            return {
                extendExisting: true,

                content: {
                    width: 'fixed',
                    rightSpace: false,
                    leftSpace: false
                }
            };
        },

        /**
         * Initialization function of variants-list.
         */
        initialize: function() {
            this.listenerEnabled = false;

            // Merge options with defaults.
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            // TODO: Register status changes
            this.status = this.options.data.attributes.status;

            // Set correct status in header bar.
            this.sandbox.emit('product.state.change', this.status);

            // Load contents then render component.
            ProductContentManager.load(this.options.data.id, this.options.locale).then(render.bind(this));

            bindCustomEvents.call(this);
        }
    };
});
