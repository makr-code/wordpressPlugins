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
    var ServerSideRender = serverSideRender;

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
                            value: attrs.layout_preset || 'clean',
                            options: [
                                { label: __( 'Clean', 'themisdb-front-slider' ), value: 'clean' },
                                { label: __( 'Magazine', 'themisdb-front-slider' ), value: 'magazine' },
                                { label: __( 'Compact', 'themisdb-front-slider' ), value: 'compact' }
                            ],
                            onChange: function( value ) {
                                props.setAttributes( { layout_preset: value || 'clean' } );
                            },
                            help: __( 'Visuelle Stilvariante des Sliders', 'themisdb-front-slider' )
                        } ),
                        el( TextControl, {
                            label: __( 'Kategorie-Slug (optional)', 'themisdb-front-slider' ),
                            value: attrs.category || '',
                            onChange: function( value ) {
                                props.setAttributes( { category: value } );
                            },
                            help: __( 'Leer lassen, um alle Kategorien einzubeziehen.', 'themisdb-front-slider' )
                        } ),
                        el( TextControl, {
                            label: __( 'Button-Text', 'themisdb-front-slider' ),
                            value: attrs.readmore_text || 'Weiterlesen →',
                            onChange: function( value ) {
                                props.setAttributes( { readmore_text: value } );
                            },
                            help: __( 'Beschriftung des \u201eWeiterlesen\u201c-Buttons', 'themisdb-front-slider' )
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
                    el( ServerSideRender, {
                        block: 'themisdb/front-slider',
                        attributes: attrs
                    } )
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
