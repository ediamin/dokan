<?php
/**
 * Dokan Sub Order Templates
 *
 * @since 2.4
 *
 * @package dokan
 */

$table_columns = dokan_sub_orders_table_columns();
?>

<header>
    <h2><?php esc_html_e( 'Sub Orders', 'dokan-lite' ); ?></h2>
</header>

<div class="dokan-info">
    <strong><?php esc_html_e( 'Note:', 'dokan-lite' ); ?></strong>
    <?php esc_html_e( 'This order has products from multiple vendors. So we divided this order into multiple vendor orders.
    Each order will be handled by their respective vendor independently.', 'dokan-lite' ); ?>
</div>

<table class="shop_table my_account_orders table table-striped">

    <thead>
        <tr>
            <?php foreach( $table_columns as $column ): ?>
                <th class="<?php echo esc_attr( $column['class_name'] ); ?>">
                    <span class="nobr">
                        <?php echo esc_html( $column['title'] ); ?>
                    </span>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach( $sub_orders as $order_post ): ?>
            <?php $order = new WC_Order( $order_post->ID ); ?>
            <tr class="order">
                <?php foreach( $table_columns as $column ): ?>
                    <td>
                        <?php if ( isset( $column['content'] ) && is_callable( $column['content'] ) ): ?>
                            <?php call_user_func( $column['content'], $order ); ?>
                        <?php else: ?>
                            <?php
                                $defaults = array(
                                    'order' => $order,
                                );

                                $args = isset( $column['args'] ) && is_array( $column['args'] )
                                    ? wp_parse_args( $column['args'], $defaults )
                                    : $defaults;

                                // dokan_get_template_part(
                                //     'sub-order-column-contents/column',
                                //     $column['id'],
                                //     $args
                                // );

                                // dokan_get_template( "$name.php", $args, 'dokan/modules/delivery-time', trailingslashit( DOKAN_DELIVERY_TIME_TEMPLATE_PATH ) );

                                $default_path = isset( $args['template_path'] ) ? $args['template_path'] : '';
                                dokan_get_template( 'sub-order-column-contents/column-' . $column['id'] . '.php', $args, '', $default_path );
                            ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
