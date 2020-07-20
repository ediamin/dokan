<?php echo isset( $statuses['wc-' . dokan_get_prop( $order, 'status' )] ) ? esc_html( $statuses['wc-' . dokan_get_prop( $order, 'status' )] ) : esc_html( dokan_get_prop( $order, 'status' ) ); ?>
