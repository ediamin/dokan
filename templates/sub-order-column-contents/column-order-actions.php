<?php
$actions = array(
    'view' => array(
        'url'  => $order->get_view_order_url(),
        'name' => __( 'View', 'dokan-lite' ),
    ),
);

$actions = apply_filters( 'dokan_my_account_my_sub_orders_actions', $actions, $order );

foreach( $actions as $key => $action ) {
    ?>
        <a href="<?php echo esc_url( $action['url'] ); ?>" class="button <?php echo sanitize_html_class( $key ) ?>">
            <?php echo esc_html( $action['name'] ); ?>
        </a>
    <?php
}
