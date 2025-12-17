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
				<label for="qfm_faq_order"><?php esc_html_e( 'Global Order', 'quick-faq-markup' ); ?></label>
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
					<?php esc_html_e( 'Global order across all categories. This is automatically calculated based on category orders. Leave empty for auto-increment.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
		
		<?php
		// Display category-specific orders if categories are assigned
		$post_categories = get_the_terms( $post->ID, 'qfm_faq_category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) :
		?>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Category Orders', 'quick-faq-markup' ); ?>
			</th>
			<td>
				<div class="qfm-category-orders">
					<?php
					// Get category order instance
					global $quick_faq_markup_plugin;
					$category_order = null;
					if ( isset( $quick_faq_markup_plugin ) && method_exists( $quick_faq_markup_plugin, 'get_category_order' ) ) {
						$category_order = $quick_faq_markup_plugin->get_category_order();
					}
					
					foreach ( $post_categories as $category ) :
						$category_order_value = '';
						if ( $category_order ) {
							$category_order_value = $category_order->get_faq_order_in_category( $post->ID, $category->term_id );
							if ( $category_order_value === false ) {
								$category_order_value = '';
							}
						}
					?>
					<div class="qfm-category-order-item">
						<label for="qfm_category_order_<?php echo esc_attr( $category->term_id ); ?>">
							<strong><?php echo esc_html( $category->name ); ?>:</strong>
						</label>
						<input
							type="number"
							id="qfm_category_order_<?php echo esc_attr( $category->term_id ); ?>"
							name="qfm_category_order[<?php echo esc_attr( $category->term_id ); ?>]"
							class="small-text qfm-category-order-input"
							value="<?php echo esc_attr( $category_order_value ); ?>"
							min="1"
							step="1"
							placeholder="<?php esc_attr_e( 'Auto', 'quick-faq-markup' ); ?>"
						/>
						<span class="qfm-category-order-help">
							<?php
							/* translators: %s: category name */
							printf( esc_html__( 'Position within %s category', 'quick-faq-markup' ), esc_html( $category->name ) );
							?>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Set the order position within each assigned category. Lower numbers appear first. Leave empty for automatic ordering.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
		<?php endif; ?>
	</table>
	
	<div class="qfm-usage-hint">
		<h4><?php esc_html_e( 'Usage Instructions', 'quick-faq-markup' ); ?></h4>
		<p><strong><?php esc_html_e( 'Question:', 'quick-faq-markup' ); ?></strong> <?php esc_html_e( 'Enter your question in the title field above.', 'quick-faq-markup' ); ?></p>
		<p><strong><?php esc_html_e( 'Answer:', 'quick-faq-markup' ); ?></strong> <?php esc_html_e( 'Provide the detailed answer using the rich text editor.', 'quick-faq-markup' ); ?></p>
		<p><strong><?php esc_html_e( 'Global Order:', 'quick-faq-markup' ); ?></strong> <?php esc_html_e( 'Automatically calculated from category orders. Can be manually set for fine-tuning.', 'quick-faq-markup' ); ?></p>
		<p><strong><?php esc_html_e( 'Category Orders:', 'quick-faq-markup' ); ?></strong> <?php esc_html_e( 'Set specific positions within each assigned category. Leave empty for automatic ordering.', 'quick-faq-markup' ); ?></p>
	</div>
	
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

.qfm-usage-hint {
	margin-top: 20px;
	padding: 15px;
	background: #e7f3ff;
	border: 1px solid #bde0ff;
	border-radius: 4px;
}

.qfm-usage-hint h4 {
	margin-top: 0;
	margin-bottom: 10px;
	font-size: 14px;
	color: #0073aa;
}

.qfm-usage-hint p {
	margin: 8px 0;
	font-size: 13px;
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

.qfm-category-orders {
	margin-top: 8px;
}

.qfm-category-order-item {
	margin-bottom: 12px;
	padding: 8px;
	background: #f8f9fa;
	border: 1px solid #e1e5e9;
	border-radius: 4px;
}

.qfm-category-order-item label {
	display: inline-block;
	min-width: 120px;
	margin-right: 8px;
	font-weight: 500;
}

.qfm-category-order-item input {
	margin-right: 8px;
}

.qfm-category-order-help {
	font-size: 12px;
	color: #666;
	font-style: italic;
}

.qfm-category-orders .description {
	margin-top: 12px;
	font-style: italic;
}
</style>