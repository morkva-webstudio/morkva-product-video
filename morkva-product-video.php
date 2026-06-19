<?php
/**
 * Plugin Name: Morkva Product Video
 * Description: Add an MP4 product video to a product gallery. Nice and simple.
 * Version: 1.0.1
 * Author: MORKVA
 * Author URI: https://morkva.co.ua
 * Text Domain: morkva-product-video
 * Domain Path: /languages
 * Tested up to: 6.9
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MRKV_PV_VER', '1.0.1' );
define( 'MRKV_PV_URL', plugin_dir_url( __FILE__ ) );
define( 'MRKV_PV_DIR', plugin_dir_path( __FILE__ ) );

const MRKV_PV_META_VIDEO_URL = '_mrkv_pv_video_url';
const MRKV_PV_META_THUMB_ID  = '_mrkv_pv_thumb_id';
const MRKV_PV_META_THUMB_URL = '_mrkv_pv_thumb_url';
const MRKV_PV_OPT_KEY = 'mrkv_pv_options'; // single options array

function mrkv_pv_default_options() {
        return [
                'single_autoplay' => 0,
                'catalog_autoplay' => 0,
                'video_autoplay' => 1,
                'video_loop'     => 1,
                'video_muted'    => 1,
                'video_position' => 'second',
        ];
}
function mrkv_pv_get_options() {
        $opts = get_option( MRKV_PV_OPT_KEY, [] );
        return wp_parse_args( is_array( $opts ) ? $opts : [], mrkv_pv_default_options() );
}

function mrkv_pv_get_video_position( $opts = null ) {
        return 'second';
}

add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
        $tabs['mrkv_product_video'] = [
                'label'    => __( 'Product Video', 'morkva-product-video' ),
                'target'   => 'mrkv_product_video_panel',
                'priority' => 80,
                'class'    => [],
        ];
        return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function() {
    $post_id   = get_the_ID();
    $thumb_id  = (int) get_post_meta( $post_id, MRKV_PV_META_THUMB_ID, true );
    $thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
    $video_url = get_post_meta( $post_id, MRKV_PV_META_VIDEO_URL, true );
    ?>
    <div id="mrkv_product_video_panel" class="panel woocommerce_options_panel">
        <div class="options_group">

         <?php wp_nonce_field( 'mrkv_pv_save_meta', 'mrkv_pv_nonce' ); ?>

            <?php
            woocommerce_wp_text_input( [
                'id'          => MRKV_PV_META_VIDEO_URL,
                'label'       => __( 'MP4 Video URL', 'morkva-product-video' ),
                'description' => __( 'Enter a URL to your MP4 file.', 'morkva-product-video' ),
                'desc_tip'    => true,
                'type'        => 'text',
                'value'       => $video_url,
            ] );
            ?>
            <p class="form-field">
                <button type="button" class="button mrkv-pv-upload-video">
                    <?php esc_html_e( 'Upload / Select Video', 'morkva-product-video' ); ?>
                </button>
            </p>

            <?php
            woocommerce_wp_text_input( [
                'id'          => MRKV_PV_META_THUMB_URL,
                'label'       => __( 'Video Thumbnail', 'morkva-product-video' ),
                'description' => __( 'Pick from Media Library.', 'morkva-product-video' ),
                'desc_tip'    => true,
                'type'        => 'text',
                'value'       => $thumb_url,
                'custom_attributes' => [ 'readonly' => 'readonly' ],
            ] );
            ?>
            <input type="hidden" id="<?php echo esc_attr( MRKV_PV_META_THUMB_ID ); ?>" name="<?php echo esc_attr( MRKV_PV_META_THUMB_ID ); ?>" value="<?php echo esc_attr( $thumb_id ); ?>"/>

            <p class="form-field">
                <button type="button" class="button mrkv-pv-upload-thumb">
                    <?php esc_html_e( 'Upload / Select Thumbnail', 'morkva-product-video' ); ?>
                </button>
                <button type="button" class="button mrkv-pv-clear-thumb">
                    <?php esc_html_e( 'Clear', 'morkva-product-video' ); ?>
                </button>
            </p>

            <?php if ( $thumb_url ) : ?>
                <p><img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;padding:2px;background:#fff;"></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
} );

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
        // Capability check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
        }

        // Verify nonce
         if ( ! isset( $_POST['mrkv_pv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mrkv_pv_nonce'] ) ), 'mrkv_pv_save_meta' ) ) {
                return;
        }

        // Ignore autosaves/revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
                return;
        }

        // Sanitize & save fields
        if ( isset( $_POST[ MRKV_PV_META_VIDEO_URL ] ) ) {
                $video = esc_url_raw( wp_unslash( $_POST[ MRKV_PV_META_VIDEO_URL ] ) );
                update_post_meta( $post_id, MRKV_PV_META_VIDEO_URL, $video );
        }

        $thumb_id = isset( $_POST[ MRKV_PV_META_THUMB_ID ] ) ? (int) wp_unslash( $_POST[ MRKV_PV_META_THUMB_ID ] ) : 0;

        if ( $thumb_id > 0 ) {
                update_post_meta( $post_id, MRKV_PV_META_THUMB_ID, $thumb_id );
                update_post_meta( $post_id, MRKV_PV_META_THUMB_URL, wp_get_attachment_url( $thumb_id ) );
        } else {
                delete_post_meta( $post_id, MRKV_PV_META_THUMB_ID );
                delete_post_meta( $post_id, MRKV_PV_META_THUMB_URL );
        }
} );

add_action( 'admin_enqueue_scripts', function( $hook ) {
        if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
                $screen = get_current_screen();
                if ( empty( $screen->post_type ) || 'product' !== $screen->post_type ) {
                        return;
                }

                wp_enqueue_media();
                wp_enqueue_script(
                        'mrkv-pv-admin',
                        MRKV_PV_URL . 'assets/mrkv-admin.js',
                        [ 'jquery' ],
                        MRKV_PV_VER,
                        true
                );
        }

        if ( 'toplevel_page_morkva-product-video' === $hook ) {
                wp_register_style(
                        'mrkv-pv-admin-style',
                        MRKV_PV_URL . 'assets/mrkv-admin.css',
                        [],
                        MRKV_PV_VER
                );

                wp_enqueue_style( 'mrkv-pv-admin-style' );
        }
    }
);

add_filter( 'woocommerce_product_get_gallery_image_ids', function ( $ids, $product ) {
        $thumb_id = (int) get_post_meta( $product->get_id(), MRKV_PV_META_THUMB_ID, true );
        if ( ! $thumb_id || in_array( $thumb_id, $ids, true ) ) {
                return $ids;
        }

        array_unshift( $ids, $thumb_id );

        return $ids;
}, 10, 2 );

add_filter( 'woocommerce_single_product_image_thumbnail_html', function( $html, $attachment_id ) {
        global $product;
        if ( ! $product instanceof WC_Product ) return $html;

        static $video_done = false;
        if ( $video_done ) return $html;

        $post_id  = $product->get_id();
        $thumb_id = (int) get_post_meta( $post_id, MRKV_PV_META_THUMB_ID, true );
        $video    = get_post_meta( $post_id, MRKV_PV_META_VIDEO_URL, true );
        $thumb    = get_post_meta( $post_id, MRKV_PV_META_THUMB_URL, true );

        if ( ! $thumb_id || (int) $attachment_id !== $thumb_id || empty( $video ) ) {
                return $html;
        }

        $opts            = mrkv_pv_get_options();
        $autoplay        = ! empty( $opts['video_autoplay'] );
        $loop            = ! empty( $opts['video_loop'] );
        $muted           = ! empty( $opts['video_muted'] );
        $single_autoplay = ! empty( $opts['single_autoplay'] );

        $attrs = [];
        if ( $autoplay ) {
                $attrs[] = 'autoplay';
        }
        if ( $loop ) {
                $attrs[] = 'loop';
        }
        if ( $muted ) {
                $attrs[] = 'muted';
        }
        $attrs[] = 'controls';
        $attrs[] = 'playsinline';
        $attr_str = implode( ' ', $attrs );

        $video_done = true;

        $poster_attr = $thumb ? ' poster="' . esc_url( $thumb ) . '"' : '';
        $thumb_attr  = $thumb ? ' data-thumb="' . esc_url( $thumb ) . '"' : '';

return '<div class="woocommerce-product-gallery__image"' . $thumb_attr . '>
  <div class="mrkv-pv__ratio" style="position:relative; padding-top:100%; overflow:hidden;">
    <video ' . $attr_str . ' preload="none"' . $poster_attr . ' style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
      <source src="' . esc_url( $video ) . '" type="video/mp4">
      ' . esc_html__( 'Your browser does not support the video tag.', 'morkva-product-video' ) . '
    </video>
  </div>
</div>';


}, 10, 2 );


add_action( 'wp_head', function () {
        if ( ! is_product() ) {
                return;
        }

        $css = '.mrkv-pv__ratio video{display:block;}';

        echo '<style id="mrkv-pv-frontend-inline">' . wp_strip_all_tags( $css ) . '</style>';
}, 20 );

add_action( 'admin_menu', function () {
        add_menu_page(
                __( 'MRKV Product video', 'morkva-product-video' ),
                __( 'MRKV Product video', 'morkva-product-video' ),
                'manage_options',
                'morkva-product-video',
                'mrkv_pv_render_settings_page',
                'dashicons-format-video'
        );
} );

add_action( 'admin_init', function () {
         register_setting( 'mrkv_pv_group', MRKV_PV_OPT_KEY, [
                'type'              => 'array',
                'sanitize_callback' => 'mrkv_pv_sanitize_options',
                'default'           => mrkv_pv_default_options(),
        ] );

        add_settings_section( 'mrkv_pv_section_general', __( 'General', 'morkva-product-video' ), '__return_false', 'morkva-product-video' );
        add_settings_section( 'mrkv_pv_section_single', __( 'Single product page (available in pro-version)', 'morkva-product-video' ), '__return_false', 'morkva-product-video' );
        add_settings_section( 'mrkv_pv_section_catalog', __( 'Catalog previews (available in pro-version)', 'morkva-product-video' ), '__return_false', 'morkva-product-video' );

        add_settings_field( 'video_autoplay', __( 'Autoplay video', 'morkva-product-video' ), 'mrkv_pv_field_video_autoplay', 'morkva-product-video', 'mrkv_pv_section_general' );
        add_settings_field( 'video_loop', __( 'Loop video', 'morkva-product-video' ), 'mrkv_pv_field_video_loop', 'morkva-product-video', 'mrkv_pv_section_general' );
        add_settings_field( 'video_muted', __( 'Mute video on start', 'morkva-product-video' ), 'mrkv_pv_field_video_muted', 'morkva-product-video', 'mrkv_pv_section_general' );
        add_settings_field( 'video_position', __( 'Video position in gallery', 'morkva-product-video' ), 'mrkv_pv_field_video_position', 'morkva-product-video', 'mrkv_pv_section_general' );

        add_settings_field( 'single_autoplay', __( 'Autoplay on single product page', 'morkva-product-video' ), 'mrkv_pv_field_single_autoplay', 'morkva-product-video', 'mrkv_pv_section_single' );

        add_settings_field( 'catalog_autoplay', __( 'Autoplay in catalog', 'morkva-product-video' ), 'mrkv_pv_field_catalog_autoplay', 'morkva-product-video', 'mrkv_pv_section_catalog' );
        add_settings_field( 'card_selector', __( 'Product card selector', 'morkva-product-video' ), 'mrkv_pv_field_card_selector', 'morkva-product-video', 'mrkv_pv_section_catalog' );
        add_settings_field( 'image_selector', __( 'Image container selector', 'morkva-product-video' ), 'mrkv_pv_field_image_selector', 'morkva-product-video', 'mrkv_pv_section_catalog' );
} );

function mrkv_pv_sanitize_options( $input ) {
        $def = mrkv_pv_default_options();
        $out = is_array( $input ) ? $input : [];

         $out['video_autoplay'] = empty( $out['video_autoplay'] ) ? 0 : 1;
        $out['video_loop']     = empty( $out['video_loop'] ) ? 0 : 1;
        $out['video_muted']    = empty( $out['video_muted'] ) ? 0 : 1;
        $out['video_position'] = 'second';
        $out['single_autoplay']  = 0;
        $out['catalog_autoplay'] = 0;

        return $out;
}

function mrkv_pv_render_settings_page() {
          if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
                <h1><?php esc_html_e( 'MRKV Product Video', 'morkva-product-video' ); ?></h1>
                <div class="mrkv-pv-settings-columns">
                        <div class="mrkv-pv-settings-main">
                                <form method="post" action="options.php">
                                        <?php
                                                settings_fields( 'mrkv_pv_group' );
                                                $opts = mrkv_pv_get_options();
                                        ?>
               
                        <h2><?php esc_html_e( 'General', 'morkva-product-video' ); ?></h2>
                        <table class="form-table" role="presentation">
                                <tbody>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Autoplay video', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_video_autoplay( $opts ); ?></td>
                                        </tr>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Loop video', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_video_loop( $opts ); ?></td>
                                        </tr>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Mute video on start', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_video_muted( $opts ); ?></td>
                                        </tr>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Video position in gallery', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_video_position( $opts ); ?></td>
                                        </tr>
                                </tbody>
                        </table>
                        <h2><?php esc_html_e( 'Single product page (available in pro-version)', 'morkva-product-video' ); ?></h2>
                        <table class="form-table" role="presentation">
                                <tbody>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Autoplay on single product page', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_single_autoplay( $opts ); ?></td>
                                        </tr>
                                </tbody>
                        </table>
                         <h2><?php esc_html_e( 'Catalog previews (available in pro-version)', 'morkva-product-video' ); ?></h2>
                        <table class="form-table" role="presentation">
                                <tbody>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Autoplay in catalog', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_catalog_autoplay( $opts ); ?></td>
                                        </tr>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Product card selector', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_card_selector( $opts ); ?></td>
                                        </tr>
                                        <tr>
                                                <th scope="row"><?php esc_html_e( 'Image container selector', 'morkva-product-video' ); ?></th>
                                                <td><?php mrkv_pv_field_image_selector( $opts ); ?></td>
                                        </tr>
                                </tbody>
                        </table>
                        <?php submit_button(); ?>
             
                    </form>
                        </div>
                        <aside class="mrkv-pv-settings-support">
                                <h2><?php esc_html_e( 'Support', 'morkva-product-video' ); ?></h2>
                                <p><?php esc_html_e( 'Need help or paid customization? Feel free to contact us at', 'morkva-product-video' ); ?>
                                        <a href="mailto:support@morkva.co.ua">support@morkva.co.ua</a>
                                </p>
                        </aside>
                </div>
             
        </div>
        <?php
}

/* --- Field renderers (use $opts if passed from manual table) --- */
function mrkv_pv_field_video_autoplay( $opts = null ) {
    $opts = $opts ?: mrkv_pv_get_options();
    printf(
        '<label><input type="checkbox" name="%1$s[video_autoplay]" value="1" %2$s> %3$s</label><p class="description">%4$s</p>',
        esc_attr( MRKV_PV_OPT_KEY ),
        checked( 1, (int) $opts['video_autoplay'], false ),
        esc_html__( 'Start playback automatically when the video loads.', 'morkva-product-video' ),
        esc_html__( 'Most mobile browsers require the video to be muted for autoplay to work.', 'morkva-product-video' )
    );
}

