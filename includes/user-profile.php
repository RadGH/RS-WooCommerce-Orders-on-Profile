<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Display the woocommerce orders table as a field on the user's profile
 *
 * @param $profileuser
 */
function woop_display_user_orders_field( $profileuser ) {
	if ( !current_user_can('manage_woocommerce') ) return;
	
	$args = array( 'customer_id' => $profileuser->ID );
	$orders = wc_get_orders($args);
	
	?>
	<h2 class="woop-orders-heading">WooCommerce Orders</h2>
	
	<div class="woop-order-table">
		<?php
			if ( empty($orders) ) {
				echo '<p><em>No orders have been placed by this user.</em></p>';
			}else{
				woop_display_orders_table( $orders );
			}
		?>
	</div>
	<?php
}
add_action( 'show_user_profile', 'woop_display_user_orders_field', 5 );
add_action( 'edit_user_profile', 'woop_display_user_orders_field', 5 );


/**
 * Display a table of orders
 *
 * @param $orders
 */
function woop_display_orders_table( $orders ) {
	
	// Header rows
	$headers = array(
		'id'              => array( 'title' => 'Order #',         'attrs' => 'data-sort-method="number"'  ),
		'status'          => array( 'title' => 'Status',          'attrs' => 'data-sort-method="text"'    ),
		'date'            => array( 'title' => 'Date Created',    'attrs' => 'data-sort-method="number"'  ),
		'total'           => array( 'title' => 'Total',           'attrs' => 'data-sort-method="number"'  ),
		'billing'         => array( 'title' => 'Bill To',         'attrs' => 'data-sort-method="text"'    ),
		'shipping'        => array( 'title' => 'Ship To',         'attrs' => 'data-sort-method="text"'    ),
		'shipping_method' => array( 'title' => 'Shipping Method', 'attrs' => 'data-sort-method="text"'    ),
		'products'        => array( 'title' => 'Products',        'attrs' => 'data-sort-method="number"'  ),
	);
	
	// Allow custom header rows via filtering
	$headers = apply_filters( 'woop/table_headers', $headers, $orders );
	
	
	// Table data rows
	$rows = array();
	
	// Loop through orders, creating a row for each
	foreach( $orders as $order ) {
		if ( !$order instanceof WC_Order ) continue;
		
		$order_id = $order->get_id();
		
		// Create a new row
		$row = array();
		
		// Loop through each header row and build data based on the key
		foreach( array_keys($headers) as $key ) {
			$data = array(
				'html' => '', // Displayed in a <td>
				'sort' => '', // Used in the td's attribute for sorting (if sortable)
			);
			
			$order_status = $order->get_status();
			$order_date_timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '';
			$total = $order->get_total();
			$billing = $order->get_formatted_billing_address();
			$shipping = $order->get_formatted_shipping_address();
			$product_count = $order->get_item_count();
			
			switch( $key ) {
				
				case 'id':
					$data['html'] = sprintf(
						'<a href="%s" target="_blank">#%s</a>',
						esc_attr(get_edit_post_link( $order_id )),
						esc_html( $order_id )
					);
					$data['sort'] = $order_id;
					break;
				
				case 'status':
					$data['html'] = '<mark class="order-status status-'. esc_attr($order_status ? $order_status : 'none') .'"><span>' . esc_html( wc_get_order_status_name( $order_status ) ) . '</span></mark>';
					$data['sort'] = $order_status;
					break;
				
				case 'date':
					$data['html'] = woop_get_order_date_html( $order );
					$data['sort'] = $order_date_timestamp;
					break;
				
				case 'total':
					$data['html'] = esc_html( get_woocommerce_currency_symbol() . $total );
					$data['sort'] = $total;
					break;
				
				case 'billing':
					$data['html'] = $billing;
					$data['sort'] = '';
					break;
				
				case 'shipping':
					$data['html'] = $shipping;
					$data['sort'] = '';
					break;
				
				case 'shipping_method':
					$data['html'] = woop_get_shipping_method_display( $order );
					$data['sort'] = esc_attr($order->get_shipping_method());
					break;
				
				case 'products':
					$data['html'] = woop_get_product_display( $order );
					$data['sort'] = $product_count;
					break;
				
			}
			
			// Allow filtering individual rows
			$data = apply_filters( 'woop/table_data', $data, $order, $key, $headers, $orders );
			
			$row[$key] = $data;
		}
		
		$rows[] = $row;
	}
	
	// Allow filtering all rows
	$rows = apply_filters( 'woop/table_data_rows', $rows, $headers, $orders );
	
	// Create the table
	?>
	<table class="woop-table woop-user-orders-table">
		<thead>
		<tr>
			<?php
			foreach( $headers as $k => $header ) {
				echo '<th class="woop-col woop-col-header woop-col-', esc_attr($k), '" ', $header['attrs'],'><span class="cell-inner">';
				echo esc_html( $header['title'] );
				echo '</span></th>';
			}
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach( $rows as $columns ) {
			?>
			<tr>
				<?php
				foreach( $columns as $key => $c ) {
					echo '<td class="woop-col woop-col-', esc_attr($k), '" data-sort="', esc_attr($c['sort']) ,'"><span class="cell-inner">';
					echo $c['html'];
					echo '</span></td>';
				}
				?>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php
}

/**
 * Gets an order date as an HTML time element using relative date (eg: 2 hours ago).
 *
 * Based on WC_Admin_List_Table_Orders::render_order_date_column()
 *
 * @param WC_Order $order
 *
 * @return string
 */
function woop_get_order_date_html( WC_Order $order ) {
	$order_timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '';
	
	if ( ! $order_timestamp ) {
		return '&ndash;';
	}
	
	// Check if the order was created within the last 24 hours, and not in the future.
	if ( $order_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $order_timestamp <= current_time( 'timestamp', true ) ) {
		$show_date = sprintf(
		/* translators: %s: human-readable time difference */
			_x( '%s ago', '%s = human-readable time difference', 'woocommerce' ),
			human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
		);
	} else {
		$show_date = $order->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'woocommerce' ) ) );
	}
	
	return sprintf(
		'<time datetime="%1$s" title="%2$s">%3$s</time>',
		esc_attr( $order->get_date_created()->date( 'c' ) ),
		esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
		esc_html( $show_date )
	);
}


