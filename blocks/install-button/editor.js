/* LH Web Application: Install the App block, editor side. The button is rendered on the server. */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var domain = 'lh-web-application';

	blocks.registerBlockType( 'lh-web-application/install-button', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var blockProps = blockEditor.useBlockProps( {
				className: attributes.textAlign ? 'has-text-align-' + attributes.textAlign : undefined
			} );

			return el(
				element.Fragment,
				null,
				el(
					blockEditor.BlockControls,
					{ group: 'block' },
					el( blockEditor.AlignmentControl, {
						value: attributes.textAlign,
						onChange: function ( value ) {
							setAttributes( { textAlign: value } );
						}
					} )
				),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Settings', domain ) },
						el( components.TextControl, {
							label: __( 'Trigger name', domain ),
							help: __( 'Sent to analytics when this button opens the install sheet, so you can tell your install buttons apart. Lower case letters, numbers, dashes and underscores.', domain ),
							value: attributes.trigger,
							onChange: function ( value ) {
								setAttributes( { trigger: value } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( components.ToggleControl, {
							label: __( 'Hide once installed', domain ),
							help: __( 'Hide the button in a browser that has installed the app. It is always hidden inside the app itself.', domain ),
							checked: !! attributes.hideWhenInstalled,
							onChange: function ( value ) {
								setAttributes( { hideWhenInstalled: value } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( blockEditor.RichText, {
						tagName: 'span',
						className: 'wp-block-lh-web-application-install-button__link wp-element-button',
						'aria-label': __( 'Button text', domain ),
						placeholder: __( 'Install the app', domain ),
						value: attributes.text,
						allowedFormats: [],
						withoutInteractiveFormatting: true,
						onChange: function ( value ) {
							setAttributes( { text: value } );
						}
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
