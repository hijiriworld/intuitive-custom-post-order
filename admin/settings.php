<?php

$hicpo_options = get_option('hicpo_options');
$objects = $hicpo_options['objects'];

?>

<div class="wrap">

<?php screen_icon( 'plugins' ); ?>

<h2><?php _e('Intuitive Custom Post Order Settings', 'hicpo'); ?></h2>

<?php if ( isset($_GET['msg'] )) : ?>
<div id="message" class="updated below-h2">
	<?php if ( $_GET['msg'] == 'update') : ?>
		<p><?php _e('Settings saved.', 'hicpo'); ?></p>
	<?php endif; ?>
</div>
<?php endif; ?>

<form method="post">

<?php if ( function_exists( 'wp_nonce_field' ) ) wp_nonce_field( 'nonce_hicpo' ); ?>

<div id="hicpo_select_objects">

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php _e('Sortable Objects', 'hicpo') ?></th>
			<td>
			<?php
				$post_types = get_post_types( array (
					'public' => true
				), 'objects' );
				
				foreach ($post_types  as $post_type ) {
					if ( $post_type->name != 'attachment' ) {
					?>
					<label><input type="checkbox" name="objects[]" value="<?php echo $post_type->name; ?>" <?php if ( isset($objects) && is_array($objects) ) { if ( in_array($post_type->name, $objects )) { echo 'checked="checked"'; } } ?> />&nbsp;<?php echo $post_type->label; ?></label><br />
					<?php
					}
				}
			?>
			</td>
		</tr>
	</tbody>
</table>

</div>

<label><input type="checkbox" id="hicpo_allcheck"> <?php _e('All Check', 'hicpo') ?></label>

<script>
(function($){

	$("#hicpo_allcheck").on('click', function(){
		
		var items = $("#hicpo_select_objects input");
		
		if ( $(this).is(':checked') ) {
			$(items).prop('checked', true);
		} else {
			$(items).prop('checked', false);	
		}
	});

})(jQuery)
</script>

<p class="submit">
	<input type="submit" class="button-primary" name="hicpo_submit" value="<?php _e('Update', 'cptg'); ?>" />
</p>
	
</form>

</div>