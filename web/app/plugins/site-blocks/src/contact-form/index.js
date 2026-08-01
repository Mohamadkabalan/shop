import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	// Fully dynamic block — PHP (render.php) generates the actual <form>
	// markup, plus any success/error notice, so there is nothing to save.
	save: () => null,
} );
