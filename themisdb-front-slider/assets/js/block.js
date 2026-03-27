( function( blocks, element, i18n, components, blockEditor, serverSideRender ) {
    'use strict';

    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelColorSettings = blockEditor.PanelColorSettings;
    var useBlockProps      = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var Spinner = components.Spinner;
    var ServerSideRender = serverSideRender;

    function previewMeta(attrs) {
        return [
            __( 'Beiträge', 'themisdb-front-slider' ) + ': ' + ( attrs.posts || 5 ),
            __( 'Intervall', 'themisdb-front-slider' ) + ': ' + ( attrs.interval || 5000 ) + ' ms',
            __( 'Layout', 'themisdb-front-slider' ) + ': ' + ( attrs.layout_preset || 'standard' )
        ];
    }

    function previewShell(attrs) {
        var meta = previewMeta(attrs);

        return el(
            'div',
            { className: 'themisdb-front-slider-editor-shell' },
            el(
                'div',
                { className: 'themisdb-front-slider-editor-header' },
                el(
                    'div',
                    { className: 'themisdb-front-slider-editor-heading' },
                    el( 'strong', null, __( 'Front Slider Vorschau', 'themisdb-front-slider' ) ),
                    el( 'span', null, __( 'Serverseitig gerenderte Live-Vorschau', 'themisdb-front-slider' ) )
                ),
                el(
                    'div',
                    { className: 'themisdb-front-slider-editor-meta' },
                    meta.map( function( item, index ) {
                        return el( 'span', { key: index, className: 'themisdb-front-slider-editor-chip' }, item );
                    } )
                )
            ),
            el(
                'div',
                { className: 'themisdb-front-slider-editor-body' },
                el( ServerSideRender, {
                    block: 'themisdb/front-slider',
                    attributes: attrs,
                    LoadingResponsePlaceholder: function () {
                        return el(
                            'div',
                            { className: 'themisdb-front-slider-editor-loading' },
                            el( Spinner, null ),
                            el( 'span', null, __( 'Slider-Vorschau wird geladen…', 'themisdb-front-slider' ) )
                        );
                    }
                } )
            )
        );
    }

    blocks.registerBlockType( 'themisdb/front-slider', {
        edit: function( props ) {
            var attrs = props.attributes;
            var blockProps = useBlockProps( {
                className: 'themisdb-front-slider-block-preview'
            } );

            return el(
                element.Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: __( 'Slider-Einstellungen', 'themisdb-front-slider' ),
                            initialOpen: true
                        },
                        el( RangeControl, {
                            label: __( 'Anzahl Beiträge', 'themisdb-front-slider' ),
                            min: 1,
                            max: 20,
                            value: attrs.posts,
                            onChange: function( value ) {
                                props.setAttributes( { posts: value || 5 } );
                            }
                        } ),
                        el( RangeControl, {
                            label: __( 'Intervall (ms)', 'themisdb-front-slider' ),
                            min: 1000,
                            max: 30000,
                            step: 500,
                            value: attrs.interval,
                            onChange: function( value ) {
                                props.setAttributes( { interval: value || 5000 } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Bildgröße', 'themisdb-front-slider' ),
                            value: attrs.img_size || 'large',
                            options: [
                                { label: __( 'Thumbnail', 'themisdb-front-slider' ),  value: 'thumbnail' },
                                { label: __( 'Medium',    'themisdb-front-slider' ),  value: 'medium' },
                                { label: __( 'Groß',     'themisdb-front-slider' ),  value: 'large' },
                                { label: __( 'Vollgröße', 'themisdb-front-slider' ), value: 'full' }
                            ],
                            onChange: function( value ) {
                                props.setAttributes( { img_size: value || 'large' } );
                            },
                            help: __( 'WordPress-Bildgröße für die Slide-Karte', 'themisdb-front-slider' )
                        } ),
                        el( SelectControl, {
                            label: __( 'Layout-Preset', 'themisdb-front-slider' ),
                            value: attrs.layout_preset || 'standard',
                            options: [
                                { label: __( 'Standard (2 Spalten)', 'themisdb-front-slider' ), value: 'standard' },
                                { label: __( 'Compact (nur Text)', 'themisdb-front-slider' ), value: 'compact' },
                                { label: __( 'Magazine (Vollbr.)', 'themisdb-front-slider' ), value: 'magazine' }
                            ],
                            onChange: function( value ) {
                                props.setAttributes( { layout_preset: value || 'standard' } );
                            },
                            help: __( 'Wähle ein vordefiniertes Layout-Design', 'themisdb-front-slider' )
                        } ),
                        el( TextControl, {
                            label: __( 'Kategorie-Slug (optional)', 'themisdb-front-slider' ),
                            value: attrs.category || '',
                            onChange: function( value ) {
                                props.setAttributes( { category: value } );
                            },
                            help: __( 'Leer lassen, um alle Kategorien einzubeziehen.', 'themisdb-front-slider' )
                        } ),
                        el( ToggleControl, {
                            label: __( 'Autoplay aktivieren', 'themisdb-front-slider' ),
                            checked: !! attrs.autoplay,
                            onChange: function( value ) {
                                props.setAttributes( { autoplay: value } );
                            }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Auszug anzeigen', 'themisdb-front-slider' ),
                            checked: !! attrs.excerpt,
                            onChange: function( value ) {
                                props.setAttributes( { excerpt: value } );
                            }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Datum anzeigen', 'themisdb-front-slider' ),
                            checked: !! attrs.date,
                            onChange: function( value ) {
                                props.setAttributes( { date: value } );
                            }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Kategorie-Label anzeigen', 'themisdb-front-slider' ),
                            checked: !! attrs.cat_label,
                            onChange: function( value ) {
                                props.setAttributes( { cat_label: value } );
                            }
                        } )
                    ),
                    el( PanelColorSettings, {
                        title: __( 'Farbe', 'themisdb-front-slider' ),
                        initialOpen: false,
                        colorSettings: [
                            {
                                value: attrs.accent_color || '#0284c7',
                                onChange: function( val ) {
                                    props.setAttributes( { accent_color: val || '#0284c7' } );
                                },
                                label: __( 'Akzentfarbe', 'themisdb-front-slider' )
                            }
                        ]
                    } )
                ),
                el( 'div', blockProps,
                    previewShell( attrs )
                )
            );
        },
        save: function() {
            return null;
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.i18n,
    window.wp.components,
    window.wp.blockEditor,
    window.wp.serverSideRender
);
