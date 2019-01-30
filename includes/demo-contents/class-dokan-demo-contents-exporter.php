<?php

namespace Dokan\DemoContents;

use Dokan\Traits\Singleton;
use WP_Query;

class Exporter {

    use Singleton;

    /**
     * WP upload directory path
     *
     * @since DOKAN_SINCE
     *
     * @var string
     */
    private $wp_upload_dir = '';

    /**
     * Exported image id-title object
     *
     * @since DOKAN_SINCE
     *
     * @var array
     */
    private $images = [];

    /**
     * Export process intiator
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function export() {
        $this->wp_upload_dir = wp_get_upload_dir();

        dokan_demo_contents()->remove_root_dir();
        dokan_demo_contents()->make_dir();

        $this->export_vendors();
        $this->export_customers();
        $this->export_products();
        $this->export_orders();
        $this->export_options();
        $this->export_images();
    }

    /**
     * WordPress user data
     *
     * @since DOKAN_SINCE
     *
     * @param array $args
     *
     * @return array
     */
    protected function get_wp_users( $args ) {
        $wp_users = $query->get_results();

        $users = [];

        foreach ( $wp_users as $wp_user ) {
            $wp_user_meta = get_user_meta( $wp_user->ID );

            $users[] = [
                'user' => $wp_user->data,
                'meta' => $wp_user_meta,
            ];
        }

        return $users;
    }

    /**
     * Export vendors
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function export_vendors() {
        $args = [
            'role'    => 'seller',
            'number'  => -1,
            'orderby' => 'registered',
            'order'   => 'ASC',
        ];

        $vendors = $this->get_wp_users( $args );

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/vendors.json', $vendors );
    }

    protected function export_customers() {
        $args = [
            'role'    => 'customer',
            'number'  => -1,
            'orderby' => 'registered',
            'order'   => 'ASC',
        ];

        $customers = $this->get_wp_users( $args );

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/customers.json', $customers );
    }

    /**
     * Export products
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function export_products() {
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];

        $query = new WP_Query( $args );

        $products = [];

        if ( ! empty( $query->posts ) ) {
            foreach ( $query->posts as $post ) {
                $wp_post_meta = get_post_meta( $post->ID );

                $thumbnail = get_post_meta( $post->ID, '_thumbnail_id', true );

                if ( $thumbnail ) {
                    $thumbnail_meta = wp_get_attachment_metadata( $thumbnail, true );
                    $src            = $this->wp_upload_dir['basedir'] . '/' . $thumbnail_meta['file'];
                    $file_name      = basename( $src );

                    $this->images[ $thumbnail ] = $file_name;

                    dokan_demo_contents()->copy_file( $src, '/images/' . $file_name );
                }

                $gallery   = get_post_meta( $post->ID, '_product_image_gallery', true );

                if ( $gallery ) {
                    $gallery = array_map( 'absint', explode( ',', $gallery ) );

                    foreach ( $gallery as $image_id ) {
                        $image_meta = wp_get_attachment_metadata( $image_id, true );
                        $src        = $this->wp_upload_dir['basedir'] . '/' . $image_meta['file'];
                        $file_name  = basename( $src );

                        $this->images[ $image_id ] = $file_name;

                        dokan_demo_contents()->copy_file( $src, '/images/' . $file_name );
                    }
                }

                $products[] = [
                    'post' => $post,
                    'meta' => $wp_post_meta,
                ];
            }
        }

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/products.json', $products );
    }

    /**
     * Export orders
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function export_orders() {
        global $wpdb;

        $args = [
            'post_type'      => 'shop_order',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'post_status'    => array_keys( wc_get_order_statuses() ),
        ];

        $query = new WP_Query( $args );

        $orders = [];

        if ( ! empty( $query->posts ) ) {
            foreach ( $query->posts as $post ) {
                $wp_post_meta = get_post_meta( $post->ID );

                $dokan_orders = $wpdb->get_results( $wpdb->prepare(
                    "
                        select * from {$wpdb->prefix}dokan_orders
                        where `order_id` = %d
                    ",
                    $post->ID
                ) );

                $order_items = [];

                $items = $wpdb->get_results( $wpdb->prepare(
                    "
                        select * from {$wpdb->prefix}woocommerce_order_items
                        where `order_id` = %d
                    ",
                    $post->ID
                ) );

                if ( ! empty( $items )  ) {
                    foreach ( $items as $item ) {
                        $item_meta = $wpdb->get_results( $wpdb->prepare(
                            "
                                select * from {$wpdb->prefix}woocommerce_order_itemmeta
                                where `order_item_id` = %d
                            ",
                            $item->order_item_id
                        ) );

                        $order_items[] = [
                            'item' => $item,
                            'meta' => $item_meta,
                        ];
                    }
                }


                $orders[] = [
                    'post'         => $post,
                    'meta'         => $wp_post_meta,
                    'dokan_orders' => $dokan_orders,
                    'items'        => $order_items,
                ];
            }
        }

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/orders.json', $orders );
    }

    /**
     * Export options
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function export_options() {
        global $wpdb;

        $dokan_options = apply_filters( 'dokan_demo_contents_export_options', [
            // basic
            'dokan_pages',
            'dokan_pages_created',

            // settings
            'dokan_general',
            'dokan_selling',
            'dokan_withdraw',
        ] );

        $count        = count( $dokan_options );
        $placeholders = array_fill( 0, $count, '%s' );
        $format       = implode( ',', $placeholders );

        $options = $wpdb->get_results( $wpdb->prepare(
            "
                select `option_name`, `option_value`, `autoload`
                from {$wpdb->options}
                where `option_name` in ({$format})
            ",
            $dokan_options
        ) );

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/options.json', $options );
    }

    /**
     * Export image data
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function export_images() {
        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/images.json', $this->images );
    }
}
