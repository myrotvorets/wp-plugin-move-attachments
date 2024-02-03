<?php defined( 'ABSPATH' ) || die(); ?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php if ( ! empty( $errors ) ) : ?>
	<div class="error notice notice-error">
		<ul>
		<?php foreach ( $errors as $message ) : ?>
			<li><?php echo esc_html( $message ); ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $messages ) ) : ?>
	<div class="updated notice notice-success">
		<ul>
		<?php foreach ( $messages as $message ) : ?>
			<li><?php echo esc_html( $message ); ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<form action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>" method="post">
		<?php wp_nonce_field( 'move_attachments' ); ?>
		<input type="hidden" name="action" value="move_attachments"/>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="url_from"><?php _e( 'Move attachments from', 'wp-move-attachments' ); ?></label></th>
					<td><input name="url_from" type="url" id="url_from" value="" class="regular-text"/></td>
				</tr>
				<tr>
					<th><label for="url_to"><?php _e( 'Move attachments to', 'wp-move-attachments' ); ?></label></th>
					<td><input name="url_to" type="url" id="url_to" value="" class="regular-text"/></td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Move', 'wp-move-attachments' ) ); ?>
	</form>
</div>
