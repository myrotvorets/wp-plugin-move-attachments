<?php

namespace Myrotvorets\WordPress\MoveAttachments;

use WP_Error;
use WP_Post;

final class Plugin {
	private static ?self $instance = null;

	/**
	 * @codeCoverageIgnore
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @codeCoverageIgnore
	 */
	private function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'admin_init', [ $this, 'admin_init' ] );
		}
	}

	public function admin_menu(): void {
		$hook = add_management_page(
			__( 'Move Attachments', 'wp-move-attachments' ),
			__( 'Move Attachments', 'wp-move-attachments' ),
			'edit_posts',
			'move-attachments',
			[ $this, 'render_page' ]
		);

		add_action( "load-{$hook}", [ $this, 'load_page' ] );
	}

	public function admin_init(): void {
		add_action( 'admin_post_move_attachments', [ $this, 'admin_post_move_attachments' ] );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function render_page(): void {
		$params = [];

		/** @var mixed */
		$logs = get_transient( 'wp_move_attachments_log' );
		if ( is_array( $logs ) && ! empty( $logs ) ) {
			$params['messages'] = $logs;
			delete_transient( 'wp_move_attachments_log' );
		}

		/** @var mixed */
		$errors = get_transient( 'wp_move_attachments_errors' );
		if ( is_array( $errors ) && ! empty( $errors ) ) {
			$params['errors'] = $errors;
			delete_transient( 'wp_move_attachments_errors' );
		}

		self::render_view( 'move-attachments', $params );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function load_page(): void {
		/** @psalm-suppress RiskyTruthyFalsyComparison */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['_wp_http_referer'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_safe_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit();
		}
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function admin_post_move_attachments(): void {
		check_admin_referer( 'move_attachments' );

		$from   = (string) filter_var( wp_unslash( $_POST['url_from'] ?? '' ), FILTER_VALIDATE_URL );
		$to     = (string) filter_var( wp_unslash( $_POST['url_to'] ?? '' ), FILTER_VALIDATE_URL );
		$result = $this->move_attachments( $from, $to );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		} else {
			list( $logs, $errors ) = $result;
			if ( $logs ) {
				set_transient( 'wp_move_attachments_log', $logs, 5 * MINUTE_IN_SECONDS );
			}

			if ( $errors ) {
				set_transient( 'wp_move_attachments_errors', $errors, 5 * MINUTE_IN_SECONDS );
			}

			wp_safe_redirect( admin_url( 'tools.php?page=move-attachments' ) );
			die();
		}
	}

	/**
	 * @psalm-return array{list<non-empty-string>, list<non-empty-string>}|WP_Error
	 */
	public function move_attachments( string $url_from, string $url_to ) {
		if ( ! $url_from || ! $url_to ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL', 'wp-move-attachments' ) );
		}

		$url_from = preg_replace( '!https?://[^/]++!i', '', $url_from );
		$url_to   = preg_replace( '!https?://[^/]++!i', '', $url_to );
		$id_from  = Utils::url_to_postid( $url_from );
		$id_to    = Utils::url_to_postid( $url_to );

		if ( ! $id_from || ! $id_to ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL', 'wp-move-attachments' ) );
		}

		if ( $id_from === $id_to ) {
			return new WP_Error( 'same_post', __( 'Source and destination posts are the same', 'wp-move-attachments' ) );
		}

		/** @var WP_Post|null */
		$target_post = get_post( $id_to );
		// @codeCoverageIgnoreStart
		if ( ! $target_post ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post', 'wp-move-attachments' ) );
		}
		// @codeCoverageIgnoreEnd

		$_REQUEST['post_id'] = $id_to;
		$dir                 = wp_upload_dir();
		unset( $_REQUEST['post_id'] );

		// @codeCoverageIgnoreStart
		/** @psalm-suppress RiskyTruthyFalsyComparison */
		if ( ! empty( $dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $dir['error'] );
		}
		// @codeCoverageIgnoreEnd

		$target_path = $dir['path'];
		$url         = $dir['url'];
		$log         = [];
		$errors      = [];

		set_time_limit( 0 );
		$attachments = Utils::get_attachments( $id_from );
		foreach ( $attachments as [ $att_id, $fullname ] ) {
			$name        = wp_basename( $fullname );
			$unique_name = wp_unique_filename( $target_path, $name );
			$destination = $target_path . DIRECTORY_SEPARATOR . $unique_name;

			if ( copy( $fullname, $destination ) ) {
				$filetype   = wp_check_filetype( $unique_name, null );
				$attachment = [
					'guid'           => $url . '/' . $unique_name,
					'post_mime_type' => $filetype['type'],
					'post_title'     => $name,
					'post_content'   => '',
					'post_status'    => 'inherit',
					'post_author'    => $target_post->post_author,
				];

				$attach_id = wp_insert_attachment( $attachment, $destination, $id_to, true );
				if ( ! is_wp_error( $attach_id ) ) {
					$attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					clean_attachment_cache( $attach_id );
					wp_delete_attachment( $att_id, true );
					clean_attachment_cache( $att_id );
					// translators: 1: filename, 2: destination
					$log[] = sprintf( __( 'Moved %1$s to %2$s', 'wp-move-attachments' ), $fullname, $destination );
				} else {
					// @codeCoverageIgnoreStart
					$errors[] = sprintf(
						// translators: 1: filename, 2: destination, 3: error message, 4: error data
						__( 'Failed to move %1$s to %2$s: %3$s %4$s', 'wp-move-attachments' ),
						$fullname,
						$destination,
						$attach_id->get_error_message(),
						(string) $attach_id->get_error_data()
					);
					// @codeCoverageIgnoreEnd
				}
			} else {
				// @codeCoverageIgnoreStart
				// translators: 1: filename, 2: destination
				$errors[] = sprintf( __( 'Failed to move %1$s to %2$s', 'wp-move-attachments' ), $fullname, $destination );
				unlink( $destination ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- this happens in uploads
				// @codeCoverageIgnoreEnd
			}
		}

		return [ $log, $errors ];
	}

	/**
	 * @codeCoverageIgnore
	 */
	private static function render_view( string $name, array $params = [] ): void {
		$file = __DIR__ . "/../views/{$name}.php";
		if ( ! file_exists( $file ) ) {
			return;
		}

		extract( $params, EXTR_SKIP );
		require $file; // NOSONAR
	}
}
