import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	// Fully dynamic block — PHP (render.php) generates all frontend markup
	// from the ACF field values, so there is nothing to save into post_content.
	save: () => null,
} );
