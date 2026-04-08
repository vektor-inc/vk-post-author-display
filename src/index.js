/**
 * VK Post Author Display - Block Editor Panel
 * ブロックエディタ用サイドバーパネル
 *
 * Replaces the legacy add_meta_box() with PluginDocumentSettingPanel
 * so that WordPress 7.0 RTC (Real-Time Collaboration) is not blocked.
 *
 * @package vk-post-author-display
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { CheckboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

const PadHidePanel = () => {
	const { postType, candidatePostTypes } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postType: editor.getCurrentPostType(),
			candidatePostTypes: window.padEditor?.postTypes || [],
		};
	}, [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( ! candidatePostTypes.includes( postType ) ) {
		return null;
	}

	const isHidden = meta?.pad_hide_post_author === 'true';

	return (
		<PluginDocumentSettingPanel
			name="pad-hide-panel"
			title={ __( 'Post Author Display', 'vk-post-author-display' ) }
			className="pad-hide-panel"
		>
			<CheckboxControl
				label={ __(
					"Don't display post author",
					'vk-post-author-display'
				) }
				checked={ isHidden }
				onChange={ ( checked ) =>
					setMeta( {
						...meta,
						pad_hide_post_author: checked ? 'true' : '',
					} )
				}
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'pad-hide-panel', {
	render: PadHidePanel,
} );
