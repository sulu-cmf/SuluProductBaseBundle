/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'suluproduct/models/product',
    'sulucategory/model/category',
    'app-config',
    'suluproduct/util/header',
    'suluproduct/util/product-delete-dialog'
], function (Product, Category, AppConfig, HeaderUtil, DeleteDialog) {
    'use strict';

    var types = {
            1: 'product',
            2: 'product-with-variants',
            3: 'product-addon',
            4: 'product-set'
        },
        eventNamespace = 'sulu.products.',

        /**
         * @event sulu.products.new
         * @description Opens the form for a new product
         */
        PRODUCT_NEW = eventNamespace + 'new',

        /**
         * @event sulu.products.save
         * @description Saves a given product
         */
        PRODUCT_SAVE = eventNamespace + 'save',

        /**
         * @event sulu.products.delete
         * @description Deletes the given products
         */
        PRODUCT_DELETE = eventNamespace + 'delete',

        /**
         * @description Opens the form for importing products
         */
        PRODUCT_IMPORT = eventNamespace + 'import',

        /**
         * @event sulu.products.list
         * @description Shows the list for products
         */
        PRODUCT_LIST = eventNamespace + 'list',

        /**
         * @event sulu.products.variants.delete
         * @description Deletes the given variants from the current product
         */
        PRODUCT_VARIANT_DELETE = eventNamespace + 'variants.delete';

    return {
        initialize: function () {
            this.product = null;

            this.bindCustomEvents();
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'tab') {
                this.renderTabs().then(function(){
                    if(this.options.content !== 'variants'){
                        HeaderUtil.initToolbar(
                            this.sandbox,
                            this.options.locale,
                            this.product.get('status')
                        );
                    }
                }.bind(this));
            } else if (this.options.display === 'import') {
                this.renderImport();
            }
        },

        bindCustomEvents: function () {
            this.sandbox.on(PRODUCT_NEW, function (type) {
                this.sandbox.emit(
                    'sulu.router.navigate',
                        'pim/products/' + AppConfig.getUser().locale + '/add/type:' + type
                );
            }.bind(this));

            this.sandbox.on(PRODUCT_SAVE, function (data) {
                this.save(data);
            }.bind(this));

            this.sandbox.on(PRODUCT_DELETE, function (ids) {
                this.del(ids);
            }, this);

            this.sandbox.on('sulu.product.delete', function(id){
                this.deleteProduct(id);
            }, this);

            this.sandbox.on(PRODUCT_IMPORT, function () {
                this.sandbox.emit('sulu.router.navigate', 'pim/products/import');
            }.bind(this));

            this.sandbox.on('husky.datagrid.item.click', function (id) {
                this.load(id, AppConfig.getUser().locale);
            }.bind(this));

            this.sandbox.on(PRODUCT_LIST, function () {
                this.sandbox.emit('sulu.router.navigate', 'pim/products');
            }.bind(this));

            this.sandbox.on('sulu.header.language-changed', function (locale) {
                this.load(this.options.id, locale);
            }, this);

            this.sandbox.on('sulu.products.products-overlay.variants.add', function (id, callback) {
                this.addVariant(id, callback);
            }, this);

            this.sandbox.on(PRODUCT_VARIANT_DELETE, function (ids) {
                this.deleteVariants(ids);
            }, this);

            // handling media
            this.sandbox.on('sulu.products.media.save', this.saveMedia.bind(this));

            // workflow
            this.sandbox.on('sulu.products.workflow.triggered', this.triggerWorkflowAction.bind(this));
        },

        triggerWorkflowAction: function(data) {
            if (!!data && !!data.ids && !!data.status) {
                var url = '/admin/api/products?action=changeState&ids=' + data.ids + '&statusId=' + data.status;
                this.sandbox.util.save(url, 'POST')
                    .then(function() {
                        this.sandbox.emit('sulu.product.workflow.completed');
                    }.bind(this))
                    .fail(function(error) {
                        this.sandbox.emit('sulu.labels.error.show',
                            this.sandbox.translate('product.workflow.state.changed.error'),
                            'labels.error',
                            ''
                        );
                        this.sandbox.logger.error('error while changing state of products', error);
                    }.bind(this));
            }
        },

        saveMedia: function(productId, newMediaIds, removedMediaIds) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.processAjaxForMedia(newMediaIds, productId, 'POST');
            this.processAjaxForMedia(removedMediaIds, productId, 'DELETE');
        },

        processAjaxForMedia: function(mediaIds, productId, type) {

            var requests = [],
                medias = [],
                url;

            if (mediaIds.length > 0) {
                this.sandbox.util.each(mediaIds, function(index, id) {

                    if (type === 'DELETE') {
                        url = '/admin/api/products/' + productId + '/media/' + id;
                    } else if (type === 'POST') {
                        url = '/admin/api/products/' + productId + '/media';
                    }

                    requests.push(
                        this.sandbox.util.ajax({
                            url: url,
                            data: {mediaId: id},
                            type: type
                        }).fail(function() {
                            this.sandbox.logger.error("Error while saving media!");
                        }.bind(this))
                    );
                    medias.push(id);
                }.bind(this));

                this.sandbox.util.when.apply(null, requests).then(function() {
                    this.product.fetchLocale(this.options.locale, {
                        success: function (model) {
                            this.options.data = this.product;
                            if (type === 'DELETE') {
                                this.sandbox.emit('sulu.products.media.removed', medias);
                            } else if (type === 'POST') {
                                this.sandbox.emit('sulu.products.media.saved', medias);
                            }
                        }.bind(this),
                        error: function(){
                            this.sandbox.logger.error("error while fetching product");
                        }.bind(this)
                    });

                }.bind(this));
            }
        },

        save: function (data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
            this.product.set(data);

            // FIXME the categories should already be loaded correctly here
            if (!!data.categories) {
                this.product.get('categories').reset();
                this.sandbox.util.foreach(data.categories, function (id) {
                    var category = Category.findOrCreate({id: id});
                    this.product.get('categories').add(category);
                }.bind(this));
            }

            this.product.saveLocale(this.options.locale, {
                success: function (response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.products.saved', model);
                    } else {
                        this.load(model.id, this.options.locale);
                    }
                }.bind(this),
                error: function () {
                    this.sandbox.logger.log('error while saving product');
                }.bind(this)
            });
        },

        load: function (id, localization) {
            this.sandbox.emit('sulu.router.navigate', 'pim/products/' + localization + '/edit:' + id + '/details');
        },

        del: function (ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.util.each(ids, function(key, id) {
                        var product = Product.findOrCreate({id: id});
                        product.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        deleteProduct: function(id) {
            DeleteDialog.show(this.sandbox, Product.findOrCreate({id: id}));
        },

        addVariant: function (id) {
            this.product.get('variants').fetch(
                {
                    data: {
                        id: id
                    },
                    type: 'POST',
                    success: function (collection, response) {
                        delete response.parent; // FIXME this is necessary because of husky datagrid
                        this.sandbox.emit('husky.datagrid.variants-datagrid.record.remove', id);
                        this.sandbox.emit('husky.datagrid.record.add', response);
                    }.bind(this)
                }
            );
        },

        deleteVariants: function (ids) {
            this.confirmDeleteDialog(function (wasConfirmed) {
                if (wasConfirmed) {
                    this.product.get('variants').fetch({
                        success: function (collection) {
                            this.sandbox.util.each(ids, function (key, id) {
                                var product = collection.get(id);
                                product.urlRoot = collection.url() + '/';

                                product.destroy({
                                    success: function () {
                                        this.sandbox.emit('sulu.products.variant.deleted', id);
                                    }.bind(this)
                                });
                            }.bind(this));
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        confirmDeleteDialog: function (callbackFunction) {
            this.sandbox.emit(
                'sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',
                callbackFunction.bind(this, false),
                callbackFunction.bind(this, true)
            );
        },

        renderTabs: function () {
            this.product = new Product();

            var $tabContainer = this.sandbox.dom.createElement('<div/>'),
                component = {
                    name: 'products/components/content@suluproduct',
                    options: {
                        el: $tabContainer,
                        locale: this.options.locale
                    }
                },
                dfd = this.sandbox.data.deferred();

            this.html($tabContainer);

            if (!!this.options.id) {
                component.options.content = this.options.content;
                component.options.id = this.options.id;
                this.product.set({id: this.options.id});
                this.product.fetchLocale(this.options.locale, {
                    success: function (model) {
                        component.options.data = this.product;
                        component.options.productType = types[model.get('type').id];
                        this.sandbox.start([component]);
                        dfd.resolve();
                    }.bind(this),
                    error: function(){
                        this.sandbox.logger.error("error while fetching product");
                        dfd.reject();
                    }.bind(this)
                });
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'workflow', 'inactive');
                component.options.productType = this.options.productType;
                this.sandbox.start([component]);
                dfd.resolve();
            }

            return dfd.promise();
        },

        /**
         * Creates the view for the flat product list
         */
        renderList: function () {
            var $list = this.sandbox.dom.createElement('<div id="products-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'products/components/list@suluproduct', options: { el: $list}}
            ]);
        },

        /**
         * Creates the view for the product import
         */
        renderImport: function () {
            var $container = this.sandbox.dom.createElement('<div id="products-import"/>');
            this.html($container);
            this.sandbox.start([
                {name: 'products/components/import@suluproduct', options: { el: $container}}
            ]);
        }
    };
});