/**
 * Returns a list of shipping methods
 *
 * @param WC_Order $order
 *
 * @return string
 */
function woop_get_shipping_method_display( WC_Order $order ) {
	$all_methods = $order->get_shipping_methods();
	$methods = array();
	
	if ( $all_methods ) foreach( $all_methods as $m ) {
		if ( !$m instanceof WC_Order_Item_Shipping ) continue;
		$methods[] = $m->get_name();
	}
	
	return $methods ? '<ul class="woop-list woop-shipping-methods">'. implode('', $methods) .'</ul>' : '';
}


/**
 * Returns a <ul> list of products contained in an order.
 * Returns false if no items
 *
 * @param WC_Order $order
 *
 * @return bool|string
 */
function woop_get_product_display( WC_Order $order ) {
	$items = $order->get_items();
	
	if ( $items ) {
		$li_elements = array();
		
		foreach( $items as $order_item_key => $order_item ) {
			if ( !($order_item instanceof WC_Order_Item_Product) ) continue;
			
			$name = $order_item->get_name();
			$qty = $order_item->get_quantity();
			$price = $order_item->get_total();
			
			$li_elements[] = '<li>' . $qty . ' &times; ' . esc_html($name) . ' <span class="price">('. get_woocommerce_currency_symbol() . esc_html($price) .')</span></li>';
		}
		
		if ( $li_elements ) {
			return '<ul class="woop-list woop-product-list">'. join(' ', $li_elements) .'</ul>';
		}
	}
	
	return false;
}