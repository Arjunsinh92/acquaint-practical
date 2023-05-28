<?php

/*Ajax call function start*/
function post_filter_ajaxurl() {
    echo '<script type="text/javascript">
            var ajaxurl = "' . admin_url('admin-ajax.php') . '";
          </script>';
    }
add_action('wp_head', 'post_filter_ajaxurl');

// ajax call for event page //
add_action('wp_ajax_load_posts_by_ajax','load_posts_by_ajax');
add_action('wp_ajax_nopriv_load_posts_by_ajax','load_posts_by_ajax');
function load_posts_by_ajax(){
    if($_POST['type'] != 0){
		if($_POST['type'] == 'featured'){
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => $_POST['type'],
				'operator' => 'IN', // or 'NOT IN' to exclude feature products
			);
			$args = array(
				'post_type' => 'product',
				'post_status' => 'publish',
				'order' => 'DESC',
				'tax_query' => $tax_query,
				'posts_per_page' => -1,
			); 
		}else{
			if($_POST['type'] == 'total_sales'){
				$args = array(
					'post_type' => 'product',
					'post_status' => 'publish',
					'order' => 'DESC',
					'posts_per_page' => -1,
					'meta_key' => $_POST['type'],
					'orderby' => 'meta_value_num',
					
				); 
			}

			if($_POST['type'] == 'all'){
				$args = array(
					'post_type' => 'product',
					'post_status' => 'publish',
					'order' => 'DESC',
					'posts_per_page' => -1,
				); 
			}
		}
	}
	$postslist = new WP_Query( $args );
		if($postslist->have_posts()){
			while($postslist->have_posts()){ $postslist->the_post(); ?>
				<li class="col-md-4 my-3 entry product">
					<a href="<?php echo get_the_permalink(); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
						<?php if(has_post_thumbnail()){
							echo get_the_post_thumbnail(get_the_ID(), 'custom-size');
						} ?>
						<h3 class="woocommerce-loop-product__title"><?php echo get_the_title(); ?></h3>
						<span class="price"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span><?php echo get_the_ID(); ?></bdi></span></span>
					</a>
					<a href="<?php echo get_the_permalink(); ?>" class="button wp-element-button add_to_cart_button" data-product_id="<?php echo get_the_ID(); ?>" rel="nofollow">Buy Now</a>
				</li>
			<?php } 
			wp_reset_postdata();
		} else {
			echo 'No products found';
		}
	wp_die();
}

/**
 * Skip cart page
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'skip_woo_cart' );
function skip_woo_cart() {
  return wc_get_checkout_url();
}

/*** Change Add to cart button text */
add_filter( 'woocommerce_product_single_add_to_cart_text', 'cw_btntext_cart' );
add_filter( 'woocommerce_product_add_to_cart_text', 'cw_btntext_cart' );
function cw_btntext_cart() {
   return __( 'Buy Now', 'woocommerce' );
}

/**
 * Custom check payment option
 */
add_action('init', 'init_agccheque_gateway_class');
function init_agccheque_gateway_class(){
    class WC_Gateway_agccheque extends WC_Payment_Gateway {
        public $domain;
        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'agccheque_payment';
            $this->id                 = 'agc';
            $this->icon               = apply_filters('woocommerce_agccheque_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'AGC', $this->domain );
            $this->method_description = __( 'Allows payments with AGC cheque gateway.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_agccheque', array( $this, 'thankyou_page' ) );

            // agcchequeer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable AGC Cheque Payment', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'AGC Cheque Payment', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-on-hold',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the agcchequeer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'agccheque' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }
        }

        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout with AGC Cheque Payment. ', $this->domain ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

			// The text for the note
			$note = __("AGC Awaiting Cheque Payment");	
			// Add the note
			$order->add_order_note( $note );

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
    }
}

/**
 * Adding custom payment gateway class
 */
add_filter( 'woocommerce_payment_gateways', 'add_agccheque_gateway_class' );
function add_agccheque_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_agccheque'; 
    return $methods;
}

/**
 * Display payment method to checkout page
 */
add_action('woocommerce_checkout_process', 'process_agccheque_payment');
function process_agccheque_payment(){
    if($_POST['payment_method'] != 'agccheque')
        return;
}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'agccheque_payment_update_order_meta' );
function agccheque_payment_update_order_meta( $order_id ) {
    if($_POST['payment_method'] != 'agccheque')
        return;
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'agccheque_checkout_field_display_admin_order_meta', 10, 1 );
function agccheque_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'agccheque')
        return;
}

/**
 * Update order note on order status change
 */
function add_custom_order_note_on_status_change( $order_id, $order ) {
    // Add your custom note content here.
    $note = 'Cheque Payment Completed';

    // Add the note to the order.
    $order->add_order_note( $note );
}
add_action( 'woocommerce_order_status_on-hold_to_completed', 'add_custom_order_note_on_status_change', 10, 4 );

/**
 * Display order status on the WooCommerce thank you page.
 */
function display_order_status_on_thankyou_page( $order_id ) {
    // Get the order object.
    $order = wc_get_order( $order_id );

    // Get the order status.
    $status = $order->get_status();

	if($status == 'on-hold'){
		echo "<b>Order Status:</b> AGC awaiting cheque payment";
	}
}
add_action( 'woocommerce_thankyou', 'display_order_status_on_thankyou_page', 2 );

/**
*        Disable Payment Gateway for a Specific User Role | WooCommerce
*/
add_filter( 'woocommerce_available_payment_gateways', 'njengah_paypal_disable_manager' );
function njengah_paypal_disable_manager( $available_gateways ) {

	if ( isset( $available_gateways['cod'] ) && current_user_can( 'manage_woocommerce' ) ) {
		unset( $available_gateways['cod'] );
	}
	if ( isset( $available_gateways['check'] ) && current_user_can( 'manage_woocommerce' ) ) {
		unset( $available_gateways['check'] );
	}
	if ( isset( $available_gateways['bacs'] ) && current_user_can( 'manage_woocommerce' ) ) {
		unset( $available_gateways['bacs'] );
	}
	if ( isset( $available_gateways['paypal'] ) && current_user_can( 'manage_woocommerce' ) ) {
		unset( $available_gateways['paypal'] );
	}
	if ( isset( $available_gateways['stripe'] ) && current_user_can( 'manage_woocommerce' ) ) {
		unset( $available_gateways['stripe'] );
	}
	return $available_gateways;
}
