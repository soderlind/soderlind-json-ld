<?php
/**
 * SoftwareApplication schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

/**
 * Generates SoftwareApplication JSON-LD schema from detected software content.
 */
final class SoftwareApplicationSchema extends AbstractSchema {

	/**
	 * Constructor.
	 *
	 * @param ContentAnalyzer $analyzer Content analyzer instance.
	 */
	public function __construct(
		private readonly ContentAnalyzer $analyzer,
	) {}

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		$types = $this->analyzer->detect( $post );
		return in_array( 'SoftwareApplication', $types, true );
	}

	/**
	 * Generate the SoftwareApplication schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$data = array(
			'@type'               => 'SoftwareApplication',
			'@id'                 => get_permalink( $post ) . '#software',
			'name'                => get_the_title( $post ),
			'description'         => $this->get_excerpt( $post ),
			'applicationCategory' => $this->detect_category( $post ),
			'url'                 => get_permalink( $post ),
			'author'              => $this->get_publisher_ref(),
		);

		$image = $this->get_post_image( $post );
		if ( $image ) {
			$data['image'] = $image;
		}

		return $data;
	}

	/**
	 * Detect the software application category from content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function detect_category( \WP_Post $post ): string {
		$text = mb_strtolower( wp_strip_all_tags( $post->post_content ) );

		$categories = array(
			'BrowserApplication'    => array( 'browser', 'web app', 'extension', 'add-on' ),
			'GameApplication'       => array( 'game', 'gaming' ),
			'DeveloperApplication'  => array( 'developer', 'ide', 'programming', 'api', 'sdk', 'cli' ),
			'DesignApplication'     => array( 'design', 'graphic', 'photo', 'image editor' ),
			'EducationApplication'  => array( 'education', 'learning', 'course', 'tutorial' ),
			'HealthApplication'     => array( 'health', 'fitness', 'medical' ),
			'BusinessApplication'   => array( 'business', 'enterprise', 'crm', 'erp' ),
			'SecurityApplication'   => array( 'security', 'antivirus', 'firewall', 'vpn' ),
			'MultimediaApplication' => array( 'video', 'audio', 'media player', 'streaming' ),
		);

		foreach ( $categories as $schema_type => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( str_contains( $text, $keyword ) ) {
					return $schema_type;
				}
			}
		}

		// WordPress plugin.
		if ( str_contains( $text, 'plugin' ) || str_contains( $text, 'WordPress' ) ) {
			return 'DeveloperApplication';
		}

		return 'SoftwareApplication';
	}
}
