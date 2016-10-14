/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'text!suluproduct/components/products/components/attributes/overlay-content.html',
    'services/product-type-manager'
], function(Config, OverlayTpl, ProductTypeManager) {
    'use strict';

    var productAttributesInstanceName = 'product-attribute-list-toolbar',
        datagridInstanceName = 'product-attribute-datagrid',
        overlayInstanceName = 'product-attribute-overlay',
        selectInstanceName = 'product-attribute-select',
        typeText = 'product.attribute.type.text',
        attributeId = null,
        actions = {
            ADD: 1,
            DELETE: 2,
            UPDATE: 3
        },

        // constants = {
        //     productAttributesDataGridInstanceName = 'product-attribute-datagrid',
        // },

        selectors = {
            variantAttributesContainer: '#js-variant-attributes-container'
            // productAttributesForm: '#js-product-attributes-form',
            // variantAttributesForm: '#js-variant-attributes-form'
        },


        /**
         * Bind custom events.
         */
        bindCustomEvents = function() {
            this.sandbox.on('sulu.toolbar.delete', onDeleteClicked.bind(this));

            this.sandbox.on('product.state.change', onStatusChanged.bind(this));

            this.sandbox.on('sulu.toolbar.save', onSaveClicked.bind(this));

            this.sandbox.on('sulu.products.saved', onProductSaved.bind(this));

            this.sandbox.on(
                'husky.datagrid.' + datagridInstanceName + '.number.selections',
                onProductAttributeSelection.bind(this, productAttributesInstanceName)
            );
        },

        /**
         * Called when delete button is clicked.
         */
        onDeleteClicked = function() {
            this.sandbox.emit('sulu.product.delete', this.options.data.id);
        },

        /**
         * Enables or disables toolbar based on number of items that were selected.
         *
         * @param {Number} number
         * @param {String} toolBarInstanceName
         */
        onProductAttributeSelection = function(number, toolBarInstanceName) {
            var postfix = number > 0 ? 'enable' : 'disable';
            this.sandbox.emit(
                'husky.toolbar.' + toolBarInstanceName + '.item.' + postfix,
                'delete',
                false)
        },

        /**
         * Callback, when product has been saved.
         *
         * @param {Object} data
         */
        onProductSaved = function(data) {
            var attributes = data.attributes;

            // Select action.
            if (data.action === actions.ADD) {
                // Add records in datagrid.
                var attribute = _.findWhere(attributes, {'attributeId': data.attributeIdAdded});
                this.sandbox.emit('husky.datagrid.' + datagridInstanceName + '.record.add', attribute);
            } else if (data.action === actions.DELETE) {
                // Delete records in datagrid.
                $.each(data.attributeIdsDeleted, function(key, id) {
                    this.sandbox.emit('husky.datagrid.' + datagridInstanceName + '.record.remove', id);
                }.bind(this));
            } else if (data.action === actions.UPDATE) {
                // Update datagrid with received records.
                this.sandbox.emit('husky.datagrid.' + datagridInstanceName + '.records.set', attributes);
            }

            setHeaderBar.call(this, true);
            this.options.data.attributes.status = this.status;
        },

        /**
         * Called when save button was clicked.
         */
        onSaveClicked = function() {
            this.sendData = {};
            this.sendData.status = this.status;
            this.sendData.id = this.options.data.id;
            save.call(this);
        },

        /**
         * Called when product status has changed.
         *
         * @param {Object} status
         */
        onStatusChanged = function(status) {
            if (!this.options.data
                || !this.options.data.attributes.status
                || this.options.data.attributes.status.id !== status.id
            ) {
                this.status = status;
                this.options.data.attributes.status = this.status;
                setHeaderBar.call(this, false);
            }
        },

        /**
         * @param {Boolean} saved Defines if saved state should be shown.
         */
        setHeaderBar = function(saved) {
            if (saved !== this.saved) {
                if (!!saved) {
                    this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
                } else {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                }
            }
            this.saved = saved;
        },

        /**
         * Create overlay content for add attribute overlay.
         */
        createAddOverlayContent = function() {
            attributeId = null;

            // create container for overlay
            var $overlayContent = this.sandbox.dom.createElement(this.sandbox.util.template(OverlayTpl, {
                translate: this.sandbox.translate
            }));
            this.sandbox.dom.append(this.$el, $overlayContent);

            return $overlayContent;
        },

        /**
         * Create the overlay.
         */
        createAddOverlay = function() {
            // Call JSON to get the attributes from the server then create the overlay after it's done.
            var attributesUrl = 'api/attributes?locale=' + this.options.locale;
            var ajaxRequest = $.getJSON(attributesUrl, function(data) {
                this.attributeTypes = [];

                $.each(data._embedded.attributes, function(key, value) {
                    var newAttribute = {
                        'id': value.id,
                        'name': value.name
                    };

                    // At this time we support only text type attributes.
                    if (value.type.name === typeText) {
                        this.attributeTypes.push(newAttribute);
                    }
                }.bind(this));
            }.bind(this));

            ajaxRequest.done(function() {
                // Create container for overlay.
                var $overlay = this.sandbox.dom.createElement('<div>');
                this.sandbox.dom.append(this.$el, $overlay);

                // Create content.
                this.sandbox.start([
                    {
                        name: 'overlay@husky',
                        options: {
                            el: $overlay,
                            supportKeyInput: false,
                            title: this.sandbox.translate('product.attribute.overlay.title'),
                            skin: 'normal',
                            openOnStart: true,
                            removeOnClose: true,
                            instanceName: overlayInstanceName,
                            data: createAddOverlayContent.call(this),
                            okCallback: overlayOkClicked.bind(this)
                        }
                    }
                ]);

            }.bind(this));

            ajaxRequest.fail(function() {
                console.log('Error retrieving attributes from server');
            }.bind(this));

            ajaxRequest.complete(function() {

                var preSelectedElement = [];

                // Set pre selected element in checkbox.
                if (this.attributeTypes.length > 0 &&
                    typeof(this.attributeTypes[0]) === "object" &&
                    typeof(this.attributeTypes[0].name) === "string"
                ) {
                    attributeId = this.attributeTypes[0].id;
                    preSelectedElement.push(this.attributeTypes[0].name);
                }

                // Create dropbox in overlay.
                var selectOptions = {
                    el: '#selectBox',
                    instanceName: selectInstanceName,
                    multipleSelect: false,
                    defaultLabel: this.sandbox.translate('product.attribute.overlay.defaultlabel'),
                    preSelectedElements: preSelectedElement,
                    valueName: 'name',
                    isNative: true,
                    data: this.attributeTypes
                };

                this.sandbox.start([
                    // Start select.
                    {
                        name: 'select@husky',
                        options: selectOptions
                    }
                ]);

                // Define select event for dropbox.
                this.sandbox.on('husky.select.' + selectInstanceName + '.selected.item', function(item) {
                    attributeId = parseInt(item);
                });
            }.bind(this));
        },

        /**
         * Save product attributes.
         */
        save = function() {
            this.saved = false;
            this.sandbox.emit('sulu.products.save', this.sendData);
        },

        /**
         * Called when OK on overlay was clicked.
         */
        overlayOkClicked = function() {
            // exit if no attribute is selected in overlay
            if (!attributeId) {
                return;
            }

            this.sendData = {};
            var attributeValueName = this.sandbox.dom.val('#attribute-name');

            var attributes = this.options.data.attributes.attributes;

            var result = _.findWhere(attributes, {'attributeId': attributeId});

            if (result) {
                result.attributeValueName = attributeValueName;
                result.attributeValueLocale = this.options.locale;
                this.sendData.action = actions.UPDATE;
            } else {
                var newAttribute = {
                    'attributeId': attributeId,
                    'attributeValueName': attributeValueName,
                    'attributeValueLocale': this.options.locale
                };
                attributes.push(newAttribute);
                this.sendData.action = actions.ADD;
            }

            this.sendData.attributeIdAdded = attributeId;
            this.sendData.attributes = attributes;
            this.sendData.status = this.status;
            this.sendData.id = this.options.data.attributes.id;

            save.call(this);
        },

        /**
         * Delete action function from toolbar.
         */
        attributeDelete = function() {
            this.sandbox.emit('husky.datagrid.' + datagridInstanceName + '.items.get-selected', function(ids) {

                var attributes = this.options.data.attributes.attributes;
                this.sendData = {};
                var attributeIdsDeleted = [];

                _.each(ids, function(value, key, list) {
                    var result = _.findWhere(attributes, {'attributeId': value});
                    attributes = _.without(attributes, result);
                    attributeIdsDeleted.push(value);
                });

                this.sendData.attributeIdsDeleted = attributeIdsDeleted;
                this.sendData.attributes = attributes;
                this.sendData.status = this.status;
                this.sendData.id = this.options.data.id;
                this.sendData.action = actions.DELETE;

                save.call(this);
            }.bind(this));
        },

        /**
         * On badge attributeName for datagrid.
         *
         * @param {Object} item
         * @param {Object} badge
         * @param {String} locale
         */
        onBadgeAttributeName = function(item, badge, locale) {
            if (item.attributeLocale
                && item.attributeLocale == item.fallbackLocale
                && item.attributeLocale != locale
            ) {
                badge.title = item.attributeLocale;

                return badge;
            }

            return false;
        },

        /**
         * On badge attributeValueName for datagrid.
         *
         * @param {Object} item
         * @param {Object} badge
         * @param {String} locale
         */
        onBadgeAttributeValueName = function(item, badge, locale) {
            if (item.attributeValueLocale
                && item.attributeValueLocale == item.fallbackLocale
                && item.attributeValueLocale != locale
            ) {
                badge.title = item.attributeValueLocale;

                return badge;
            }

            return false;
        },

        /**
         * Calls basic form components.
         */
        startFormComponents = function() {
            var datagridOptions = {
                el: '#product-attribute-list',
                instanceName: datagridInstanceName,
                idKey: 'attributeId',
                resultKey: 'attributes',
                matchings: [
                    {
                        name: 'attributeName',
                        content: this.sandbox.translate('product.attribute.name')
                    },
                    {
                        name: 'attributeValueName',
                        content: this.sandbox.translate('product.attribute.value')
                    }
                ],
                viewOptions: {
                    table: {
                        type: 'checkbox',
                        badges: [
                            {
                                column: 'attributeName',
                                callback: function(item, badge) {
                                    return onBadgeAttributeName(item, badge, this.options.locale);
                                }.bind(this)
                            },
                            {
                                column: 'attributeValueName',
                                callback: function(item, badge) {
                                    return onBadgeAttributeValueName(item, badge, this.options.locale);
                                }.bind(this)
                            }
                        ]
                    }
                },
                data: this.options.data.attributes
            };

            this.sandbox.start([
                // Start datagrid.
                {
                    name: 'datagrid@husky',
                    options: datagridOptions
                }
            ]);

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: {
                        el: '#product-attribute-toolbar',
                        instanceName: productAttributesInstanceName,
                        small: false,
                        buttons: [
                            {
                                id: 'add',
                                icon: 'plus-circle',
                                callback: createAddOverlay.bind(this)
                            },
                            {
                                id: 'delete',
                                icon: 'trash-o',
                                disabled: true,
                                callback: attributeDelete.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Starts toolbar and datagrid for managing variant attributes.
         */
        startVariantAttributeFormComponents = function() {
            var datagridOptions = {
                el: '#js-variant-attribute-list',
                instanceName: datagridInstanceName,
                idKey: 'attributeId',
                resultKey: 'attributes',
                matchings: '/admin/api/product-variant-attributes/fields?locale=' + this.options.locale,
                // viewOptions: {
                //     table: {
                //         type: 'checkbox',
                //         badges: [
                //             {
                //                 column: 'name',
                //                 callback: function(item, badge) {
                //                     return onBadgeAttributeName(item, badge, this.options.locale);
                //                 }.bind(this)
                //             }
                //         ]
                //     }
                // },
                url: '/admin/api/products/' + this.options.data.id + '/variant-attributes?locale=' + this.options.locale
            };

            this.sandbox.start([
                // Start datagrid.
                {
                    name: 'datagrid@husky',
                    options: datagridOptions
                },
                {
                    name: 'toolbar@husky',
                    options: {
                        el: '#js-variant-attribute-toolbar',
                        instanceName: productAttributesInstanceName,
                        small: false,
                        buttons: [
                            {
                                id: 'add',
                                icon: 'plus-circle',
                                callback: createAddOverlay.bind(this)
                            },
                            {
                                id: 'delete',
                                icon: 'trash-o',
                                disabled: true,
                                callback: attributeDelete.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Initialize variant attributes components.
         */
        initVariantAttributesForm = function() {
            var productType = this.options.data.attributes.type.id;

            // If current product is a product with variants we also show product variants table.
            if (productType !== ProductTypeManager.types.PRODUCT_WITH_VARIANTS) {
                return;
            }

            // Show variant attributes container.
            $(selectors.variantAttributesContainer).removeClass('is-hidden');

            startVariantAttributeFormComponents.call(this);
        },

        /**
         * Initialize product attributes components.
         */
        initProductAttributesForm = function() {
            startFormComponents.call(this);
        };

    return {
        name: 'Sulu Product Attributes View',

        templates: ['/admin/product/template/product/attributes'],

        /**
         * Constructor of component.
         */
        initialize: function() {
            bindCustomEvents.call(this);

            // Set correct status.
            this.status = Config.get('product.status.inactive');
            if (!!this.options.data) {
                this.status = this.options.data.attributes.status;
            }
            // Reset status if it has been changed before and has not been saved.
            this.sandbox.emit('product.state.change', this.status);

            this.render();
            setHeaderBar.call(this, true);
        },

        /**
         * Renders component.
         */
        render: function() {
            this.sandbox.dom.html(
                this.$el,
                this.renderTemplate(
                    '/admin/product/template/product/attributes',
                    {
                        'translate': this.sandbox.translate
                    }
                )
            );

            initProductAttributesForm.call(this);
            initVariantAttributesForm.call(this);
        }
    };
});
