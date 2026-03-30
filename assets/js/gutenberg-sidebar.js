/**
 * Ligase Schema Sidebar Panel for Gutenberg
 *
 * Adds a sidebar panel to the post editor showing:
 * - Schema validation status (errors + warnings)
 * - List of generated schema types
 * - Quick link to Google Rich Results Test
 */
( function( plugins, editPost, element, components, data, compose ) {
    if ( ! plugins || ! editPost || ! element ) return;

    var el             = element.createElement;
    var Fragment       = element.Fragment;
    var useState       = element.useState;
    var useEffect      = element.useEffect;
    var PluginSidebar  = editPost.PluginSidebar;
    var PanelBody      = components.PanelBody;
    var Button         = components.Button;
    var Spinner        = components.Spinner;
    var useSelect      = data.useSelect;

    function LigaseSidebar() {
        var postId   = useSelect( function( select ) { return select( 'core/editor' ).getCurrentPostId(); } );
        var postUrl  = useSelect( function( select ) {
            var link = select( 'core/editor' ).getPermalink();
            return link || '';
        } );

        var validation  = useState( null );
        var validating  = useState( false );

        var result      = validation[0];
        var setResult   = validation[1];
        var loading     = validating[0];
        var setLoading  = validating[1];

        function runValidation() {
            if ( ! postId || ! window.LigaseAPI ) return;
            setLoading( true );
            window.LigaseAPI.validatePost( postId, function( err, data ) {
                setLoading( false );
                if ( err ) {
                    setResult( { valid: false, errors: [ err ], warnings: [], types: [], json: '' } );
                } else {
                    setResult( data );
                }
            } );
        }

        // Auto-validate on load
        useEffect( function() {
            if ( postId ) {
                // Small delay to let the post load
                var timer = setTimeout( runValidation, 1500 );
                return function() { clearTimeout( timer ); };
            }
        }, [ postId ] );

        // Status icon
        var statusIcon = '?';
        var statusColor = '#6B7280';
        if ( result ) {
            if ( result.valid && ( ! result.warnings || result.warnings.length === 0 ) ) {
                statusIcon = '\u2713';  // checkmark
                statusColor = '#10B981';
            } else if ( result.valid ) {
                statusIcon = '!';
                statusColor = '#F59E0B';
            } else {
                statusIcon = '\u2717';  // X
                statusColor = '#EF4444';
            }
        }

        return el( PluginSidebar, {
            name: 'ligase-schema-sidebar',
            title: 'Ligase Schema',
            icon: el( 'span', { style: { color: statusColor, fontWeight: 'bold', fontSize: '16px' } }, statusIcon ),
        },
            el( PanelBody, { title: 'Walidacja schema', initialOpen: true },
                el( 'div', { style: { marginBottom: '12px' } },
                    el( Button, {
                        isPrimary: true,
                        onClick: runValidation,
                        disabled: loading,
                        style: { width: '100%', justifyContent: 'center' },
                    }, loading ? el( Spinner, null ) : 'Waliduj schema' )
                ),

                result && el( Fragment, null,
                    // Status badge
                    el( 'div', { style: { marginBottom: '12px', padding: '8px 10px', borderRadius: '4px', background: result.valid ? '#D1FAE5' : '#FEE2E2', color: result.valid ? '#065F46' : '#991B1B', fontSize: '13px', fontWeight: '600' } },
                        result.valid ? 'Schema poprawna' : 'Znaleziono bledy (' + result.errors.length + ')'
                    ),

                    // Types
                    result.types && result.types.length > 0 && el( 'div', { style: { marginBottom: '12px' } },
                        el( 'strong', { style: { fontSize: '11px', color: '#6B7280', textTransform: 'uppercase' } }, 'Typy schema:' ),
                        el( 'div', { style: { marginTop: '4px' } },
                            result.types.map( function( t, i ) {
                                return el( 'span', {
                                    key: i,
                                    style: { display: 'inline-block', background: '#F3F4F6', padding: '2px 8px', borderRadius: '3px', fontSize: '12px', marginRight: '4px', marginBottom: '4px' }
                                }, t );
                            } )
                        )
                    ),

                    // Errors
                    result.errors && result.errors.length > 0 && el( 'div', { style: { marginBottom: '8px' } },
                        result.errors.map( function( e, i ) {
                            return el( 'div', {
                                key: 'err-' + i,
                                style: { padding: '6px 8px', background: '#FEE2E2', color: '#991B1B', borderRadius: '3px', fontSize: '12px', marginBottom: '4px', borderLeft: '3px solid #EF4444' }
                            }, e );
                        } )
                    ),

                    // Warnings
                    result.warnings && result.warnings.length > 0 && el( 'div', { style: { marginBottom: '8px' } },
                        result.warnings.map( function( w, i ) {
                            return el( 'div', {
                                key: 'warn-' + i,
                                style: { padding: '6px 8px', background: '#FEF3C7', color: '#78350F', borderRadius: '3px', fontSize: '12px', marginBottom: '4px', borderLeft: '3px solid #F59E0B' }
                            }, w );
                        } )
                    ),

                    // Rich Results Test link
                    postUrl && el( 'div', { style: { marginTop: '12px' } },
                        el( Button, {
                            isSecondary: true,
                            href: 'https://search.google.com/test/rich-results?url=' + encodeURIComponent( postUrl ),
                            target: '_blank',
                            style: { width: '100%', justifyContent: 'center' },
                        }, 'Testuj w Google Rich Results' )
                    )
                )
            )
        );
    }

    plugins.registerPlugin( 'ligase-schema-sidebar', {
        render: LigaseSidebar,
    } );

} )(
    window.wp.plugins,
    window.wp.editPost,
    window.wp.element,
    window.wp.components,
    window.wp.data,
    window.wp.compose
);
