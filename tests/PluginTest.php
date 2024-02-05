<?php

use Myrotvorets\WordPress\MoveAttachments\Plugin;

/**
 * @covers Myrotvorets\WordPress\MoveAttachments\Plugin
 */
class PluginTest extends WP_UnitTestCase {
	private static Plugin $plugin;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$plugin = Plugin::instance();
	}

	public function test_instance(): void {
		self::assertInstanceOf( Plugin::class, self::$plugin );
	}

	public function test_admin_menu(): void {
		self::assertFalse( has_action( 'load-admin_page_move-attachments', [ self::$plugin, 'load_page' ] ) );

		wp_set_current_user( 1 );
		self::$plugin->admin_menu();
		self::assertGreaterThan( 0, has_action( 'load-admin_page_move-attachments', [ self::$plugin, 'load_page' ] ) );
	}

	public function test_admin_init(): void {
		self::assertFalse( has_action( 'admin_post_move_attachments', [ self::$plugin, 'admin_post_move_attachments' ] ) );
		self::$plugin->admin_init();
		self::assertGreaterThan( 0, has_action( 'admin_post_move_attachments', [ self::$plugin, 'admin_post_move_attachments' ] ) );
	}

	/**
	 * @uses Myrotvorets\WordPress\MoveAttachments\Utils::get_attachments
	 * @uses Myrotvorets\WordPress\MoveAttachments\Utils::url_to_postid
	 */
	public function test_move_attachments(): void {
		$ids = self::factory()->post->create_many( 2 );

		self::assertIsInt( $ids[0] );
		self::assertIsInt( $ids[1] );

		$attachment = self::factory()->attachment->create_and_get( [
			'post_parent' => $ids[0],
			'file'        => __DIR__ . '/fixtures/20x20.png',
		] );

		$this->assertNotWPError( $attachment );

		$from = get_permalink( $ids[0] );
		$to   = get_permalink( $ids[1] );

		$result = self::$plugin->move_attachments( $from, $to );
		$this->assertNotWPError( $result );
		self::assertIsArray( $result );
		self::assertNotEmpty( $result[0] );
		self::assertEmpty( $result[1] );

		$attachments_1 = get_posts( [
			'post_parent' => $ids[0],
			'post_type'   => 'attachment',
			'numberposts' => -1,
		] );

		$attachments_2 = get_posts( [
			'post_parent' => $ids[1],
			'post_type'   => 'attachment',
			'numberposts' => -1,
		] );

		self::assertEmpty( $attachments_1 );
		self::assertNotEmpty( $attachments_2 );
		self::assertCount( 1, $attachments_2 );

		/** @var WP_Post $attachment */
		self::assertNotEquals( $attachment->ID, $attachments_2[0]->ID );
	}

	/**
	 * @dataProvider data_move_attachments_param_errors
	 * @uses Myrotvorets\WordPress\MoveAttachments\Utils::url_to_postid
	 */
	public function test_move_attachments_param_errors( string $from, string $to, string $code ): void {
		$result = self::$plugin->move_attachments( $from, $to );
		$this->assertWPError( $result );
		self::assertEquals( $code, $result->get_error_code() );
	}

	public function data_move_attachments_param_errors(): iterable {
		return [
			[ '', '', 'invalid_url' ],
			[ 'invalid', 'invalid', 'invalid_url' ],
		];
	}

	/**
	 * @uses Myrotvorets\WordPress\MoveAttachments\Utils::url_to_postid
	 */
	public function test_move_attachments_same_post(): void {
		$id = self::factory()->post->create();

		self::assertIsInt( $id );

		$from = get_permalink( $id );
		$to   = $from;

		$result = self::$plugin->move_attachments( $from, $to );
		$this->assertWPError( $result );
		self::assertEquals( 'same_post', $result->get_error_code() );
	}
}
