<?php
/**
 * Organization schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates Organization JSON-LD schema.
 */
final class OrganizationSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return true; // Every page.
	}

	/**
	 * Generate the Organization schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$settings = $this->get_settings();

		$data = array(
			'@type' => 'Organization',
			'@id'   => $this->get_org_id(),
			'name'  => $settings['org_name'] ? $settings['org_name'] : get_bloginfo( 'name' ),
			'url'   => trailingslashit( home_url() ),
		);

		if ( ! empty( $settings['org_logo'] ) ) {
			$data['logo'] = array(
				'@type'      => 'ImageObject',
				'url'        => $settings['org_logo'],
				'contentUrl' => $settings['org_logo'],
			);
		} elseif ( has_custom_logo() ) {
			$logo_id  = get_theme_mod( 'custom_logo' );
			$logo_url = $logo_id ? wp_get_attachment_image_url( (int) $logo_id, 'full' ) : '';
			if ( $logo_url ) {
				$data['logo'] = array(
					'@type'      => 'ImageObject',
					'url'        => $logo_url,
					'contentUrl' => $logo_url,
				);
			}
		}

		if ( ! empty( $settings['org_founding_date'] ) ) {
			$data['foundingDate'] = $settings['org_founding_date'];
		}

		$same_as = $this->get_org_same_as( $settings );
		if ( ! empty( $same_as ) ) {
			$data['sameAs'] = $same_as;
		}

		return $data;
	}

	/**
	 * Get organization sameAs URLs from settings.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return list<string>
	 */
	private function get_org_same_as( array $settings ): array {
		if ( empty( $settings['org_social_urls'] ) ) {
			return array();
		}

		$urls = array_filter(
			array_map( 'trim', (array) $settings['org_social_urls'] ),
			static fn( string $url ): bool => (bool) filter_var( $url, FILTER_VALIDATE_URL ),
		);

		return array_values( $urls );
	}
}
