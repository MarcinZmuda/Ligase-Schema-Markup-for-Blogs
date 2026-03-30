( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var RichText = blockEditor.RichText;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var Button = components.Button;
    var PanelBody = components.PanelBody;

    blocks.registerBlockType( 'ligase/faq', {
        edit: function( props ) {
            var items = props.attributes.items || [];
            var blockProps = useBlockProps();

            function updateItem( index, field, value ) {
                var newItems = items.slice();
                newItems[ index ] = Object.assign( {}, newItems[ index ] );
                newItems[ index ][ field ] = value;
                props.setAttributes( { items: newItems } );
            }

            function addItem() {
                var newItems = items.concat( [ { question: '', answer: '' } ] );
                props.setAttributes( { items: newItems } );
            }

            function removeItem( index ) {
                var newItems = items.filter( function( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { items: newItems } );
            }

            return el( 'div', blockProps,
                el( 'div', {
                    style: {
                        borderLeft: '4px solid #1E429F',
                        paddingLeft: '16px',
                        marginBottom: '16px'
                    }
                },
                    el( 'h3', {
                        style: {
                            margin: '0 0 4px',
                            fontSize: '16px',
                            fontWeight: 700,
                            color: '#1E429F'
                        }
                    }, 'FAQ Schema' ),
                    el( 'p', {
                        style: {
                            margin: 0,
                            fontSize: '12px',
                            color: '#6B7280'
                        }
                    }, items.length + ' pytań — schema FAQPage generowana automatycznie' )
                ),

                items.map( function( item, index ) {
                    return el( 'div', {
                        key: index,
                        style: {
                            background: '#F9FAFB',
                            border: '1px solid #E5E7EB',
                            borderRadius: '8px',
                            padding: '14px',
                            marginBottom: '10px',
                            position: 'relative'
                        }
                    },
                        el( 'div', {
                            style: {
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                marginBottom: '8px'
                            }
                        },
                            el( 'span', {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 700,
                                    color: '#6B7280',
                                    textTransform: 'uppercase',
                                    letterSpacing: '0.5px'
                                }
                            }, 'Pytanie ' + ( index + 1 ) ),
                            el( Button, {
                                isDestructive: true,
                                isSmall: true,
                                onClick: function() { removeItem( index ); },
                                icon: 'trash'
                            } )
                        ),

                        el( 'div', {
                            style: { marginBottom: '8px' }
                        },
                            el( 'label', {
                                style: {
                                    display: 'block',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: '#374151',
                                    marginBottom: '4px'
                                }
                            }, 'Pytanie:' ),
                            el( RichText, {
                                tagName: 'p',
                                value: item.question,
                                onChange: function( val ) { updateItem( index, 'question', val ); },
                                placeholder: 'Wpisz pytanie...',
                                allowedFormats: [],
                                style: {
                                    background: '#FFFFFF',
                                    border: '1px solid #E5E7EB',
                                    borderRadius: '6px',
                                    padding: '8px 12px',
                                    fontSize: '14px',
                                    minHeight: '20px'
                                }
                            } )
                        ),

                        el( 'div', null,
                            el( 'label', {
                                style: {
                                    display: 'block',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: '#374151',
                                    marginBottom: '4px'
                                }
                            }, 'Odpowiedź:' ),
                            el( RichText, {
                                tagName: 'div',
                                multiline: 'p',
                                value: item.answer,
                                onChange: function( val ) { updateItem( index, 'answer', val ); },
                                placeholder: 'Wpisz odpowiedź...',
                                style: {
                                    background: '#FFFFFF',
                                    border: '1px solid #E5E7EB',
                                    borderRadius: '6px',
                                    padding: '8px 12px',
                                    fontSize: '14px',
                                    minHeight: '60px'
                                }
                            } ),
                            // Word counter
                            ( function() {
                                var text = ( item.answer || '' ).replace( /<[^>]+>/g, '' ).trim();
                                var words = text ? text.split( /\s+/ ).length : 0;
                                var color = ( words >= 40 && words <= 60 ) ? '#10B981' : ( words > 60 ? '#F59E0B' : '#9CA3AF' );
                                var hint = ( words >= 40 && words <= 60 ) ? 'Optymalna dlugosc' : ( words < 40 ? 'Min. 40 slow dla AI' : 'Zalecane 40-60 slow' );
                                return el( 'span', {
                                    style: { fontSize: '11px', color: color, marginTop: '4px', display: 'block' }
                                }, words + ' slow — ' + hint );
                            } )()
                        )
                    );
                } ),

                el( Button, {
                    isPrimary: true,
                    onClick: addItem,
                    style: { marginTop: '8px' }
                }, '+ Dodaj pytanie' )
            );
        },

        save: function() {
            // Schema is generated by PHP, not rendered on the frontend
            return null;
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components
);
