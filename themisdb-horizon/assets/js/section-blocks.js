( function( wp ) {
    'use strict';

    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var RangeControl = wp.components.RangeControl;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var Spinner = wp.components.Spinner;
    var ServerSideRender = wp.serverSideRender;
    var useSelect = wp.data && wp.data.useSelect ? wp.data.useSelect : null;

    function CategorySelectControl( props ) {
        if ( ! useSelect ) {
            return el( TextControl, {
                label: __( 'Kategorie-Slug', 'themisdb-theme' ),
                value: props.value,
                onChange: props.onChange
            } );
        }

        var categories = useSelect( function( select ) {
            return select( 'core' ).getEntityRecords( 'taxonomy', 'category', {
                per_page: 100,
                orderby: 'name',
                order: 'asc',
                hide_empty: false
            } );
        }, [] );

        var options = [
            { label: __( 'Kategorie auswahlen', 'themisdb-theme' ), value: '' }
        ];
        var currentValue = props.value || '';
        var hasCurrent = '' === currentValue;

        if ( Array.isArray( categories ) ) {
            categories.forEach( function( term ) {
                if ( ! term || ! term.slug ) {
                    return;
                }
                options.push( {
                    label: term.name + ' (' + term.slug + ')',
                    value: term.slug
                } );
                if ( term.slug === currentValue ) {
                    hasCurrent = true;
                }
            } );
        }

        if ( currentValue && ! hasCurrent ) {
            options.unshift( {
                label: __( 'Aktuell', 'themisdb-theme' ) + ': ' + currentValue,
                value: currentValue
            } );
        }

        return el( SelectControl, {
            label: __( 'Kategorie', 'themisdb-theme' ),
            help: __( 'Wahle eine bestehende WordPress-Kategorie als Datenquelle.', 'themisdb-theme' ),
            value: currentValue,
            options: options,
            onChange: props.onChange
        } );
    }

    function controlsCommon( attrs, setAttributes, options ) {
        var sectionValue = attrs.section || options.defaultSection;

        return el(
            PanelBody,
            { title: __( 'Datenquelle', 'themisdb-theme' ), initialOpen: true },
            el( CategorySelectControl, {
                value: sectionValue,
                onChange: function( value ) {
                    setAttributes( { section: value } );
                }
            } ),
            el( RangeControl, {
                label: __( 'Anzahl Eintrage', 'themisdb-theme' ),
                min: 1,
                max: 24,
                value: attrs.perPage || options.defaultPerPage,
                onChange: function( value ) {
                    setAttributes( { perPage: value } );
                }
            } )
        );
    }

    function layoutToggles( attrs, setAttributes, labels ) {
        return el(
            PanelBody,
            { title: __( 'Layout', 'themisdb-theme' ), initialOpen: false },
            labels.columns ? el( RangeControl, {
                label: labels.columns,
                min: labels.columnsMin || 1,
                max: labels.columnsMax || 4,
                value: attrs.columns || labels.columnsDefault || 3,
                onChange: function( value ) { setAttributes( { columns: value } ); }
            } ) : null,
            labels.excerptWords ? el( RangeControl, {
                label: labels.excerptWords,
                min: 6,
                max: 80,
                value: attrs.excerptWords || labels.excerptWordsDefault || 20,
                onChange: function( value ) { setAttributes( { excerptWords: value } ); }
            } ) : null,
            typeof attrs.showHeader !== 'undefined' ? el( ToggleControl, {
                label: __( 'Sektionstitel anzeigen', 'themisdb-theme' ),
                checked: attrs.showHeader !== false,
                onChange: function( value ) { setAttributes( { showHeader: value } ); }
            } ) : null,
            typeof attrs.showDescription !== 'undefined' ? el( ToggleControl, {
                label: __( 'Kategoriebeschreibung anzeigen', 'themisdb-theme' ),
                checked: attrs.showDescription !== false,
                onChange: function( value ) { setAttributes( { showDescription: value } ); }
            } ) : null,
            typeof attrs.showImage !== 'undefined' ? el( ToggleControl, {
                label: __( 'Bild anzeigen', 'themisdb-theme' ),
                checked: attrs.showImage !== false,
                onChange: function( value ) { setAttributes( { showImage: value } ); }
            } ) : null,
            typeof attrs.showDate !== 'undefined' ? el( ToggleControl, {
                label: __( 'Datum anzeigen', 'themisdb-theme' ),
                checked: attrs.showDate !== false,
                onChange: function( value ) { setAttributes( { showDate: value } ); }
            } ) : null,
            typeof attrs.showExcerpt !== 'undefined' ? el( ToggleControl, {
                label: __( 'Textauszug anzeigen', 'themisdb-theme' ),
                checked: attrs.showExcerpt !== false,
                onChange: function( value ) { setAttributes( { showExcerpt: value } ); }
            } ) : null
        );
    }

    function previewMetaItems( attrs ) {
        var items = [];

        if ( attrs.section ) {
            items.push( __( 'Kategorie', 'themisdb-theme' ) + ': ' + attrs.section );
        }

        if ( typeof attrs.perPage !== 'undefined' ) {
            items.push( __( 'Einträge', 'themisdb-theme' ) + ': ' + attrs.perPage );
        }

        if ( typeof attrs.columns !== 'undefined' ) {
            items.push( __( 'Spalten', 'themisdb-theme' ) + ': ' + attrs.columns );
        }

        if ( typeof attrs.excerptWords !== 'undefined' ) {
            items.push( __( 'Wörter', 'themisdb-theme' ) + ': ' + attrs.excerptWords );
        }

        return items;
    }

    function previewHeader( title, attrs ) {
        var meta = previewMetaItems( attrs );

        return el(
            'div',
            { className: 'themisdb-editor-preview-header' },
            el(
                'div',
                { className: 'themisdb-editor-preview-heading' },
                el( 'strong', null, title ),
                el( 'span', { className: 'themisdb-editor-preview-subtitle' }, __( 'Serverseitige Vorschau', 'themisdb-theme' ) )
            ),
            meta.length ? el(
                'div',
                { className: 'themisdb-editor-preview-meta' },
                meta.map( function( item, index ) {
                    return el( 'span', { key: index, className: 'themisdb-editor-preview-chip' }, item );
                } )
            ) : null
        );
    }

    function loadingPreview() {
        return el(
            'div',
            { className: 'themisdb-editor-preview-loading' },
            el( Spinner, null ),
            el( 'span', null, __( 'Vorschau wird aktualisiert…', 'themisdb-theme' ) )
        );
    }

    function errorPreview( message ) {
        return el(
            'div',
            { className: 'themisdb-editor-preview-error' },
            el( 'strong', null, __( 'Vorschau konnte nicht geladen werden', 'themisdb-theme' ) ),
            el( 'p', null, message || __( 'Bitte Attribute prüfen oder später erneut laden.', 'themisdb-theme' ) )
        );
    }

    function blockPreview( name, attrs, title ) {
        return el(
            'div',
            { className: 'themisdb-editor-preview-shell', key: 'preview-shell' },
            previewHeader( title || name, attrs ),
            el(
                'div',
                { className: 'themisdb-editor-preview-body' },
                el( ServerSideRender, {
                    key: 'preview',
                    block: name,
                    attributes: attrs,
                    LoadingResponsePlaceholder: loadingPreview,
                    EmptyResponsePlaceholder: function () {
                        return errorPreview( __( 'Die Vorschau hat keine Ausgabe geliefert.', 'themisdb-theme' ) );
                    },
                    ErrorResponsePlaceholder: function ( response ) {
                        var message = response && response.responseJSON && response.responseJSON.message
                            ? response.responseJSON.message
                            : '';
                        return errorPreview( message );
                    }
                } )
            )
        );
    }

    registerBlockType( 'themisdb/gallery-grid', {
        apiVersion: 2,
        title: __( 'ThemisDB Galerie Grid', 'themisdb-theme' ),
        icon: 'format-gallery',
        category: 'widgets',
        description: __( 'Dynamische Galerie aus WordPress-Beitragen einer Kategorie.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'digital' },
            perPage: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'digital', defaultPerPage: 6 } ),
                    layoutToggles( attrs, props.setAttributes, {
                        columns: __( 'Spalten', 'themisdb-theme' ),
                        columnsMin: 2,
                        columnsMax: 4,
                        columnsDefault: 3
                    } )
                ),
                blockPreview( 'themisdb/gallery-grid', attrs, __( 'Galerie Grid', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/three-row-grid', {
        apiVersion: 2,
        title: __( 'ThemisDB 3-Row Grid', 'themisdb-theme' ),
        icon: 'screenoptions',
        category: 'widgets',
        description: __( 'Dynamisches 3-Spalten-Raster fur Module oder andere Sektionen.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'module' },
            perPage: { type: 'number', default: 6 },
            excerptWords: { type: 'number', default: 20 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true },
            showImage: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showExcerpt: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'module', defaultPerPage: 6 } ),
                    layoutToggles( attrs, props.setAttributes, {
                        excerptWords: __( 'Excerpt-Worter', 'themisdb-theme' ),
                        excerptWordsDefault: 20
                    } )
                ),
                blockPreview( 'themisdb/three-row-grid', attrs, __( '3-Row Grid', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/button-box-grid', {
        apiVersion: 2,
        title: __( 'ThemisDB Button Box Grid', 'themisdb-theme' ),
        icon: 'grid-view',
        category: 'widgets',
        description: __( 'Button-Box-Raster mit Links auf Beitrage einer Sektion.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'workflow' },
            perPage: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'workflow', defaultPerPage: 6 } ),
                    layoutToggles( attrs, props.setAttributes, {
                        columns: __( 'Spalten', 'themisdb-theme' ),
                        columnsMin: 2,
                        columnsMax: 4,
                        columnsDefault: 3
                    } )
                ),
                blockPreview( 'themisdb/button-box-grid', attrs, __( 'Button Box Grid', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/section-cards', {
        apiVersion: 2,
        title: __( 'ThemisDB Abschnitt Karten', 'themisdb-theme' ),
        icon: 'index-card',
        category: 'widgets',
        description: __( 'Dynamisches Kartenraster fur Inhaltssektionen.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'zahlen' },
            perPage: { type: 'number', default: 4 },
            columns: { type: 'number', default: 4 },
            excerptWords: { type: 'number', default: 20 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true },
            showImage: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showExcerpt: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'zahlen', defaultPerPage: 4 } ),
                    layoutToggles( attrs, props.setAttributes, {
                        columns: __( 'Spalten', 'themisdb-theme' ),
                        columnsMin: 1,
                        columnsMax: 4,
                        columnsDefault: 4,
                        excerptWords: __( 'Excerpt-Worter', 'themisdb-theme' ),
                        excerptWordsDefault: 20
                    } )
                ),
                blockPreview( 'themisdb/section-cards', attrs, __( 'Section Cards', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/section-feature', {
        apiVersion: 2,
        title: __( 'ThemisDB Abschnitt Feature', 'themisdb-theme' ),
        icon: 'align-pull-left',
        category: 'widgets',
        description: __( 'Hervorgehobener Einzelbeitrag aus einer Kategorie.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'evolution' },
            excerptWords: { type: 'number', default: 36 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'evolution', defaultPerPage: 1 } ),
                    layoutToggles( attrs, props.setAttributes, {
                        excerptWords: __( 'Excerpt-Worter', 'themisdb-theme' ),
                        excerptWordsDefault: 36
                    } )
                ),
                blockPreview( 'themisdb/section-feature', attrs, __( 'Section Feature', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/section-timeline', {
        apiVersion: 2,
        title: __( 'ThemisDB Abschnitt Timeline', 'themisdb-theme' ),
        icon: 'calendar-alt',
        category: 'widgets',
        description: __( 'Zeitliche Liste aktueller Inhalte einer Kategorie.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'aktuelles' },
            perPage: { type: 'number', default: 4 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'aktuelles', defaultPerPage: 4 } ),
                    layoutToggles( attrs, props.setAttributes, {} )
                ),
                blockPreview( 'themisdb/section-timeline', attrs, __( 'Timeline', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/section-faq', {
        apiVersion: 2,
        title: __( 'ThemisDB Abschnitt FAQ', 'themisdb-theme' ),
        icon: 'editor-help',
        category: 'widgets',
        description: __( 'FAQ-Liste aus WordPress-Beitragen einer Kategorie.', 'themisdb-theme' ),
        attributes: {
            section: { type: 'string', default: 'faq' },
            perPage: { type: 'number', default: 6 },
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    controlsCommon( attrs, props.setAttributes, { defaultSection: 'faq', defaultPerPage: 6 } ),
                    layoutToggles( attrs, props.setAttributes, {} )
                ),
                blockPreview( 'themisdb/section-faq', attrs, __( 'FAQ', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/contact-form', {
        apiVersion: 2,
        title: __( 'ThemisDB Kontaktformular', 'themisdb-theme' ),
        icon: 'email',
        category: 'widgets',
        description: __( 'Systemzugang-Anfrageformular des Themes.', 'themisdb-theme' ),
        attributes: {},
        edit: function() {
            return blockPreview( 'themisdb/contact-form', {}, __( 'Kontaktformular', 'themisdb-theme' ) );
        },
        save: function() {
            return null;
        }
    } );

    registerBlockType( 'themisdb/state-grid', {
        apiVersion: 2,
        title: __( 'ThemisDB Länderverbund', 'themisdb-theme' ),
        icon: 'location-alt',
        category: 'widgets',
        description: __( 'Länderverbund-Raster mit Wappen und Kürzeln der beteiligten Länder.', 'themisdb-theme' ),
        attributes: {
            showHeader: { type: 'boolean', default: true },
            showDescription: { type: 'boolean', default: true }
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return [
                el( InspectorControls, { key: 'inspector' },
                    layoutToggles( attrs, props.setAttributes, {} )
                ),
                blockPreview( 'themisdb/state-grid', attrs, __( 'State Grid', 'themisdb-theme' ) )
            ];
        },
        save: function() {
            return null;
        }
    } );

    if ( ! wp.blocks.getBlockType( 'themisdb/front-slider' ) ) {
        registerBlockType( 'themisdb/front-slider', {
            apiVersion: 2,
            title: __( 'ThemisDB Hero Slider', 'themisdb-theme' ),
            icon: 'images-alt2',
            category: 'widgets',
            description: __( 'Fallback-Slider des Themes, wenn das Front-Slider-Plugin nicht aktiv ist.', 'themisdb-theme' ),
            attributes: {
                posts: { type: 'number', default: 5 },
                interval: { type: 'number', default: 5000 },
                category: { type: 'string', default: '' },
                excerpt: { type: 'boolean', default: true },
                date: { type: 'boolean', default: true },
                cat_label: { type: 'boolean', default: true },
                autoplay: { type: 'boolean', default: true },
                overlay: { type: 'string', default: 'normal' }
            },
            edit: function( props ) {
                var attrs = props.attributes;

                return [
                    el( InspectorControls, { key: 'inspector' },
                        el(
                            PanelBody,
                            { title: __( 'Datenquelle', 'themisdb-theme' ), initialOpen: true },
                            el( CategorySelectControl, {
                                value: attrs.category || '',
                                onChange: function( value ) {
                                    props.setAttributes( { category: value } );
                                }
                            } ),
                            el( RangeControl, {
                                label: __( 'Anzahl Beiträge', 'themisdb-theme' ),
                                min: 1,
                                max: 20,
                                value: attrs.posts || 5,
                                onChange: function( value ) { props.setAttributes( { posts: value } ); }
                            } ),
                            el( RangeControl, {
                                label: __( 'Intervall (ms)', 'themisdb-theme' ),
                                min: 1000,
                                max: 30000,
                                step: 500,
                                value: attrs.interval || 5000,
                                onChange: function( value ) { props.setAttributes( { interval: value } ); }
                            } )
                        ),
                        el(
                            PanelBody,
                            { title: __( 'Anzeige', 'themisdb-theme' ), initialOpen: false },
                            el( ToggleControl, {
                                label: __( 'Autoplay', 'themisdb-theme' ),
                                checked: attrs.autoplay !== false,
                                onChange: function( value ) { props.setAttributes( { autoplay: value } ); }
                            } ),
                            el( ToggleControl, {
                                label: __( 'Kategorie anzeigen', 'themisdb-theme' ),
                                checked: attrs.cat_label !== false,
                                onChange: function( value ) { props.setAttributes( { cat_label: value } ); }
                            } ),
                            el( ToggleControl, {
                                label: __( 'Datum anzeigen', 'themisdb-theme' ),
                                checked: attrs.date !== false,
                                onChange: function( value ) { props.setAttributes( { date: value } ); }
                            } ),
                            el( ToggleControl, {
                                label: __( 'Textauszug anzeigen', 'themisdb-theme' ),
                                checked: attrs.excerpt !== false,
                                onChange: function( value ) { props.setAttributes( { excerpt: value } ); }
                            } ),
                            el( SelectControl, {
                                label: __( 'Overlay', 'themisdb-theme' ),
                                value: attrs.overlay || 'normal',
                                options: [
                                    { label: __( 'Normal', 'themisdb-theme' ), value: 'normal' },
                                    { label: __( 'Stark', 'themisdb-theme' ), value: 'strong' }
                                ],
                                onChange: function( value ) { props.setAttributes( { overlay: value } ); }
                            } )
                        )
                    ),
                    blockPreview( 'themisdb/front-slider', attrs, __( 'Front Slider', 'themisdb-theme' ) )
                ];
            },
            save: function() {
                return null;
            }
        } );
    }
} )( window.wp );
