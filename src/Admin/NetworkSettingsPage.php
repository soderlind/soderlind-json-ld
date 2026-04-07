<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Admin;

final class NetworkSettingsPage {

    private const SLUG = 'soderlind-jsonld-network';
    private const ACTION = 'soderlind_jsonld';

    public function register(): void {
        add_submenu_page(
            'settings.php',
            __('JSON-LD Network Settings', 'soderlind-json-ld'),
            __('JSON-LD', 'soderlind-json-ld'),
            'manage_network_options',
            self::SLUG,
            [$this, 'render'],
        );
    }

    public function render(): void {
        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'soderlind-json-ld'));
        }

        $settings = wp_parse_args(
            (array) get_site_option(Settings::NETWORK_OPTION_KEY, []),
            Settings::get_defaults(),
        );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="description">
                <?php esc_html_e('These settings serve as defaults for all sites in the network. Individual sites can override them.', 'soderlind-json-ld'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=' . self::ACTION)); ?>">
                <?php wp_nonce_field(self::ACTION . '_nonce', '_soderlind_jsonld_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="org_name"><?php esc_html_e('Organization Name', 'soderlind-json-ld'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="org_name" name="soderlind_jsonld[org_name]"
                                value="<?php echo esc_attr($settings['org_name']); ?>"
                                placeholder="<?php echo esc_attr(get_network()->site_name); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="org_logo"><?php esc_html_e('Logo URL', 'soderlind-json-ld'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="org_logo" name="soderlind_jsonld[org_logo]"
                                value="<?php echo esc_attr($settings['org_logo']); ?>"
                                class="regular-text" placeholder="https://" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="org_founding_date"><?php esc_html_e('Founding Date', 'soderlind-json-ld'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="org_founding_date" name="soderlind_jsonld[org_founding_date]"
                                value="<?php echo esc_attr($settings['org_founding_date']); ?>"
                                class="small-text" placeholder="YYYY" />
                            <p class="description"><?php esc_html_e('Year (e.g., 2020) or full date (e.g., 2020-01-15).', 'soderlind-json-ld'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Social Profile URLs', 'soderlind-json-ld'); ?>
                        </th>
                        <td>
                            <?php
                            $urls = ! empty($settings['org_social_urls']) ? $settings['org_social_urls'] : [''];
                            foreach ($urls as $url) {
                                printf(
                                    '<div style="margin-bottom:5px;"><input type="url" name="soderlind_jsonld[org_social_urls][]" value="%s" class="regular-text" placeholder="https://" /></div>',
                                    esc_attr($url),
                                );
                            }
                            ?>
                            <p class="description"><?php esc_html_e('Add social profile URLs (LinkedIn, Twitter/X, Facebook, GitHub, etc.).', 'soderlind-json-ld'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'soderlind-json-ld'));
        }

        check_admin_referer(self::ACTION . '_nonce', '_soderlind_jsonld_nonce');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Settings::sanitize().
        $input = isset($_POST['soderlind_jsonld']) ? wp_unslash($_POST['soderlind_jsonld']) : [];
        $clean = Settings::sanitize($input);

        update_site_option(Settings::NETWORK_OPTION_KEY, $clean);

        // Bump network version to invalidate all site caches.
        $version = (int) get_site_option('soderlind_jsonld_network_version', 0);
        update_site_option('soderlind_jsonld_network_version', $version + 1);

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => self::SLUG,
                    'updated' => 'true',
                ],
                network_admin_url('settings.php'),
            ),
        );
        exit;
    }
}
