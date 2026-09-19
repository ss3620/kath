/**
 * Editor script for the three app-embed blocks (auto-popup, chat-button,
 * link-popup).
 *
 * Hand-authored against the global wp.* packages (no JSX, no build step), the
 * same pattern as the inline quiz block. All three are dynamic: save() returns
 * null and the PHP render callbacks emit the embed.js markers. The editor shows
 * a placeholder card (auto-popup/chat have no inline visual — embed.js builds
 * them on the live page) with the options in the sidebar inspector.
 *
 * The block names below carry no canonical tokens, so they are identical in
 * both distribution artifacts; the text domain IS substituted per-target by the
 * single-source build, so every __() resolves against the right .pot.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var Placeholder = components.Placeholder;

	// Shared Quiz ID control.
	function quizIdControl( attributes, setAttributes ) {
		return el( TextControl, {
			label: __( 'Quiz ID', 'product-recommendation-quiz-for-ecommerce' ),
			help: __( 'The code from your quiz Share link, e.g. the CODE in /public/quiz/CODE.', 'product-recommendation-quiz-for-ecommerce' ),
			value: attributes.quizId,
			onChange: function ( value ) {
				setAttributes( { quizId: value } );
			}
		} );
	}

	function placeholder( icon, label, instructions ) {
		return el( Placeholder, { icon: icon, label: label, instructions: instructions } );
	}

	/* ---------- Auto Popup ---------- */
	blocks.registerBlockType( 'revenuehunt/auto-popup-quiz', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Auto popup settings', 'product-recommendation-quiz-for-ecommerce' ), initialOpen: true },
					quizIdControl( attributes, setAttributes ),
					el( TextControl, {
						label: __( 'Timeout (seconds)', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						value: attributes.timeout,
						onChange: function ( value ) {
							setAttributes( { timeout: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Exit intent', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'Also open when the shopper moves to leave the page.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.exitIntent,
						onChange: function ( value ) {
							setAttributes( { exitIntent: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Aggressive', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'Reopen more persistently.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.aggressive,
						onChange: function ( value ) {
							setAttributes( { aggressive: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Popup width (px)', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						value: attributes.popupWidth,
						onChange: function ( value ) {
							setAttributes( { popupWidth: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( TextControl, {
						label: __( 'Popup height (px)', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						value: attributes.popupHeight,
						onChange: function ( value ) {
							setAttributes( { popupHeight: parseInt( value, 10 ) || 0 } );
						}
					} )
				)
			);

			var card = placeholder(
				'external',
				__( 'Auto Popup Quiz', 'product-recommendation-quiz-for-ecommerce' ),
				attributes.quizId
					? __( 'Fires automatically on pages that use this template.', 'product-recommendation-quiz-for-ecommerce' )
					: __( 'Enter your Quiz ID in the block settings on the right.', 'product-recommendation-quiz-for-ecommerce' )
			);

			return el( 'div', blockProps, inspector, card );
		},
		save: function () {
			return null;
		}
	} );

	/* ---------- Chat Button ---------- */
	blocks.registerBlockType( 'revenuehunt/chat-button-quiz', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Chat button settings', 'product-recommendation-quiz-for-ecommerce' ), initialOpen: true },
					quizIdControl( attributes, setAttributes ),
					el( TextControl, {
						label: __( 'Button color', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.color,
						onChange: function ( value ) {
							setAttributes( { color: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Greeting text', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.greeting,
						onChange: function ( value ) {
							setAttributes( { greeting: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Notification dot', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.dot,
						onChange: function ( value ) {
							setAttributes( { dot: value } );
						}
					} ),
					el( ToggleControl, {
						label: __( 'Hidden', 'product-recommendation-quiz-for-ecommerce' ),
						help: __( 'Keep the button hidden initially.', 'product-recommendation-quiz-for-ecommerce' ),
						checked: attributes.hide,
						onChange: function ( value ) {
							setAttributes( { hide: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Popup width (px)', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						value: attributes.popupWidth,
						onChange: function ( value ) {
							setAttributes( { popupWidth: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( TextControl, {
						label: __( 'Popup height (px)', 'product-recommendation-quiz-for-ecommerce' ),
						type: 'number',
						value: attributes.popupHeight,
						onChange: function ( value ) {
							setAttributes( { popupHeight: parseInt( value, 10 ) || 0 } );
						}
					} )
				)
			);

			var card = placeholder(
				'format-chat',
				__( 'Chat Button Quiz', 'product-recommendation-quiz-for-ecommerce' ),
				attributes.quizId
					? __( 'A floating chat button appears on pages that use this template.', 'product-recommendation-quiz-for-ecommerce' )
					: __( 'Enter your Quiz ID in the block settings on the right.', 'product-recommendation-quiz-for-ecommerce' )
			);

			return el( 'div', blockProps, inspector, card );
		},
		save: function () {
			return null;
		}
	} );

	/* ---------- Link Popup ---------- */
	blocks.registerBlockType( 'revenuehunt/link-popup-quiz', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Link popup settings', 'product-recommendation-quiz-for-ecommerce' ), initialOpen: true },
					quizIdControl( attributes, setAttributes ),
					el( TextControl, {
						label: __( 'Button label', 'product-recommendation-quiz-for-ecommerce' ),
						value: attributes.label,
						onChange: function ( value ) {
							setAttributes( { label: value } );
						}
					} )
				)
			);

			var label = attributes.label || __( 'Take the quiz', 'product-recommendation-quiz-for-ecommerce' );
			var preview = el(
				'div',
				{ className: 'wp-block-button' },
				el( 'span', { className: 'wp-block-button__link' }, label )
			);

			var content = attributes.quizId
				? preview
				: placeholder(
					'admin-links',
					__( 'Link Popup Quiz', 'product-recommendation-quiz-for-ecommerce' ),
					__( 'Enter your Quiz ID in the block settings on the right.', 'product-recommendation-quiz-for-ecommerce' )
				);

			return el( 'div', blockProps, inspector, content );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
