<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Admin;

final class SettingsPage {

    private const SLUG = 'soderlind-jsonld';
    private const GROUP = 'soderlind_jsonld_group';

    public function register(): void {
        $hook = add_options_page(
            __('JSON-LD Settings', 'soderlind-json-ld'),
            __('JSON-LD', 'soderlind-json-ld'),
            'manage_options',
            self::SLUG,
            [$this, 'render'],
        );

        if ($hook) {
            add_action("load-{$hook}", [$this, 'enqueue_media']);
        }
    }

    public function enqueue_media(): void {
        wp_enqueue_media();
    }

    public function register_settings(): void {
        register_setting(
            self::GROUP,
            Settings::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [Settings::class, 'sanitize'],
                'default'           => Settings::get_defaults(),
            ],
        );

        add_settings_section(
            'soderlind_jsonld_org',
            __('Organization', 'soderlind-json-ld'),
            [$this, 'render_org_section'],
            self::SLUG,
        );

        $this->add_field('org_name', __('Organization Name', 'soderlind-json-ld'), 'render_org_name');
        $this->add_field('org_logo', __('Logo URL', 'soderlind-json-ld'), 'render_org_logo');
        $this->add_field('org_founding_date', __('Founding Date', 'soderlind-json-ld'), 'render_org_founding_date');
        $this->add_field('org_social_urls', __('Social Profile URLs', 'soderlind-json-ld'), 'render_org_social_urls');
    }

    public function render(): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::GROUP);
                do_settings_sections(self::SLUG);
                submit_button();
                ?>
            </form>
            <hr />
            <h2><?php esc_html_e('Cache', 'soderlind-json-ld'); ?></h2>
            <p class="description">
                <?php esc_html_e('Clear all cached JSON-LD output for this site.', 'soderlind-json-ld'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="soderlind_jsonld_flush_cache" />
                <?php wp_nonce_field('soderlind_jsonld_flush_cache', '_soderlind_jsonld_nonce'); ?>
                <?php submit_button(__('Flush JSON-LD Cache', 'soderlind-json-ld'), 'secondary', 'flush_cache', false); ?>
            </form>
            <?php if (isset($_GET['cache_flushed']) && $_GET['cache_flushed'] === '1') : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('JSON-LD cache flushed.', 'soderlind-json-ld'); ?></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_flush_cache(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'soderlind-json-ld'));
        }

        check_admin_referer('soderlind_jsonld_flush_cache', '_soderlind_jsonld_nonce');

        \Soderlind\JsonLd\Cache::invalidate_site();

        wp_safe_redirect(add_query_arg(
            ['page' => self::SLUG, 'cache_flushed' => '1'],
            admin_url('options-general.php'),
        ));
        exit;
    }

    public function render_org_section(): void {
        if (is_multisite()) {
            echo '<p class="description">';
            esc_html_e('Leave fields empty to use network defaults.', 'soderlind-json-ld');
            echo '</p>';
        }
    }

    public function render_org_name(): void {
        $settings = Settings::get_site();
        $network = Settings::get_network();
        $placeholder = $network['org_name'] ?: get_bloginfo('name');
        printf(
            '<input type="text" name="%s[org_name]" value="%s" placeholder="%s" class="regular-text" />',
            esc_attr(Settings::OPTION_KEY),
            esc_attr($settings['org_name']),
            esc_attr($placeholder),
        );
    }

    public function render_org_logo(): void {
        $settings = Settings::get_site();
        $network = Settings::get_network();
        $placeholder = $network['org_logo'] ?: '';
        $value = $settings['org_logo'];

        printf(
            '<input type="url" id="soderlind-jsonld-logo" name="%s[org_logo]" value="%s" placeholder="%s" class="regular-text" />',
            esc_attr(Settings::OPTION_KEY),
            esc_attr($value),
            esc_attr($placeholder),
        );
        echo ' <button type="button" class="button" id="soderlind-jsonld-logo-btn">';
        esc_html_e('Select Image', 'soderlind-json-ld');
        echo '</button>';

        if ($value) {
            printf(
                '<br /><img src="%s" alt="" style="max-width:200px;margin-top:10px;" />',
                esc_url($value),
            );
        }

        // Inline media picker script.
        ?>
        <script>
        (function(){
            document.getElementById('soderlind-jsonld-logo-btn')?.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof wp === 'undefined' || !wp.media) return;
                var frame = wp.media({
                    title: '<?php echo esc_js(__('Select Logo', 'soderlind-json-ld')); ?>',
                    button: { text: '<?php echo esc_js(__('Use as Logo', 'soderlind-json-ld')); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    document.getElementById('soderlind-jsonld-logo').value = attachment.url;
                });
                frame.open();
            });
        })();
        </script>
        <?php
    }

    public function render_org_founding_date(): void {
        $settings = Settings::get_site();
        $network = Settings::get_network();
        $placeholder = $network['org_founding_date'] ?: 'YYYY';
        printf(
            '<input type="text" name="%s[org_founding_date]" value="%s" placeholder="%s" class="small-text" />',
            esc_attr(Settings::OPTION_KEY),
            esc_attr($settings['org_founding_date']),
            esc_attr($placeholder),
        );
        echo '<p class="description">' . esc_html__('Year (e.g., 2020) or full date (e.g., 2020-01-15).', 'soderlind-json-ld') . '</p>';
    }

    public function render_org_social_urls(): void {
        $settings = Settings::get_site();
        $urls = ! empty($settings['org_social_urls']) ? $settings['org_social_urls'] : [''];

        echo '<div id="soderlind-jsonld-social-urls">';
        foreach ($urls as $i => $url) {
            printf(
                '<div class="soderlind-jsonld-social-row" style="margin-bottom:5px;"><input type="url" name="%s[org_social_urls][]" value="%s" class="regular-text" placeholder="https://" /></div>',
                esc_attr(Settings::OPTION_KEY),
                esc_attr($url),
            );
        }
        echo '</div>';
        echo '<button type="button" class="button" id="soderlind-jsonld-add-social">' . esc_html__('Add URL', 'soderlind-json-ld') . '</button>';
        echo '<p class="description">' . esc_html__('Add social profile URLs (LinkedIn, Twitter/X, Facebook, GitHub, etc.).', 'soderlind-json-ld') . '</p>';
        ?>
        <script>
        (function(){
            document.getElementById('soderlind-jsonld-add-social')?.addEventListener('click', function() {
                var container = document.getElementById('soderlind-jsonld-social-urls');
                var row = document.createElement('div');
                row.className = 'soderlind-jsonld-social-row';
                row.style.marginBottom = '5px';
                row.innerHTML = '<input type="url" name="<?php echo esc_js(Settings::OPTION_KEY); ?>[org_social_urls][]" value="" class="regular-text" placeholder="https://" />';
                container.appendChild(row);
            });
        })();
        </script>
        <?php
    }

    private function add_field(string $id, string $label, string $callback): void {
        add_settings_field(
            $id,
            $label,
            [$this, $callback],
            self::SLUG,
            'soderlind_jsonld_org',
        );
    }
}
