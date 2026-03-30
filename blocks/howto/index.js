( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var RichText = blockEditor.RichText;
    var useBlockProps = blockEditor.useBlockProps;
    var Button = components.Button;
    var TextControl = components.TextControl;

    blocks.registerBlockType( 'ligase/howto', {
        edit: function( props ) {
            var name = props.attributes.name || '';
            var totalTime = props.attributes.totalTime || '';
            var steps = props.attributes.steps || [];
            var blockProps = useBlockProps();

            function updateStep( index, field, value ) {
                var newSteps = steps.slice();
                newSteps[ index ] = Object.assign( {}, newSteps[ index ] );
                newSteps[ index ][ field ] = value;
                props.setAttributes( { steps: newSteps } );
            }

            function addStep() {
                var newSteps = steps.concat( [ { name: '', text: '' } ] );
                props.setAttributes( { steps: newSteps } );
            }

            function removeStep( index ) {
                var newSteps = steps.filter( function( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { steps: newSteps } );
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
                    }, 'HowTo Schema' ),
                    el( 'p', {
                        style: {
                            margin: 0,
                            fontSize: '12px',
                            color: '#6B7280'
                        }
                    }, steps.length + ' kroków — schema HowTo generowana automatycznie' )
                ),

                el( 'div', {
                    style: {
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '12px',
                        marginBottom: '16px'
                    }
                },
                    el( TextControl, {
                        label: 'Nazwa instrukcji',
                        value: name,
                        onChange: function( val ) { props.setAttributes( { name: val } ); },
                        placeholder: 'np. Jak zainstalować WordPress'
                    } ),
                    el( TextControl, {
                        label: 'Całkowity czas (ISO 8601)',
                        value: totalTime,
                        onChange: function( val ) { props.setAttributes( { totalTime: val } ); },
                        placeholder: 'np. PT30M, PT1H, PT2H30M',
                        help: 'Format: PT##H##M — np. PT1H30M = 1 godz. 30 min.'
                    } )
                ),

                steps.map( function( step, index ) {
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
                            el( 'div', {
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px'
                                }
                            },
                                el( 'span', {
                                    style: {
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        width: '24px',
                                        height: '24px',
                                        borderRadius: '50%',
                                        background: '#1E429F',
                                        color: '#FFFFFF',
                                        fontSize: '12px',
                                        fontWeight: 700
                                    }
                                }, String( index + 1 ) ),
                                el( 'span', {
                                    style: {
                                        fontSize: '11px',
                                        fontWeight: 700,
                                        color: '#6B7280',
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.5px'
                                    }
                                }, 'Krok ' + ( index + 1 ) )
                            ),
                            el( Button, {
                                isDestructive: true,
                                isSmall: true,
                                onClick: function() { removeStep( index ); },
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
                            }, 'Tytuł kroku:' ),
                            el( RichText, {
                                tagName: 'p',
                                value: step.name,
                                onChange: function( val ) { updateStep( index, 'name', val ); },
                                placeholder: 'np. Pobierz WordPress',
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
                            }, 'Opis kroku:' ),
                            el( RichText, {
                                tagName: 'div',
                                multiline: 'p',
                                value: step.text,
                                onChange: function( val ) { updateStep( index, 'text', val ); },
                                placeholder: 'Opisz szczegółowo co należy zrobić w tym kroku...',
                                style: {
                                    background: '#FFFFFF',
                                    border: '1px solid #E5E7EB',
                                    borderRadius: '6px',
                                    padding: '8px 12px',
                                    fontSize: '14px',
                                    minHeight: '60px'
                                }
                            } )
                        )
                    );
                } ),

                el( Button, {
                    isPrimary: true,
                    onClick: addStep,
                    style: { marginTop: '8px' }
                }, '+ Dodaj krok' )
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
