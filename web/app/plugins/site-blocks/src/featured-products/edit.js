import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Notice } from '@wordpress/components';
import './editor.css';

/**
 * Editor-only mock — no ServerSideRender, no live WooCommerce data. The
 * actual product/title/columns fields are managed by ACF (see
 * ../../src/FieldGroups.php) and edited via ACF's own block fields UI; this
 * component only draws a static placeholder grid so the admin can see
 * roughly what the block will look like, plus a note pointing them to the
 * real preview on the frontend.
 *
 * `previewColumns` below is a plain block attribute used ONLY to size this
 * mock grid — it is intentionally separate from the real ACF "columns"
 * field that render.php reads on the frontend, since this component never
 * fetches real field data.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { previewColumns } = attributes;
	const blockProps = useBlockProps( {
		className: 'site-blocks-featured-products-editor',
	} );
	const cardCount = Number( previewColumns ) || 3;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'site-blocks' ) }>
					<SelectControl
						label={ __( 'Columns (preview only)', 'site-blocks' ) }
						help={ __(
							'Sizes this editor mock only. The real column count is set on the "Number of Columns" ACF field for this block.',
							'site-blocks'
						) }
						value={ previewColumns }
						options={ [
							{ label: '2', value: '2' },
							{ label: '3', value: '3' },
							{ label: '4', value: '4' },
						] }
						onChange={ ( value ) =>
							setAttributes( { previewColumns: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<p className="site-blocks-featured-products-editor__label">
					{ __( 'Featured Products', 'site-blocks' ) }
				</p>
				<div
					className="site-blocks-featured-products-editor__grid"
					style={ { '--columns': cardCount } }
				>
					{ Array.from( { length: cardCount } ).map( ( _, index ) => (
						<div
							className="site-blocks-featured-products-editor__card"
							key={ index }
						>
							<div className="site-blocks-featured-products-editor__image" />
							<div className="site-blocks-featured-products-editor__title">
								{ __( 'Product title', 'site-blocks' ) }
							</div>
							<div className="site-blocks-featured-products-editor__price">
								{ __( '$0.00', 'site-blocks' ) }
							</div>
						</div>
					) ) }
				</div>
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Products preview available on frontend. Choose the title, products, and columns in the ACF fields for this block.',
						'site-blocks'
					) }
				</Notice>
			</div>
		</>
	);
}
