<?php
/**
 * Abstract base schema class.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\Admin\Settings;
use WP_Post;
use WP_User;

/**
 * Base class for all JSON-LD schema generators.
 */
abstract class AbstractSchema implements SchemaInterface {

	/**
	 * Get merged plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_settings(): array {
		return Settings::get_merged();
	}

	/**
	 * Get the Organization @id URL.
	 *
	 * @return string
	 */
	protected function get_org_id(): string {
		return trailingslashit( home_url() ) . '#organization';
	}

	/**
	 * Get the WebSite @id URL.
	 *
	 * @return string
	 */
	protected function get_website_id(): string {
		return trailingslashit( home_url() ) . '#website';
	}

	/**
	 * Get image data for a post's featured image.
	 *
	 * @param WP_Post|null $post Optional post object.
	 * @return array<string, mixed>|null
	 */
	protected function get_post_image( ?WP_Post $post = null ): ?array {
		$post = $post ?? get_post();
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$thumbnail_id = get_post_thumbnail_id( $post );
		if ( ! $thumbnail_id ) {
			return null;
		}

		$image_url  = wp_get_attachment_image_url( (int) $thumbnail_id, 'full' );
		$image_meta = wp_get_attachment_metadata( (int) $thumbnail_id );

		if ( ! $image_url ) {
			return null;
		}

		$data = array(
			'@type' => 'ImageObject',
			'url'   => $image_url,
		);

		if ( is_array( $image_meta ) ) {
			if ( ! empty( $image_meta['width'] ) ) {
				$data['width'] = (int) $image_meta['width'];
			}
			if ( ! empty( $image_meta['height'] ) ) {
				$data['height'] = (int) $image_meta['height'];
			}
		}

		return $data;
	}

	/**
	 * Get author data for a post.
	 *
	 * @param WP_Post|null $post Optional post object.
	 * @return array<string, mixed>
	 */
	protected function get_author_data( ?WP_Post $post = null ): array {
		$post      = $post ?? get_post();
		$author_id = $post instanceof WP_Post ? (int) $post->post_author : 0;
		$user      = $author_id ? get_userdata( $author_id ) : null;

		return $this->build_person_data( $user ? $user : null );
	}

	/**
	 * Build Person schema data from a user.
	 *
	 * @param WP_User|null $user User object or null.
	 * @return array<string, mixed>
	 */
	protected function build_person_data( ?WP_User $user ): array {
		if ( ! $user instanceof WP_User ) {
			return array(
				'@type' => 'Person',
				'name'  => __( 'Unknown', 'soderlind-json-ld' ),
			);
		}

		$data = array(
			'@type' => 'Person',
			'@id'   => get_author_posts_url( $user->ID ) . '#person',
			'name'  => $user->display_name,
			'url'   => get_author_posts_url( $user->ID ),
		);

		$description = get_the_author_meta( 'description', $user->ID );
		if ( $description ) {
			$data['description'] = wp_strip_all_tags( $description );
		}

		$avatar_url = get_avatar_url( $user->ID, array( 'size' => 96 ) );
		if ( $avatar_url ) {
			$data['image'] = $avatar_url;
		}

		$same_as = $this->get_user_same_as( $user );
		if ( ! empty( $same_as ) ) {
			$data['sameAs'] = $same_as;
		}

		return $data;
	}

	/**
	 * Get sameAs URLs for a user.
	 *
	 * @param WP_User $user User object.
	 * @return list<string>
	 */
	protected function get_user_same_as( WP_User $user ): array {
		$urls = array();

		$user_url = $user->user_url;
		if ( $user_url ) {
			$urls[] = $user_url;
		}

		$social_fields = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'wikipedia', 'github', 'mastodon' );
		foreach ( $social_fields as $field ) {
			$value = get_the_author_meta( $field, $user->ID );
			if ( $value && filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$urls[] = $value;
			}
		}

		return $urls;
	}

	/**
	 * Get publisher reference for schema output.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_publisher_ref(): array {
		return array(
			'@type' => 'Organization',
			'@id'   => $this->get_org_id(),
		);
	}

	/**
	 * Get a post excerpt, falling back to trimmed content.
	 *
	 * @param WP_Post|null $post Optional post object.
	 * @return string
	 */
	protected function get_excerpt( ?WP_Post $post = null ): string {
		$post = $post ?? get_post();
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		return wp_trim_words( wp_strip_all_tags( $post->post_content ), 55 );
	}
}
