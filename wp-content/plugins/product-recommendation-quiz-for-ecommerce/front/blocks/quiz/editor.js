/**
 * Editor script for the Product Recommendation Quiz block.
 *
 * Hand-authored against the global wp.* packages (no JSX, no build step). The
 * block is dynamic: save() returns null and the server render callback emits the
 * delivery markup. In the editor the block shows a LIVE preview of the quiz
 * (an iframe to the hosted quiz, the same source the published page uses); the
 * Quiz ID and other options live only in the sidebar inspector.
 *
 * The block name and text domain below are the canonical (eCommerce) tokens; the
 * single-source build substitutes them per distribution, so the editor block
 * name always matches the PHP-registered block.json name for that artifact.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var Placeholder = components.Placeholder;

	// Origin of the hosted quiz, provided by PHP (respects dev/prod); falls back
	// to production if the localized data is unavailable.
	var adminOrigin = ( window.prqQuizBlock && window.prqQuizBlock.adminOrigin )
		? window.prqQuizBlock.adminOrigin
		: 'https://admin.revenuehunt.com';

	blocks.registerBlockType( 'revenuehunt/product-recommendation-quiz-for-ecommerce', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Quiz settings', 'product-recommendation-quiz-for-ecommerce' ), initialOpen: true },
					el( TextControl, {
						label: __( 'Quiz ID', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'The code from your quiz Share link, e.g. the CODE in /public/quiz/CODE.', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.id,
						onChange: function ( value ) {
							setAttributes( { id: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Quiz height', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						help: __( 'Initial height of the quiz. If it is not tall enough, it expands to fit the content after the first question (unless a fixed height is set).', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.height,
						onChange: function ( value ) {
							setAttributes( { height: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( SelectControl, {
						label: __( 'Height unit', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'Pixels is a fixed height; vh is a percentage of the screen height.', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.heightUnit,
						options: [
							{ label: __( 'Pixels (px)', 'product-recommendation-quiz-for-ecommerce' ), value: 'px' },
							{ label: __( 'Viewport height (vh)', 'product-recommendation-quiz-for-ecommerce' ), value: 'vh' }
						],
						onChange: function ( value ) {
							setAttributes( { heightUnit: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Fixed height', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'When enabled the quiz stays at the height above instead of expanding to fit its content.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.fixedHeight,
						onChange: function ( value ) {
							setAttributes( { fixedHeight: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Auto-scroll', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'When enabled the page scrolls to the quiz as the shopper moves through it.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.autoscroll,
						onChange: function ( value ) {
							setAttributes( { autoscroll: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Full width quiz', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'Enable this to make the quiz span the full width of the screen.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.fullWidth,
						onChange: function ( value ) {
							setAttributes( { fullWidth: value } );
						}
					} )
				)
			);

			var content;
			if ( attributes.id ) {
				// Live preview honors the chosen unit (px/vh) literally, matching
				// what the published page emits; min-height keeps it visible.
				content = el( 'iframe', {
					src: adminOrigin + '/public/quiz/' + encodeURIComponent( attributes.id ),
					title: __( 'Product Recommendation Quiz preview', 'product-recommendation-quiz-for-ecommerce' ),
					style: {
						width: '100%',
						height: ( attributes.height || 600 ) + ( attributes.heightUnit || 'px' ),
						minHeight: '150px',
						border: '0',
						display: 'block',
						// Preview only: let clicks select the block instead of the quiz.
						pointerEvents: 'none'
					}
				} );
			} else {
				content = el(
					Placeholder,
					{
						icon: 'format-chat',
						label: __( 'Product Recommendation Quiz', 'product-recommendation-quiz-for-ecommerce' ),
						instructions: __( 'Enter your Quiz ID in the block settings on the right to preview and display the quiz.', 'product-recommendation-quiz-for-ecommerce' )
					}
				);
			}

			return el( 'div', blockProps, inspector, content );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
