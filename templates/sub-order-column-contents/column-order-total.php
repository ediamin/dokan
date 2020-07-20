<?php
$item_count = $order->get_item_count();
echo wp_kses_post(
    sprintf(
        _n( '%s for %s item', '%s for %s items', $item_count, 'dokan-lite' ),
        $order->get_formatted_order_total(),
        $item_count
    )
);
