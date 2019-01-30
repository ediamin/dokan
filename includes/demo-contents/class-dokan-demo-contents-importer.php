<?php

namespace Dokan\DemoContents;

use Dokan\Traits\Singleton;
use WP_User;

class Importer {

    use Singleton;

    /**
     * Import process initiator
     *
     * @since DOKAN_SINCE
     *
     * @param string $attachment file path for zip file that contains the exported files
     *
     * @return void
     */
    public function import( $attachment ) {
        dokan_demo_contents()->remove_root_dir();

        $unzipped = unzip_file( $attachment, WP_CONTENT_DIR );

        if ( is_wp_error( $unzipped ) ) {
            return $unzipped;
        }

        $this->import_vendors();
        // $this->import_customers();
        $this->import_products();
    }

    /**
     * Import vendors
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function import_vendors() {
        $vendors = dokan_demo_contents()->get_json_data( DOKAN_DEMO_CONTENTS_STORE . '/vendors.json' );

        if ( empty( $vendors ) ) {
            return;
        }

        $vendor_map = [];

        foreach ( $vendors as $vendor ) {
            $userdata  = $vendor['user'];
            $user_meta = $vendor['meta'];

            $exported_user_id = $userdata['ID'];

            $email_exists    = email_exists( $userdata['user_email'] );
            $username_exists = username_exists( $userdata['user_login'] );

            $imported_user_id = 0;

            if ( $email_exists ) {
                $imported_user_id = $email_exists;
            } else if ( $username_exists ) {
                $imported_user_id = $username_exists;
            }

            if ( $imported_user_id ) {
                $userdata['ID']   = $imported_user_id;
                $imported_user_id = wp_update_user( $userdata );
            } else {
                unset( $userdata['ID'] );
                $imported_user_id = wp_insert_user( $userdata );
            }

            $wp_user = new WP_User( $imported_user_id );

            $wp_user->add_role( 'seller' );

            if ( ! empty( $user_meta ) ) {
                foreach ( $user_meta as $meta_key => $meta_value ) {
                    delete_user_meta( $imported_user_id, $meta_key );

                    foreach ( $meta_value as $value ) {
                        add_user_meta( $imported_user_id, $meta_key, maybe_unserialize( $value ), false );
                    }
                }
            }

            $vendor_map[ $exported_user_id ] = $imported_user_id;
        }

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/vendor-map.json', $vendor_map );
    }

    /**
     * Import customers
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function import_customers() {
        $customers = dokan_demo_contents()->get_json_data( DOKAN_DEMO_CONTENTS_STORE . '/customers.json' );

        if ( empty( $customers ) ) {
            return;
        }

        $customer_map = [];

        foreach ( $customers as $customer ) {
            $userdata  = $customer['user'];
            $user_meta = $customer['meta'];

            $exported_user_id = $userdata['ID'];

            $email_exists    = email_exists( $userdata['user_email'] );
            $username_exists = username_exists( $userdata['user_login'] );

            $imported_user_id = 0;

            if ( $email_exists ) {
                $imported_user_id = $email_exists;
            } else if ( $username_exists ) {
                $imported_user_id = $username_exists;
            }

            if ( $imported_user_id ) {
                $userdata['ID']   = $imported_user_id;
                $imported_user_id = wp_update_user( $userdata );
            } else {
                unset( $userdata['ID'] );
                $imported_user_id = wp_insert_user( $userdata );
            }

            $wp_user = new WP_User( $imported_user_id );

            $wp_user->add_role( 'customer' );

            if ( ! empty( $user_meta ) ) {
                foreach ( $user_meta as $meta_key => $meta_value ) {
                    delete_user_meta( $imported_user_id, $meta_key );

                    foreach ( $meta_value as $value ) {
                        add_user_meta( $imported_user_id, $meta_key, maybe_unserialize( $value ), false );
                    }
                }
            }

            $customer_map[ $exported_user_id ] = $imported_user_id;
        }

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/customer-map.json', $customer_map );
    }

    /**
     * Import products
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function import_products() {
        $products = dokan_demo_contents()->get_json_data( DOKAN_DEMO_CONTENTS_STORE . '/products.json' );

        if ( empty( $products ) ) {
            return;
        }

        $product_map = [];

        $vendor_map      = dokan_demo_contents()->get_json_data( DOKAN_DEMO_CONTENTS_STORE . '/vendor-map.json' );
        $exported_images = dokan_demo_contents()->get_json_data( DOKAN_DEMO_CONTENTS_STORE . '/images.json' );

        foreach ( $products as $product ) {
            $product_data = $product['post'];
            $product_meta = $product['meta'];

            if ( ! isset( $product_meta['_thumbnail_id'] ) ) {
                continue;
            }

            if ( isset( $vendor_map[ $product_data['post_author'] ] ) ) {
                $product_data['post_author'] = $vendor_map[ $product_data['post_author'] ];
            }

            $exported_product_id = $product_data['ID'];

            unset( $product_data['ID'] );

            $imported_product_id = wp_insert_post( $product_data );

            if ( ! is_wp_error( $imported_product_id ) && ! empty( $product_meta ) ) {
                foreach ( $product_meta as $meta_key => $meta_value ) {
                    if ( '_thumbnail_id' === $meta_key && $meta_value ) {
                        $image_id = absint( $meta_value[0] );

                        if ( isset( $exported_images[ $image_id ] ) ) {
                            $attachment_id = dokan_demo_contents()->upload_image( $exported_images[ $image_id ], $imported_product_id );

                            if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
                                update_post_meta( $imported_product_id, '_thumbnail_id', $attachment_id );
                            }
                        }

                    } else if ( '_product_image_gallery' === $meta_key && $meta_value ) {
                        $gallery        = explode( ',', $meta_value[0] );
                        $attachment_ids = [];

                        foreach ( $gallery as $image_id ) {
                            $image_id = absint( $image_id );

                            if ( isset( $exported_images[ $image_id ] ) ) {
                                $attachment_id = dokan_demo_contents()->upload_image( $exported_images[ $image_id ], $imported_product_id );

                                if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
                                    $attachment_ids[] = $attachment_id;
                                }
                            }
                        }

                        if ( $attachment_ids ) {
                            update_post_meta( $imported_product_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
                        }

                    } else {
                        foreach ( $meta_value as $value ) {
                            add_post_meta( $imported_product_id, $meta_key, maybe_unserialize( $value ), false );
                        }
                    }
                }

                $product_map[ $exported_product_id ] = $imported_product_id;
            }
        }

        dokan_demo_contents()->save_contents( DOKAN_DEMO_CONTENTS_STORE . '/product-map.json', $product_map );
    }
}
