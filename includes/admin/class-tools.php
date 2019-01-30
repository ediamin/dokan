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
        add_action( 'wp_ajax_dokan_import_demo_contents', array( $this, 'import_demo_contents' ) );
        add_action( 'wp_ajax_dokan_export_demo_contents', array( $this, 'export_demo_contents' ) );
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
     * Check a Dokan shortcode  page exist or not
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

    /**
     * Import demo contents
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function import_demo_contents() {
        check_ajax_referer( 'dokan_admin', '_nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( "You don't have permission to perform this action.", 'dokan-lite' ) ), 403 );
        }

        $attatchment_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0;

        if ( ! $attatchment_id ) {
            wp_send_json_error( array( 'message' => __( "Invalid id", 'dokan-lite' ) ), 400 );
        }

        $attachment = get_attached_file( $attatchment_id );

        if ( ! $attachment || ! file_exists( $attachment ) ) {
            wp_send_json_error( array( 'message' => __( "Invalid attachment.", 'dokan-lite' ) ), 400 );
        }

        try {
            $import = dokan_demo_contents()->importer->import( $attachment );

            if ( is_wp_error( $import ) ) {
                throw new Exception( $import->get_error_message() );
            }
        } catch ( Exception $e ) {
            $message = $e->getMessage();
            $code    = $e->getCode() ? $e->getCode() : 422;

            wp_send_json_error( array( 'message' => $message ), $code );
        }

        return [];
    }

    /**
     * Export demo contents
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function export_demo_contents() {
        check_ajax_referer( 'dokan_admin', '_nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( "You don't have permission to perform this action.", 'dokan-lite' ) ), 403 );
        }

        try {
            dokan_demo_contents()->exporter->export();
        } catch ( Exception $e ) {
            $message = $e->getMessage();
            $code    = $e->getCode() ? $e->getCode() : 422;

            wp_send_json_error( array( 'message' => $message ), $code );
        }

        return [];
    }
}
