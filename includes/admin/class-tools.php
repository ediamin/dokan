<?php

/**
 * Dokan Tools
 *
 * @since DOKAN_SINCE
 */
class Dokan_Tools {

    /**
     * Class constructor
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_create_pages', array( $this, 'create_default_pages' ) );
    }

    /**
     * Create default pages
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    function create_default_pages() {

        if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'create_pages' ) {
            return wp_send_json_error( __( 'You don\'t have enough permission', 'dokan', '403' ) );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return wp_send_json_error( __( 'You don\'t have enough permission', 'dokan', '403' ) );
        }

        $page_created = get_option( 'dokan_pages_created', false );
        $pages = array(
            array(
                'post_title' => __( 'Dashboard', 'dokan' ),
                'slug'       => 'dashboard',
                'page_id'    => 'dashboard',
                'content'    => '[dokan-dashboard]'
            ),
            array(
                'post_title' => __( 'Store List', 'dokan' ),
                'slug'       => 'store-listing',
                'page_id'    => 'store_listing',
                'content'    => '[dokan-stores]'
            ),
            array(
                'post_title' => __( 'My Orders', 'dokan-lite' ),
                'slug'       => 'my-orders',
                'page_id'    => 'my_orders',
                'content'    => '[dokan-my-orders]',
            ),
        );

        $dokan_pages = array() ;

        if ( ! $page_created ) {

            foreach ( $pages as $page ) {
                $page_id = wp_insert_post( array(
                    'post_title'     => $page['post_title'],
                    'post_name'      => $page['slug'],
                    'post_content'   => $page['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                        ) );
                $dokan_pages[ $page['page_id'] ] = $page_id ;
            }

            update_option( 'dokan_pages', $dokan_pages );
            flush_rewrite_rules();
        } else {
            foreach ( $pages as $page ) {

                if ( !$this->dokan_page_exist( $page['slug'] ) ) {
                    $page_id = wp_insert_post( array(
                        'post_title'     => $page['post_title'],
                        'post_name'      => $page['slug'],
                        'post_content'   => $page['content'],
                        'post_status'    => 'publish',
                        'post_type'      => 'page',
                        'comment_status' => 'closed'
                            ) );
                    $dokan_pages[ $page['page_id'] ] = $page_id ;
                    update_option( 'dokan_pages', $dokan_pages );
                }
            }

            flush_rewrite_rules();
        }

        update_option( 'dokan_pages_created', 1 );
        wp_send_json_success( array(
            'message' => __( 'All the default pages has been created!', 'dokan' )
        ), 201 );
        exit;
    }

    /**
     * Check a Donan shortcode  page exist or not
     *
     * @since DOKAN_SINCE
     *
     * @param type $slug
     *
     * @return boolean
     */
    function dokan_page_exist( $slug ) {
        if ( ! $slug ) {
            return false;
        }

        $page_created = get_option( 'dokan_pages_created', false );

        if ( ! $page_created ) {
            return false;
        }

        $page_list = get_option( 'dokan_pages', '' );
        $slug      = str_replace( '-', '_', $slug );
        $page      = isset( $page_list[$slug] ) ? get_post( $page_list[$slug] ) : null;

        if ( $page === null ) {
            return false;
        } else {
            return true;
        }
    }
}