function mrkv_pv_field_video_loop( $opts = null ) {
        $opts = $opts ?: mrkv_pv_get_options();
        printf(
                '<label><input type="checkbox" name="%1$s[video_loop]" value="1" %2$s> %3$s</label>',
                esc_attr( MRKV_PV_OPT_KEY ),
                checked( 1, (int) $opts['video_loop'], false ),
                esc_html__( 'Restart playback automatically.', 'morkva-product-video' )
        );
}
function mrkv_pv_field_video_muted( $opts = null ) {
        $opts = $opts ?: mrkv_pv_get_options();
        printf(
                '<label><input type="checkbox" name="%1$s[video_muted]" value="1" %2$s> %3$s</label>',
                esc_attr( MRKV_PV_OPT_KEY ),
                checked( 1, (int) $opts['video_muted'], false ),
                esc_html__( 'Mute the video when it starts playing.', 'morkva-product-video' )
        );
}
function mrkv_pv_field_video_position( $opts = null ) {
        $opts = $opts ?: mrkv_pv_get_options();
        $position = mrkv_pv_get_video_position( $opts );
        printf(
                '<select name="%1$s[video_position]" disabled="disabled">
                        <option value="second" %2$s>%3$s</option>
                        <option value="last" %4$s>%5$s</option>
                </select><p class="description">%6$s</p>',
                esc_attr( MRKV_PV_OPT_KEY ),
                selected( 'second', $position, false ),
                esc_html__( 'Second (default)', 'morkva-product-video' ),
                selected( 'last', $position, false ),
                esc_html__( 'Last', 'morkva-product-video' ),
                esc_html__( '(Pro-only) Choose where to place the video thumbnail within the product gallery.', 'morkva-product-video' )
        );
}

