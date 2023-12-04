<?php
/*
Plugin Name: Banner Fácil
Plugin URI: 
Description: Gerencia banners de anúncio com link de redirecionamento e imagem de destaque.
Version: 3.1
Author: Jean C. Lima
Author URI: 
License: GPLv2 or later
*/

defined('ABSPATH') or die('No script kiddies please!');

// Registra o tipo de post 'banner'
function bf_register_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Banners',
        'supports' => array('title', 'thumbnail'),
        'menu_icon' => 'dashicons-align-center',
        'menu_position' => 5
    );
    register_post_type('bf_banner', $args);
}

// Gera o shortcode do banner
function bf_display_shortcode($atts) {
    $atts = shortcode_atts(array('id' => ''), $atts);
    $post_id = $atts['id'];
    $redirect_url = get_post_meta($post_id, 'bf_redirect_url', true);
    $image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');

    if (!$image) {
        return '<p>Banner não encontrado.</p>';
    }

    return "<div class='bf_banner-container'><a href='" . esc_url($redirect_url) . "' target='_blank'><img src='" . esc_url($image[0]) . "' alt='Banner'></a></div>";
}

// Adiciona metaboxes para detalhes do banner e shortcode
function bf_add_metaboxes() {
    add_meta_box('bf_banner_redirect', 'URL de Redirecionamento', 'bf_banner_redirect_callback', 'bf_banner', 'normal', 'high');
    add_meta_box('bf_banner_shortcode', 'Shortcode do Banner', 'bf_banner_shortcode_callback', 'bf_banner', 'side', 'default');
}

// Callback para o metabox de URL de redirecionamento
function bf_banner_redirect_callback($post) {
    wp_nonce_field('bf_save_banner_redirect', 'bf_banner_redirect_nonce');
    $redirect_url = get_post_meta($post->ID, 'bf_redirect_url', true);
    echo '<h4>URL de Redirecionamento</h4><input type="url" name="bf_redirect_url" value="' . esc_attr($redirect_url) . '" class="widefat">';
}

// Callback para o metabox de shortcode
function bf_banner_shortcode_callback($post) {
    echo '<input id="bf_banner_shortcode" type="text" value="[bf_display_banner id=\'' . $post->ID . '\']" class="widefat" readonly>';
    echo '<button type="button" class="button button-secondary" onclick="bf_copyShortcode()">Copiar Shortcode</button>';
    echo '<p id="bf_copySuccess" style="color: green; display: none;"><span class="dashicons dashicons-yes"></span> Copiado com sucesso!</p>';
}

// Salva o valor do campo de URL de redirecionamento
function bf_save_banner_redirect($post_id) {
    if (!isset($_POST['bf_banner_redirect_nonce']) || !wp_verify_nonce($_POST['bf_banner_redirect_nonce'], 'bf_save_banner_redirect')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['bf_redirect_url'])) update_post_meta($post_id, 'bf_redirect_url', sanitize_text_field($_POST['bf_redirect_url']));
}

// Adiciona uma coluna para o shortcode na listagem de banners
function bf_add_shortcode_column($columns) {
    $columns['bf_shortcode'] = 'Shortcode';
    return $columns;
}
add_filter('manage_bf_banner_posts_columns', 'bf_add_shortcode_column');

// Exibe o shortcode na coluna da listagem de banners
function bf_display_shortcode_column($column, $post_id) {
    if ($column == 'bf_shortcode') {
        echo '[bf_display_banner id="' . $post_id . '"]';
    }
}
add_action('manage_bf_banner_posts_custom_column', 'bf_display_shortcode_column', 10, 2);

// Enfileira scripts para a área administrativa
function bf_admin_scripts() {
    ?>
    <script>
        function bf_copyShortcode() {
            var copyText = document.getElementById("bf_banner_shortcode");
            copyText.select();
            document.execCommand("copy");

            var copySuccess = document.getElementById("bf_copySuccess");
            copySuccess.style.display = "block";
            setTimeout(function() { copySuccess.style.display = "none"; }, 3000);
        }
    </script>
    <?php
}
add_action('admin_footer', 'bf_admin_scripts');

// Registra as ações e filtros
add_action('init', 'bf_register_post_type');
add_action('add_meta_boxes', 'bf_add_metaboxes');
add_action('save_post', 'bf_save_banner_redirect');
add_shortcode('bf_display_banner', 'bf_display_shortcode');
?>