<?php
/**
 * Plugin Name: MF-Ads
 * Description: Gestión avanzada de cabeceras publicitarias para diferentes secciones del sitio
 * Version: 1.0.0
 * Author: Mauricio Aguirre - mauroaguirre.com
 * License: GPL-2.0+
 * Text Domain: medios-federales
 */

defined('ABSPATH') || exit;

class MediosFederales_Plugin {
    private $sections = [
        'home'       => 'Portada',
        'category'   => 'Categorías',
        'page'       => 'Páginas',
        'archive'    => 'Archivos',
        'post'       => 'Entradas',
        'author'     => 'Autores',
        'search'     => 'Búsqueda',
        '404'        => 'Error 404',
        'coctelera'  => 'Categoría Coctelera',
        'ferozidades'=> 'Categoría Ferozidades',
        'columnistas'=> 'Página Columnistas'
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'inject_ad_code'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Medios Federales',
            'Medios Federales',
            'manage_options',
            'medios-federales',
            [$this, 'render_admin_page'],
            'dashicons-media-document',
            80
        );
    }

    public function register_settings() {
        register_setting('medios_federales_group', 'medios_federales_options');

        add_settings_section(
            'section_headers',
            'Configuración de Cabeceras Publicitarias',
            [$this, 'render_section_description'],
            'medios-federales'
        );

        foreach ($this->sections as $key => $label) {
            add_settings_field(
                'header_' . $key,
                $label,
                [$this, 'render_textarea_field'],
                'medios-federales',
                'section_headers',
                ['key' => $key, 'label' => $label]
            );
        }
    }

    public function render_section_description() {
        echo '<p>Configura los códigos de publicidad para cada sección del sitio. Puedes usar HTML, JavaScript y shortcodes.</p>';
    }

    public function render_textarea_field($args) {
        $options = get_option('medios_federales_options');
        $value = isset($options[$args['key']]) ? $options[$args['key']] : '';
        printf(
            '<textarea name="medios_federales_options[%s]" rows="8" cols="80" class="large-text code">%s</textarea>',
            esc_attr($args['key']),
            esc_textarea($value)
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-media-document"></span> Medios Federales</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('medios_federales_group');
                do_settings_sections('medios-federales');
                submit_button('Guardar Configuración');
                ?>
            </form>
        </div>
        <?php
    }

    public function inject_ad_code() {
        $options = get_option('medios_federales_options');
        if (empty($options)) return;

        // Detección especializada para plantillas personalizadas
        if (is_category('coctelera') && !empty($options['coctelera'])) {
            echo $this->sanitize_output($options['coctelera']);
        } elseif (is_category('ferozidades') && !empty($options['ferozidades'])) {
            echo $this->sanitize_output($options['ferozidades']);
        } elseif (is_page('columnistas') && !empty($options['columnistas'])) {
            echo $this->sanitize_output($options['columnistas']);
        } elseif (is_front_page() && !empty($options['home'])) {
            echo $this->sanitize_output($options['home']);
        } elseif (is_category() && !empty($options['category'])) {
            echo $this->sanitize_output($options['category']);
        } elseif (is_page() && !empty($options['page'])) {
            echo $this->sanitize_output($options['page']);
        } elseif (is_archive() && !empty($options['archive'])) {
            echo $this->sanitize_output($options['archive']);
        } elseif (is_single() && !empty($options['post'])) {
            echo $this->sanitize_output($options['post']);
        } elseif (is_author() && !empty($options['author'])) {
            echo $this->sanitize_output($options['author']);
        } elseif (is_search() && !empty($options['search'])) {
            echo $this->sanitize_output($options['search']);
        } elseif (is_404() && !empty($options['404'])) {
            echo $this->sanitize_output($options['404']);
        }
    }

    private function sanitize_output($content) {
        // Permite HTML seguro y scripts pero limpia el output
        $allowed = wp_kses_allowed_html('post');
        $allowed['script'] = [
            'type' => true,
            'src' => true,
            'async' => true,
            'defer' => true
        ];
        return wp_kses($content, $allowed);
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_medios-federales' !== $hook) {
            return;
        }
        wp_enqueue_style(
            'medios-federales-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/admin.css')
        );
    }
}

new MediosFederales_Plugin();
