<?php
/**
 * FAQ Meta Box Template
 *
 * This file is used to markup the admin-facing meta box for FAQ content.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="qfm-meta-box">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="qfm_faq_question"><?php esc_html_e( 'Question', 'quick-faq-markup' ); ?></label>
			</th>
			<td>
				<textarea 
					id="qfm_faq_question" 
					name="qfm_faq_question" 
					class="large-text" 
					rows="3" 
					placeholder="<?php esc_attr_e( 'Enter the frequently asked question here...', 'quick-faq-markup' ); ?>"
					required
				><?php echo esc_textarea( $question ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'The question that visitors frequently ask. This will be displayed as the clickable header.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row">
				<label for="qfm_faq_answer"><?php esc_html_e( 'Answer', 'quick-faq-markup' ); ?></label>
			</th>
			<td>
				<?php
				// Use wp_editor for rich text editing
				wp_editor(
					$answer,
					'qfm_faq_answer',
					array(
						'textarea_name' => 'qfm_faq_answer',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny'         => true,
						'quicktags'     => array(
							'buttons' => 'strong,em,ul,ol,li,link,close'
						),
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo',
							'toolbar2' => '',
							'toolbar3' => '',
						),
					)
				);
				?>
				<p class="description">
					<?php esc_html_e( 'The detailed answer to the question. HTML formatting is supported for links, lists, and basic formatting.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
		
		<tr>
			<th scope="row">
				<label for="qfm_faq_order"><?php esc_html_e( 'Display Order', 'quick-faq-markup' ); ?></label>
			</th>
			<td>
				<input 
					type="number" 
					id="qfm_faq_order" 
					name="qfm_faq_order" 
					class="small-text" 
					value="<?php echo esc_attr( get_post_field( 'menu_order', $post->ID ) ); ?>" 
					min="0"
					step="1"
				/>
				<p class="description">
					<?php esc_html_e( 'Controls the display order of this FAQ. Lower numbers appear first. You can also drag and drop to reorder in the FAQ list.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
	</table>
	
	<div class="qfm-shortcode-hint">
		<h4><?php esc_html_e( 'Shortcode Usage', 'quick-faq-markup' ); ?></h4>
		<p><?php esc_html_e( 'After saving this FAQ, you can display it using:', 'quick-faq-markup' ); ?></p>
		<code>[qfm_faq ids="<?php echo esc_attr( $post->ID ); ?>"]</code>
		<p><?php esc_html_e( 'Or display all FAQs with:', 'quick-faq-markup' ); ?></p>
		<code>[qfm_faq]</code>
		<p><?php esc_html_e( 'Or display FAQs in a specific category:', 'quick-faq-markup' ); ?></p>
		<code>[qfm_faq category="category-slug"]</code>
	</div>
</div>

<style>
.qfm-meta-box .form-table th {
	width: 150px;
	padding-left: 10px;
}

.qfm-meta-box .form-table td {
	padding-left: 10px;
}

.qfm-shortcode-hint {
	margin-top: 20px;
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.qfm-shortcode-hint h4 {
	margin-top: 0;
	margin-bottom: 10px;
	font-size: 14px;
}

.qfm-shortcode-hint code {
	background: #fff;
	padding: 4px 8px;
	border: 1px solid #ddd;
	border-radius: 3px;
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
}

.qfm-shortcode-hint p {
	margin: 8px 0;
}
</style>