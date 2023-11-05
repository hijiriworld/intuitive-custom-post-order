<div class="wrap">

<h2><?php esc_html_e( 'Intuitive Custom Post Order Network Settings', 'intuitive-custom-post-order' ); ?></h2>

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

<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<?php esc_html_e( 'Sortable Objects', 'intuitive-custom-post-order' ); ?>
			</th>
			<td>
				<label><input type="checkbox" name="sites" value="1"
				<?php
				if ( get_option( 'hicpo_network_sites' ) ) {
					echo 'checked="checked"'; }
				?>
				>&nbsp;<?php esc_html_e( 'Sites', 'intuitive-custom-post-order' ); ?></label>
			</td>
		</tr>
	</tbody>
</table>

<p class="submit">
	<input type="submit" class="button-primary" name="hicpo_network_submit" value="<?php esc_html_e( 'Update', 'cptg' ); ?>">
</p>

</form>

</div>
