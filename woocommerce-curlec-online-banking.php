<?php
/* @wordpress-plugin
 * Plugin Name:          Woocommerce CurlecPay
 * Description:          Curlec FPX online banking and Creditcard payment gateway.
 * Version:              1.9.0
 * WC requires at least: 2.6
 * WC tested up to:      7.4.1
 * Author:               Curlec
 * Author URI:           https://www.curlec.com
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 */

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(in_array('woocommerce/woocommerce.php', $active_plugins)){
	add_filter('woocommerce_payment_gateways', 'curlec_online_banking');
	function curlec_online_banking( $gateways ){
		$gateways[] = 'WC_Curlec_Online_Banking';
		return $gateways; 
	}

	add_action('plugins_loaded', 'init_curlec_online_banking');
	function init_curlec_online_banking(){
		require 'class-woocommerce-curlec-online-banking.php';
	}
}