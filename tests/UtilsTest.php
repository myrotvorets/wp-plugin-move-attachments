<?php

use Myrotvorets\WordPress\MoveAttachments\Utils;

class UtilsTest extends WP_UnitTestCase {
	private static int $post_1_id;
	private static int $post_2_id;
	private static int $attachment_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$post_1_id     = $factory->post->create();
		self::$post_2_id     = $factory->post->create();
		self::$attachment_id = $factory->attachment->create( [
			'post_parent' => self::$post_1_id,
			'file'        => __DIR__ . '/fixtures/20x20.png',
		] );
	}

	/**
	 * @covers Myrotvorets\WordPress\MoveAttachments\Utils::url_to_postid
	 */
	public function test_url_to_postid(): void {
		$url     = get_permalink( self::$post_1_id );
		$post_id = Utils::url_to_postid( $url );

		self::assertSame( self::$post_1_id, $post_id );
	}

	/**
	 * @covers Myrotvorets\WordPress\MoveAttachments\Utils::url_to_postid
	 */
	public function test_url_to_postid_caches(): void {
		$url   = get_permalink( self::$post_2_id );
		$calls = 0;

		add_filter( 'url_to_postid', function ( string $url ) use ( &$calls ): string {
			++$calls;
			return $url;
		} );

		$post_id = Utils::url_to_postid( $url );
		self::assertSame( self::$post_2_id, $post_id );
		self::assertEquals( 1, $calls );

		$post_id = Utils::url_to_postid( $url );
		self::assertSame( self::$post_2_id, $post_id );
		self::assertEquals( 1, $calls );
	}

	/**
	 * @covers Myrotvorets\WordPress\MoveAttachments\Utils::get_attachments
	 */
	public function test_get_attachments(): void {
		$attachments = Utils::get_attachments( self::$post_1_id );

		self::assertIsArray( $attachments );
		self::assertNotEmpty( $attachments );
		self::assertArrayHasKey( self::$attachment_id, $attachments );
		self::assertIsArray( $attachments[ self::$attachment_id ] );
		self::assertSame( self::$attachment_id, $attachments[ self::$attachment_id ][0] );
		self::assertIsString( $attachments[ self::$attachment_id ][1] );
	}

	/**
	 * @covers Myrotvorets\WordPress\MoveAttachments\Utils::get_attachments
	 */
	public function test_get_attachments_empty(): void {
		$attachments = Utils::get_attachments( self::$post_2_id );

		self::assertIsArray( $attachments );
		self::assertEmpty( $attachments );
	}
}
