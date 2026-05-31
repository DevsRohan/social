<?php
/**
 * Settings — manages plugin settings registration and defaults.
 *
 * @package SocialProofLive\Admin
 */

namespace SocialProofLive\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Registers settings, provides getters, and handles product-level meta.
 */
class Settings {

    /**
     * Initialize settings hooks.
     *
     * @return void
     */
    public function init() {
        // Add product-level settings meta box.
        add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ) );
        add_action( 'save_post_product', array( $this, 'save_product_meta' ) );
    }

    /**
     * Add meta box to product edit screen.
     *
     * @return void
     */
    public function add_product_meta_box() {
        add_meta_box(
            'splive_product_settings',
            __( 'Social Proof LIVE', 'social-proof-live' ),
            array( $this, 'render_product_meta_box' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render the product-level meta box.
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_product_meta_box( $post ) {
        $disabled = get_post_meta( $post->ID, '_splive_disabled', true );
        wp_nonce_field( 'splive_product_meta', 'splive_product_meta_nonce' );
        ?>
        <p>
            <label>
                <input type="checkbox" name="splive_disabled" value="1" <?php checked( $disabled, '1' ); ?> />
                <?php esc_html_e( 'Disable social proof for this product', 'social-proof-live' ); ?>
            </label>
        </p>
        <p class="description">
            <?php esc_html_e( 'When checked, the live viewer count, cart count, and recent purchase widgets will not appear on this product page.', 'social-proof-live' ); ?>
        </p>
        <?php
    }

    /**
     * Save product-level meta.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public function save_product_meta( $post_id ) {
        if ( ! isset( $_POST['splive_product_meta_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['splive_product_meta_nonce'] ) ), 'splive_product_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $disabled = isset( $_POST['splive_disabled'] ) ? '1' : '';
        update_post_meta( $post_id, '_splive_disabled', $disabled );
    }

    /**
     * Check if social proof is disabled for a product.
     *
     * @param int $product_id Product ID.
     * @return bool True if disabled.
     */
    public static function is_product_disabled( $product_id ) {
        return '1' === get_post_meta( $product_id, '_splive_disabled', true );
    }
}
