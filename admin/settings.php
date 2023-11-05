<?php

$hicpo_options = get_option( 'hicpo_options' );
$hicpo_objects = isset( $hicpo_options['objects'] ) ? $hicpo_options['objects'] : [];
$hicpo_tags = isset( $hicpo_options['tags'] ) ? $hicpo_options['tags'] : [];

?>

<div class="wrap">

<h2><?php esc_html_e( 'Intuitive Custom Post Order Settings', 'intuitive-custom-post-order' ); ?></h2>

<?php if ( isset( $_GET['msg'] ) ) : ?>
<div id="message" class="updated below-h2">
	<?php if ( 'update' === $_GET['msg'] ) : ?>
		<p><?php esc_html_e( 'Settings saved.' ); ?></p>
	<?php endif; ?>
</div>
<?php endif; ?>

<form method="post">

<?php
if ( function_exists( 'wp_nonce_field' ) ) {
	wp_nonce_field( 'nonce_hicpo' );}
?>

<div id="hicpo_select_objects">

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Sortable Post Types', 'intuitive-custom-post-order' ); ?></th>
			<td>
			<?php
				$post_types = get_post_types(
					[
						'show_ui' => true,
						'show_in_menu' => true,
					],
					'objects'
				);

				foreach ( $post_types as $post_type ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					if ( 'attachment' === $post_type->name ) {
						continue;
					}
					?>
					<label><input type="checkbox" name="objects[]" value="<?php echo esc_html( $post_type->name ); ?>"
					<?php
					if ( isset( $hicpo_objects ) && is_array( $hicpo_objects ) ) {
						if ( in_array( $post_type->name, $hicpo_objects ) ) {
							echo 'checked="checked"'; }
					}
					?>
					>&nbsp;<?php echo esc_html( $post_type->label ); ?></label><br>
					<?php
				}
				?>
			</td>
		</tr>
	</tbody>
</table>

</div>

<label><input type="checkbox" id="hicpo_allcheck_objects"> <?php esc_html_e( 'All Check', 'intuitive-custom-post-order' ); ?></label>

<div id="hicpo_select_tags">

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Sortable Taxonomies', 'intuitive-custom-post-order' ); ?></th>
			<td>
			<?php
				$taxonomies = get_taxonomies(
					[
						'show_ui' => true,
					],
					'objects'
				);

				foreach ( $taxonomies as $taxonomy ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					if ( 'post_format' === $taxonomy->name ) {
						continue;
					}
					?>
					<label><input type="checkbox" name="tags[]" value="<?php echo esc_html( $taxonomy->name ); ?>"
					<?php
					if ( isset( $hicpo_tags ) && is_array( $hicpo_tags ) ) {
						if ( in_array( $taxonomy->name, $hicpo_tags ) ) {
							echo 'checked="checked"'; }
					}
					?>
					>&nbsp;<?php echo esc_html( $taxonomy->label ); ?></label><br>
					<?php
				}
				?>
			</td>
		</tr>
	</tbody>
</table>

</div>

<label><input type="checkbox" id="hicpo_allcheck_tags"> <?php esc_html_e( 'All Check', 'intuitive-custom-post-order' ); ?></label>

<p class="submit">
	<input type="submit" class="button-primary" name="hicpo_submit" value="<?php esc_html_e( 'Update' ); ?>">
</p>

</form>

</div>

<script>
(function($){

	$("#hicpo_allcheck_objects").on('click', function(){
		var items = $("#hicpo_select_objects input");
		if ( $(this).is(':checked') ) $(items).prop('checked', true);
		else $(items).prop('checked', false);
	});

	$("#hicpo_allcheck_tags").on('click', function(){
		var items = $("#hicpo_select_tags input");
		if ( $(this).is(':checked') ) $(items).prop('checked', true);
		else $(items).prop('checked', false);
	});

})(jQuery)
</script>