function mrkv_pv_field_single_autoplay( $opts = null ){
        printf(
                '<label><input type="checkbox" name="%1$s[single_autoplay]" value="1" disabled="disabled"> %2$s</label><p class="description">%3$s</p>',
                esc_attr( MRKV_PV_OPT_KEY ),
                esc_html__( 'Play the product video automatically on the single product gallery.', 'morkva-product-video' ),
                esc_html__( 'When enabled the video replaces the main image, is forced to the first slide, and plays muted on a loop.', 'morkva-product-video' )
        );
}
function mrkv_pv_field_catalog_autoplay( $opts = null ){

        printf(
                '<label><input type="checkbox" name="%1$s[catalog_autoplay]" value="1" disabled="disabled"> %2$s</label><p class="description">%3$s</p>',
                esc_attr( MRKV_PV_OPT_KEY ),
                esc_html__( 'Autoplay videos for product cards when they enter the viewport.', 'morkva-product-video' ),
                esc_html__( 'Autoplays one product card at a time as shoppers scroll through the catalog.', 'morkva-product-video' )
        );
}
function mrkv_pv_field_card_selector( $opts = null ){
    $example = '.products .product, .product-small, .product';
    printf(
        '<input type="text" class="regular-text code" value="%1$s" disabled="disabled"> <p class="description">%2$s</p>',
        esc_attr( $example ),
        esc_html__( 'CSS selector for product cards on catalog pages.', 'morkva-product-video' )
    );
}

function mrkv_pv_field_image_selector( $opts = null ){
    $example = '.box-image, .product-image, .attachment-woocommerce_thumbnail';
    printf(
        '<input type="text" class="regular-text code" value="%1$s" disabled="disabled"> <p class="description">%2$s</p>',
        esc_attr( $example ),
        esc_html__( 'CSS selector for the image container inside each product card.', 'morkva-product-video' )
    );
}