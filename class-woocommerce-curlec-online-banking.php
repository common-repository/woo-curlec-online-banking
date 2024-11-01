<?php 

class WC_Curlec_Online_Banking extends WC_Payment_Gateway{

	public function __construct(){
		$this->id = 'online_banking';
		$this->method_title = __('Curlec Payment','woocommerce-curlec-online-banking');
		$this->method_description = __( 'Curlec Online Banking enables real time payments from customer`s internet banking account.', 'woocommerce-curlec-online-banking' );
		$this->title = __('Curlec Payment','woocommerce-curlec-online-banking');
		$this->order_button_text = __( 'Proceed To Pay', 'woocommerce-curlec-online-banking' );
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
    	$this->curlecUrl = $this->get_option('curlecUrl');
		$this->merchantId = $this->get_option('merchantId');
		$this->employeeId = $this->get_option('employeeId');
    
        // Log is created always for main transaction points - debug option adds more logging points during transaction
		$this->debug = $this->get_option( 'debug' );
		$this->log   = new WC_Logger();

		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));	
        add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'callback_handler' ) );
		wp_register_style ('curlecpay', plugins_url ('curlecpay.css', __FILE__ ));
	}
	
	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce-curlec-online-banking' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable Curlec Payment', 'woocommerce-curlec-online-banking' ),
				'default' 		=> 'yes'
			),
			'title' => array(
				'title' 		=> __( 'Method Title', 'woocommerce-curlec-online-banking' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title', 'woocommerce-curlec-online-banking' ),
				'default'		=> __( 'Online Banking & Card Payments', 'woocommerce-curlec-online-banking' ),
				'desc_tip'		=> true,
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce-curlec-online-banking' ),
				'type' => 'textarea',
				'css' => 'width:500px;',
				'default' => 'FPX is available from all major banks in Malaysia and enables real time payments from your internet banking account (including Visa and Mastercard Credit Cards issued by your bank). You will be redirected to your selected bank website where you may choose your account or Credit Card to complete the payment.',
				'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-curlec-online-banking' ),
			),
          	'curlecUrl' => array(
				'title' => __( 'Url', 'woocommerce-curlec-online-banking' ),
				'type' => 'text',
				'default' => 'https://demo.curlec.com',
				'description' 	=> __( 'Provide the Curlec Server URL that you plan to use as your payment gateway. Please use the Production Server to start accepting live payments.', 'woocommerce-curlec-online-banking' ),
			),
          	'merchantId' => array(
				'title' => __( 'Merchant Id', 'woocommerce-curlec-online-banking' ),
				'type' => 'text',
				'default' => '',
				'description' 	=> __( 'please enter your curlec merchant id', 'woocommerce-curlec-online-banking' ),
			),
			'employeeId' => array(
				'title' => __( 'Admin Id', 'woocommerce-curlec-online-banking' ),
				'type' => 'text',
				'default' => '',
				'description' 	=> __( 'please enter your curlec employee id', 'woocommerce-curlec-online-banking' ),
			),
          	'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-curlec-online-banking' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-curlec-online-banking' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Curlec Payment events inside <code>%s</code>', 'woocommerce-curlec-online-banking' ), wc_get_log_file_path( $this->id ) ),
			),
		);
	}
	
	/**
	 * Get gateway icon.
	 * @return string
	 */
	public function get_icon() {
		$icon_html = '';
		$icon      = (array) $this->get_icon_image();

		foreach ( $icon as $i ) {
			$icon_html .= '<img src="https://demo.ag-icloudsolutions.com/curlec-bank-images/curleclogo.png" style="max-height:40px;max-width:90px" alt="Curlec Online Banking" />';
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Get PayPal images for a country.
	 *
	 * @param string $country Country code.
	 * @return array of image URLs
	 */
	protected function get_icon_image() {
		$icon = 'https://demo.ag-icloudsolutions.com/curlec-bank-images/fpx.png';
		return apply_filters( 'woocommerce_curlec_icon', $icon );
	}
	
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Curlec Payment Settings', 'woocommerce-curlec-online-banking' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
                    </div>
			</div>
			<div class="clear"></div>
		<?php
	}
  
    function build_url_query( $query ){
      $query_array = array();
      foreach( $query as $key => $key_value ){
          $query_array[] = rawurlencode( $key ) . '=' . rawurlencode( $key_value );
      }
      return implode( '&', $query_array );
	}
	
	public function get_url( $order ){
		$bankcode =  esc_attr($_POST['bankCode']) == null ? '' : esc_attr($_POST['bankCode']);
		$paymentMethod = esc_attr($_POST['paymentMethod']) == null ? '': esc_attr($_POST['paymentMethod']);
		$payMet = $paymentMethod == 1 ? 'FPX': 'CC';
		$businessModel = esc_attr($_POST['businessModel']) == null ? "" : esc_attr($_POST['businessModel']);
		//set callback url
		$homeUrl = get_home_url();
		$merchantUrl = $homeUrl.'/wc-api/wc_curlec_online_banking';
	    $merchantCallbackUrl = $homeUrl.'/wc-api/wc_curlec_online_banking';
		try {
			if((($bankcode !== "" && $payMet == 'FPX') || ($bankcode == "" && $payMet == 'CC')) && $paymentMethod !== ''){
				$instant_pay_params = array(
					'email' => sanitize_email($order->get_billing_email()),
					'description' => trim($payMet),
					'amount' => trim(number_format((float)$order->get_total(), 2, '.', '')),
					'merchantId' => trim($this->merchantId),
					'employeeId' => trim($this->employeeId),
					'bankCode' => $bankcode, 
					'businessModel' => trim($businessModel),					
					'paymentMethod' => trim($paymentMethod),
					'orderNo' => trim($order->get_order_number()),
					'notes' => trim($order->get_customer_note()),
					'merchantUrl' => trim($merchantUrl),
					'merchantCallbackUrl' => trim($merchantCallbackUrl),
					'method' => '03',                               
				);
				$inst_pay_args = $this->build_url_query( $instant_pay_params );     // generated URL
				$url = $this->curlecUrl . '/new-instant-pay?' .$inst_pay_args;
				//echo 'url' . $url;
				// Mark as pending (we're awaiting the payment)
				$order->update_status('pending', __( 'Awaiting payment', 'woocommerce-curlec-online-banking' ));
				// Reduce stock levels
				wc_reduce_stock_levels( $order_id );
				return $url;
			}
		}catch(Exception $e) {
			echo 'Message: ' .$e->getMessage();
		}	
	}
	
	public function checkExistingTransaction( $order_id ){
		$arr = array(
			'order_no' => urlencode( $order_id ),
			'merchantId' => urlencode( $this->merchantId ),
			'method' => '01'
		);
		$checkStatus = $this->checkStatus( $arr );
		$isNew = false;
		if($checkStatus['Status'][0] == '404'){
			if($checkStatus['Message'][0] == 'Transaction Not Found'){
				return $isNew = true;
			}
		} else if($checkStatus['Status'][0] == '201'){
			foreach ( $checkStatus['Response'][0] as $status ) {
				if( $status ['fpx_debitAuthCode'][0] == '00' ){
					wc_clear_notices();
					wc_add_notice( 'The transaction status for this order is already approved! Please wait for woocommerce to update the order status.', 'error' );
					return $isNew = false;
				} else if( $status ['fpx_debitAuthCode'][0] == '99' ){
					wc_clear_notices();
					wc_add_notice( 'The transaction status for this order is on hold! Please wait for woocommerce to update the order status.', 'error' );
					return $isNew = false;
				} else {
					return $isNew = true;
				}
			}
		}
	}
		
	public function process_payment( $order_id ) {
		global $woocommerce;
		$isNew = $this->checkExistingTransaction( $order_id );
		$order = wc_get_order( $order_id );
		$url = $this->get_url( $order );
		
		if(!$isNew){
			return array(
			'result' => 'failure',
			'redirect' => ''
			);
		}
		
		if($url !== null){
			$status = 'success';
		} else {
			$status = 'failure';
		}
    	// Return thankyou redirect
		return array(
			'result' => $status,
			'redirect' => $url
		);	
	}
  
	public function callback_handler() {
		global $woocommerce;
		@ob_clean();
		header( 'HTTP/1.1 200 OK' );
		
		wp_enqueue_style('curlecpay');
		
		$this->log->add( $this->id, 'CALLBACK URL IS STARTED ');
		filter_var_array($_REQUEST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		
		if ( ! isset( $_REQUEST['fpx_fpxTxnId'] ) || ! isset( $_REQUEST['fpx_sellerExOrderNo'] ) || ! isset( $_REQUEST['fpx_sellerOrderNo'] )  || ! isset( $_REQUEST['fpx_fpxTxnTime'] ) || 
			! isset( $_REQUEST['fpx_debitAuthCode'] )) {
			echo 'failed';
			$redirect_url = wc_get_checkout_url();
			wp_redirect( $redirect_url );
			//wp_die();
		}
		
		$order_id = absint( $_REQUEST['fpx_sellerOrderNo'] );
		$order = wc_get_order( $order_id );
		
		if ( ! $order ) {
			$this->log->add( $this->id, 'Invalid order ID received: ' . print_r( $order_id, true ) );
			die( "Order was lost during payment attempt - please inform merchant about WooCommerce Curlec Payment gateway problem." );
		}
		
		if ( $this->debug == 'yes' ) {
			$this->log->add( $this->id, 'Curlec Payment return handler started. ' . print_r( $_REQUEST, true ) );
			$this->log->add( $this->id, 'Order ' . var_export( $order, true ) );
		}
		
		$order_complete = $this->process_order_status( $order );
    	$this->log->add( $this->id, 'Order Complete ' . $order_complete );
		
		if ($order_complete === 'SUCCESS') {
			$this->log->add( $this->id, 'Order complete' );
			$redirect_url = $this->get_return_url( $order );
		} else if ($order_complete === 'PENDING'){
			$this->log->add( $this->id, 'Order complete' );
			$redirect_url = get_permalink( wc_get_page_id( 'myaccount' ) );
		} else {
			$tx_time = $_REQUEST['fpx_fpxTxnTime'];
			$fpxStatusCode = $_REQUEST['fpx_debitAuthCode'];
			switch ( $order_complete ) {
				case 'FAILED':
					$order->update_status( 'failed', __( 'Payment failed', 'woocommerce-curlec-online-banking' ) );
					$this->log->add( $this->id, 'Payment was declined by payment processor.' );
					$order->add_order_note( sprintf( __( 'transaction was failed to processed (Reference: %s, Timestamp: %s, StatusCode: %s)', 'woocommerce-curlec-online-banking' ), esc_attr($order_id), esc_attr($tx_time), esc_attr($fpxStatusCode)) );
					$redirect_url = wc_get_checkout_url();
				break;
				case 'CANCEL':
					$order->update_status( 'failed', __( 'Payment cancelled.', 'woocommerce-curlec-online-banking' ) );
					$this->log->add( $this->id, 'Payment was cancelled by user.' );
					$order->add_order_note( sprintf( __( 'transaction was cancelled to processed (Reference: %s, Timestamp: %s, StatusCode: %s)', 'woocommerce-curlec-online-banking' ), esc_attr($order_id), esc_attr($tx_time), esc_attr($fpxStatusCode)) );
					$redirect_url = wc_get_checkout_url();
				break;
				default:
					$order->update_status( 'failed', __( 'An error occurred while processing the payment response, please notify merchant!', 'woocommerce-curlec-online-banking' ) );
					$this->log->add( $this->id, 'An error occurred while processing the payment response.' );
					$order->add_order_note( sprintf( __( 'transaction was failed to processed (Reference: %s, Timestamp: %s, StatusCode: %s)', 'woocommerce-curlec-online-banking' ), esc_attr($order_id), esc_attr($tx_time), esc_attr($fpxStatusCode)) );
					$redirect_url = wc_get_checkout_url();
				break;
			}
		}
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			// The return for GET method
			$this->log->add( $this->id, 'Redirected to ' . $redirect_url );
			if($order_complete == "PENDING"){
				
			?>
				<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ).'/curlecpay.css' ?>">
				<div class="popup center">
					<div class="icon">
						<i class="fa fa-check"></i>
					</div>
					<div class="title">
						Order Received!
					</div>
					<div class="description">
						<p>Your order is pending approval. It will only be confirmed after approval has been received and payment has been made.</p>
						<p>To check on the status of this order, go to <b>My Account</b>, then click <b>Orders</b>.</p>
					</div>
					<div class="dismiss-btn">
						<button id="dismiss-popup-btn">Close</button>
					</div>
				</div>
				<script> 
					document.getElementsByClassName("popup")[0].classList.add("active");
					document.getElementById("dismiss-popup-btn").addEventListener("click",function(){
						document.getElementsByClassName("popup")[0].classList.remove("active");
						location.href = "<?php echo $redirect_url; ?>";
					});
				</script>
			<?php	
			} else {
				wp_redirect( $redirect_url );
			}
		} 
		
		exit;
	}
  
	public function process_order_status( $order ) {
	
		$result = $this->verify_curlec_response( $_REQUEST );
		$order_id = absint( $_REQUEST['fpx_sellerOrderNo'] );
		$tx_time = $_REQUEST['fpx_fpxTxnTime'];
		$fpxStatusCode = $_REQUEST['fpx_debitAuthCode'];
    
		if ($result === 'SUCCESS' ) {
			// Payment complete
			$order->payment_complete( $order_id );
			// Add order note
			$order->add_order_note( sprintf( __( 'Online Banking was successfully processed by FPX (Reference: %s, Timestamp: %s)', 'woocommerce-curlec-online-banking' ), esc_attr($order_id), esc_attr($tx_time), esc_attr($fpxStatusCode)) );
			// Remove cart
			WC()->cart->empty_cart();
			$this->log->add( $this->id, 'empty cart ');
		} else if($result === 'PENDING'){
			$order->update_status( 'pending', __( 'Payment On Hold', 'woocommerce-curlec-online-banking' ) );
			$this->log->add( $this->id, 'Payment was on hold by payment processor.' );
			$order->add_order_note( sprintf( __( 'transaction is on hold (Reference: %s, Timestamp: %s, StatusCode: %s)', 'woocommerce-curlec-online-banking' ), esc_attr($order_id), esc_attr($tx_time), esc_attr($fpxStatusCode)) );
			// Remove cart
			WC()->cart->empty_cart();
			$this->log->add( $this->id, 'empty cart ');
		}
		return $result;
	}
	
	function checkStatus( $array ) {
		
		//extract data from the post
		//set POST variables
		$url = $this->get_option('curlecUrl') . '/curlec-services?';
		$fields = $array;

		//url-ify the data for the POST
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string, '&');

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

		//execute post
		$result = curl_exec($ch);
		$result = json_decode($result, true);

		//close connection
		curl_close($ch);
		return $result;
	}
	
	public function verify_curlec_response( array $data ) {
		$statusCode = $data['fpx_debitAuthCode'];
		$arr = array(
			'order_no' => urlencode( $data['fpx_sellerOrderNo'] ),
			'ex_order_no' => urldecode( $data['fpx_sellerExOrderNo'] ),
			'merchantId' => urlencode( $this->get_option('merchantId') ),
			'method' => '01'
		);
		$checkStatus = $this->checkStatus( $arr );
		$this->log->add( $this->id, 'CHECK STATUS: ' . print_r( json_encode($checkStatus), true ) );
		foreach ( $checkStatus['Response'][0] as $cs ) {
			$this->log->add( $this->id, 'STATUSCODE: ' . print_r( json_encode($cs['fpx_debitAuthCode'][0]), true ) );
			if( $cs['fpx_debitAuthCode'][0] == '00' ){
				$statusCode = '00';
				break;
			} else {
				$statusCode = $cs['fpx_debitAuthCode'][0];
			}
		}
		switch ( $statusCode ) {
			case '00':
			    $status = 'SUCCESS';
			break;
			case '99':
			    $status = 'PENDING';
			break;
			case '80':
			case 'BC':
			case '1C':
			case '1F':
			case '1I':
			case '1L':
			    $status = 'CANCEL';
			break;
			default:
			    $status = 'FAILED';
			break;
		}
		return $status;
	}
  
    public function payment_fields(){
	    ?>
		<p id="description"><?php echo $this->description; ?></p>
		<fieldset style="padding-left:20px">
			<div id="fpxInstantpay" style="display:block">
				<input type="radio" name="paymentMethod" value="1" id="ip_online_bank" onclick="handleClick(this);" style="color:#b20000;font-size:15px;" checked>
				<label for="online_bank_md">Online Banking <img src="https://demo.ag-icloudsolutions.com/curlec-bank-images/fpx.png" style="margin-left:5px;max-height:40px;max-width:70px" alt="Curlec Online Banking" /></label>
			</div>
			<div id="instantPayFPX" style="display:block;padding-left:20px">
					<label for="businessModel" class="rdBtn" style="width:100%">
						<input type="radio" id="b2c_instantpay" name="businessModel" value="B2C" onclick="getIPBankList(this)" checked>
							Personal Account
					</label>
					<label for="businessModel" class="rdBtn" style="width:100%">
						<input type="radio" id="b2b_instantpay" name="businessModel" value="B2B1" onclick="getIPBankList(this)">
							Business Account
					</label>
					<label for="online_bank_ip" style="width:50%">
						<select id="bankCode" name="bankCode" class="selectBank" required>
						</select>
					</label>
			</div>
			<div id="cardInstantPay" style="display:none">
				<input type="radio" name="paymentMethod" value="2" id="ip_creditCard" onclick="handleClick(this);" style="color:#b20000;font-size:15px;">
				<label for="creditCard">Credit/Debit Card <img src="https://demo.ag-icloudsolutions.com/curlec-bank-images/visa.png" style="margin-left:5px;max-height:50px;max-width:80px" alt="Curlec Online Banking" /></label> 
			</div> 
		</fieldset>

		<style type="text/css">
			.selectBank{
				width: 100%;
				margin: 8px 0;
				display: inline-block;
				border: 1px solid #ccc;
				border-radius: 4px;
				box-sizing: border-box;
			}
		</style>
		<script type="text/javascript">
		    var checked = '1';
			var merchantId = '<?php echo $this->merchantId ;?>';
			var employeeId = '<?php echo $this->employeeId ;?>';
			getIPBankList("B2C");
			getIPData();
			
			function handleClick(paymentMethod) {
				checked = paymentMethod.value;
				if(checked == "1"){
					document.getElementById("bankCode").style.display = "block";
					document.getElementById("instantPayFPX").style.display = "block";
				} else {
					document.getElementById("instantPayFPX").style.display = "none";
					document.getElementById("bankCode").value = "";
				}
			}
			
			function removeRepeat(comboBox) {
				while (comboBox.options.length > 0) {                
					comboBox.remove(0);
				}
			}
			
			function getIPBankList(businessModel){
				var method = "01";               
				var msgToken = "01";
				var busModel = businessModel.value;
				if(busModel == "B2B1"){
					msgToken =  "02";
				}
				var xmlhttp = new XMLHttpRequest();
				var curlecUrl = '<?php echo $this->curlecUrl ;?>';
				var url = curlecUrl + "/curlec-services/banks?method=" + method + "&msgToken=" + msgToken;
				var bankList = [];
				var selectBox = document.getElementById("bankCode");
				xmlhttp.onreadystatechange = function() {
					if (this.readyState === 4) {
						if(this.status === 200){
							var myArr = JSON.parse(this.responseText);
							if(myArr.Status == 201){ 
								bankList = myArr.Response[0];
								removeRepeat(selectBox);
								selectBox.add(new Option("Please Select The Bank", ""));
								for(var i = 0, l = bankList.length; i < l; i++){
									var option = bankList[i];
									selectBox.add(new Option(option.display_name, option.code));
								}
							}
						}
					}
				};
				xmlhttp.open("POST", url, true);
				xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xmlhttp.send();
			}
			
			function getIPData(){
				var xmlhttp = new XMLHttpRequest();
				var curlecUrl = '<?php echo $this->curlecUrl ;?>';
				var url = curlecUrl + "/curlec-services/merchant?merchantId=" + merchantId + "&employeeId=" + employeeId;
				xmlhttp.onreadystatechange = function() {
					if(this.readyState === 4){
						if (this.status === 200) {
							var dt = JSON.parse(this.responseText);
							var isCreditCard = dt.Response[0]['credit_card_enable'][0];
							var creditCardInstantPay = dt.Response[0]['credit_card_instant_pay'][0];
							if (isCreditCard && creditCardInstantPay){
								document.getElementById("cardInstantPay").style.display = "block";
							}
						}
					}
				};
				xmlhttp.open("POST", url, true);
				xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xmlhttp.send();	
			}
   	
		</script>
		
		<?php
	}
  
}