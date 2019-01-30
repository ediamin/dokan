<?php

namespace Dokan\DemoContents;

use Dokan\Traits\Singleton;
use Exception;

class Controller {

    use Singleton;

    /**
     * WP Filesystem
     *
     * @since DOKAN_SINCE
     *
     * @var null|WP_Filesystem_Base
     */
    public $fs = null;

    /**
     * Execute on first instance
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function boot() {
        $this->define_constants();
        $this->set_fs();
    }

    /**
     * Magic get method
     *
     * @since DOKAN_SINCE
     *
     * @param string $prop
     *
     * @return void
     */
    public function __get( $prop ) {
        switch ( $prop ) {
            case 'importer':
                require_once DOKAN_DEMO_CONTENTS_DIR . '/class-dokan-demo-contents-importer.php';
                return \Dokan\DemoContents\Importer::instance();
                break;

            case 'exporter':
                require_once DOKAN_DEMO_CONTENTS_DIR . '/class-dokan-demo-contents-exporter.php';
                return \Dokan\DemoContents\Exporter::instance();
                break;

            default:
                throw new Exception( sprintf( __( 'Undefined property %s::$%s', 'dokan-lite' ), self::class, $prop ) );
                break;
        }
    }

    /**
     * Define constants
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function define_constants() {
        define( 'DOKAN_DEMO_CONTENTS_DIR', DOKAN_INC_DIR . '/demo-contents' );
        define( 'DOKAN_DEMO_CONTENTS_STORE', WP_CONTENT_DIR . '/dokan-demo-contents' );
        define( 'DOKAN_DEMO_CONTENTS_DIR_PERMISSION', 0755 );
        define( 'DOKAN_DEMO_CONTENTS_FILE_PERMISSION', 0644 );
    }

    /**
     * Set filesystem
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    protected function set_fs() {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );

        if ( $this->fs instanceof \WP_Filesystem_Base ) {
            return $this->fs;
        }

        WP_Filesystem();

        global $wp_filesystem;

        $this->fs = $wp_filesystem;
    }

    /**
     * Remove demo contents root directory
     *
     * @since DOKAN_SINCE
     *
     * @return bool
     */
    public function remove_root_dir() {
        if ( $this->fs->is_dir( DOKAN_DEMO_CONTENTS_STORE ) ) {
            return $this->fs->rmdir( DOKAN_DEMO_CONTENTS_STORE, true );
        }

        return false;
    }

    /**
     * Create demo contents directories
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function make_dir() {
        $dirs = [
            DOKAN_DEMO_CONTENTS_STORE, DOKAN_DEMO_CONTENTS_STORE . '/images'
        ];

        foreach ( $dirs as $dir ) {
            $created = $this->fs->mkdir( $dir, DOKAN_DEMO_CONTENTS_DIR_PERMISSION );

            if ( ! $created ) {
                throw new Exception(
                    sprintf( __( 'File permission error. Cannot create "%s" directory', 'dokan-lite' ), $dir )
                );
            }
        }
    }

    /**
     * Create a file
     *
     * @since DOKAN_SINCE
     *
     * @param string $file
     *
     * @return bool
     */
    public function make_file( $file ) {
        if ( $this->fs->is_file( $file ) ) {
            $this->fs->delete( $file );
        }

        return $this->fs->touch( $file );
    }

    /**
     * Save contents to a file
     *
     * @since DOKAN_SINCE
     *
     * @param string $file
     * @param array $contents
     *
     * @return bool
     */
    public function save_contents( $file, $contents ) {
        $this->make_file( $file );
        return $this->fs->put_contents( $file, json_encode( $contents ), DOKAN_DEMO_CONTENTS_FILE_PERMISSION );
    }

    /**
     * Copy file
     *
     * @since DOKAN_SINCE
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy_file( $from, $to = '' ) {
        $to = DOKAN_DEMO_CONTENTS_STORE . $to;

        return $this->fs->copy( $from, $to, true, DOKAN_DEMO_CONTENTS_FILE_PERMISSION );
    }

    /**
     * Get file contents as json data
     *
     * @since DOKAN_SINCE
     *
     * @param string $file
     *
     * @return array
     */
    public function get_json_data( $file ) {
        $json = $this->fs->get_contents( $file );

        return $json ? json_decode( $json, true ) : [];
    }

    /**
     * Upload an image to WordPress
     *
     * @since DOKAN_SINCE
     *
     * @param string $image_name
     * @param int    $attached_to parent post id
     *
     * @return int attachment id
     */
    public function upload_image( $image_name, $attached_to ) {
        $image = DOKAN_DEMO_CONTENTS_STORE . '/images/' . $image_name;

        if ( file_exists( $image ) ) {
            $filetype = wp_check_filetype( $image );

            $file = [
                'name'     => $image_name,
                'type'     => $filetype['type'],
                'tmp_name' => $image,
                'size'     => filesize( $image ),
            ];

            $overrides      = [ 'test_form' => false ];
            $time           = null;
            $action         = 'dokan_demo_import';
            $uploaded_image = _wp_handle_upload( $file, $overrides, $time, $action );

            if ( isset( $uploaded_image['error'] ) ) {
                return 0;
            }

            $attachment = [
                'post_title'     => $image_name,
                'post_mime_type' => $uploaded_image['type'],
                'guid'           => $uploaded_image['url'],
                'context'        => 'import',
            ];

            $attachment_id = wp_insert_attachment( $attachment, $uploaded_image['file'], $attached_to );

            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata( $attachment_id, $uploaded_image['file'] )
            );

            return $attachment_id;
        } else {
            $attachment = get_page_by_title( $image_name, OBJECT, 'attachment' );

            if ( $attachment ) {
                return $attachment->ID;
            }
        }

        return 0;
    }
}
