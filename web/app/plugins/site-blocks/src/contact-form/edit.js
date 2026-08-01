import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import './editor.css';

/**
 * Static placeholder only — no live form here. The real <form>, its
 * validation, and the submission handling all live server-side
 * (render.php + ContactFormHandler.php), so there's nothing for this
 * component to wire up.
 */
export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'site-blocks-contact-form-editor',
	} );

	return (
		<div { ...blockProps }>
			<p className="site-blocks-contact-form-editor__label">
				{ __( 'Contact Form', 'site-blocks' ) }
			</p>
			<div className="site-blocks-contact-form-editor__field" />
			<div className="site-blocks-contact-form-editor__field" />
			<div className="site-blocks-contact-form-editor__field site-blocks-contact-form-editor__field--tall" />
			<div className="site-blocks-contact-form-editor__button">
				{ __( 'Send message', 'site-blocks' ) }
			</div>
			<Notice status="info" isDismissible={ false }>
				{ __( 'Live form available on the frontend.', 'site-blocks' ) }
			</Notice>
		</div>
	);
}
