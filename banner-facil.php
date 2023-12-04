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

function bf_register_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Banners',
        'supports' => array('title', 'thumbnail'),
        'menu_icon' => 'dashicons-align-center'
    );
    register_post_type('bf_banner', $args);
}

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

function bf_add_metaboxes() {
    add_meta_box('bf_banner_details', 'Detalhes do Banner', 'bf_banner_metabox_callback', 'bf_banner', 'normal', 'high');
}

function bf_banner_metabox_callback($post) {
    wp_nonce_field('bf_save_banner_details', 'bf_banner_details_nonce');
    $redirect_url = get_post_meta($post->ID, 'bf_redirect_url', true);

    echo '<h4>Título</h4><p>' . esc_html(get_the_title($post->ID)) . '</p>';
    echo '<h4>Imagem de Destaque</h4>';
    echo has_post_thumbnail($post->ID) ? get_the_post_thumbnail($post->ID, 'full') : '<p>Nenhuma imagem de destaque definida.</p>';
    echo '<h4>URL de Redirecionamento</h4><input type="url" name="bf_redirect_url" value="' . esc_attr($redirect_url) . '" class="widefat">';
    echo '<h4>Shortcode</h4><input id="bf_banner_shortcode" type="text" value="[bf_display_banner id=\'' . $post->ID . '\']" class="widefat" readonly>';
    echo '<button type="button" class="button button-secondary" onclick="bf_copyShortcode()">Copiar Shortcode</button>';
    echo '<p id="bf_copySuccess" style="color: green; display: none;"><span class="dashicons dashicons-yes"></span> Copiado com sucesso!</p>';
}

function bf_save_banner_details($post_id) {
    if (!isset($_POST['bf_banner_details_nonce']) || !wp_verify_nonce($_POST['bf_banner_details_nonce'], 'bf_save_banner_details')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['bf_redirect_url'])) update_post_meta($post_id, 'bf_redirect_url', sanitize_text_field($_POST['bf_redirect_url']));
}

add_action('init', 'bf_register_post_type');
add_action('add_meta_boxes', 'bf_add_metaboxes');
add_action('save_post', 'bf_save_banner_details');
add_shortcode('bf_display_banner', 'bf_display_shortcode');

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
?>