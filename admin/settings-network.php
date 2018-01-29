<div class="wrap">

<h2><?php _e( 'Intuitive Custom Post Order Network Settings', 'intuitive-custom-post-order' ); ?></h2>

<?php if ( isset($_GET['msg'] )) : ?>
<div id="message" class="updated below-h2">
	<?php if ( $_GET['msg'] == 'update' ) : ?>
		<p><?php _e( 'Settings saved.' ); ?></p>
	<?php endif; ?>
</div>
<?php endif; ?>

<form method="post">

<?php if ( function_exists( 'wp_nonce_field' ) ) wp_nonce_field( 'nonce_hicpo' ); ?>

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<?php _e( 'Sortable Objects', 'intuitive-custom-post-order' ) ?>
			</th>
			<td>
				<label><input type="checkbox" name="sites" value="1" <?php if ( get_option( 'hicpo_network_sites' ) ) { echo 'checked="checked"'; } ?>>&nbsp;<?php _e( 'Sites', 'intuitive-custom-post-order' ) ?></label>
			</td>
		</tr>
	</tbody>
</table>

<p class="submit">
	<input type="submit" class="button-primary" name="hicpo_network_submit" value="<?php _e( 'Update', 'cptg' ); ?>">
</p>

</form>

</div>
