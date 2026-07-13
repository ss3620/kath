<?php

function add_interparcel_zone() {

  if (!class_exists('WC_IP')) {

    class WC_IP extends WC_Shipping_Method {


      public function __construct($instance_id = 0) {
        
        $this->id = 'interparcel';
        $this->instance_id = absint($instance_id);
        $this->title = 'Interparcel';
        $this->method_title = 'Interparcel';  
        $this->method_description   = __("Interparcel offers live domestic & international shipping quotes from multiple couriers.");
        $this->supports = array(
          'shipping-zones',
          'instance-settings',
          'instance-settings-modal'
        );
        
        $this->init();
        
        $this->debug = $this->get_option('debug');
        $this->region = $this->get_option('region');
        $this->auth_key = $this->get_option('auth_key');
        
      }



      function init() {
        $this->init_form_fields();
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this,'process_admin_options'));
      }



      function init_form_fields() {
        
        $this->instance_form_fields = array(
            'auth_key' => array(
                'title' => __('Interparcel Auth Key', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'css' => 'width: 400px;',
                'default' => ''
            ),
            'region' => array(
                'title' => __('Region', 'woocommerce'),
                'type' => 'select',
                'default' => '0',
                'css' => 'width: 400px;',
                'options' => array(
                    'uk' => __('Interparcel UK', 'woocommerce'),
                    'au' => __('Interparcel Australia', 'woocommerce'),
                    'nz' => __('Interparcel New Zealand', 'woocommerce')
                )
            ),    
            'debug' => array(
                'title' => __('Debug mode', 'woocommerce'),
                'type' => 'select',
                'default' => '0',
                'css' => 'width: 400px;',
                'options' => array(
                    '0' => __('Disabled', 'woocommerce'),
                    '1' => __('Enabled', 'woocommerce')
                )
            )
        );
        
      }
      
      

      public function calculate_shipping($package = array()) {
        
        global $woocommerce;
        
        $auth_key = $this->auth_key;
        $region = $this->region;
        
        $del_city = $package['destination']['city'];
        $del_postcode = $package['destination']['postcode'];
        $del_state = $package['destination']['state'];
        $del_country = $package['destination']['country'];      

        if (!$auth_key) {
          wc_add_notice('Please add your Interparcel Auth Key to obtain shipping rates', 'error');
          return null;
        }
        
        if (!$del_city || !$del_postcode) {
          return null;
        }
        
        $products = array();
        
        foreach ($package['contents'] as $item_id => $values) {
          $_product = $values['data'];

          for ($i = 0; $i < $values['quantity']; $i++) {
            $prod = array();
            $prod['id'] = $_product->get_id();
            $prod['name'] = $_product->get_name();
            $prod['sku'] = $_product->get_sku();           
            $prod['weight'] = $_product->get_weight();
            $prod['length'] = $_product->get_length();
            $prod['width']  = $_product->get_width();
            $prod['height'] = $_product->get_height();
            $products[] = $prod;
          }
        }

				$parcels = array();
				foreach ($products as $product) {
					$parcels[] = array(
            'id' => $product['id'], 'name' => $product['name'], 'sku' => $product['sku'], 'weight' => $product['weight'], 'length' => $product['length'], 'width' => $product['width'], 'height' => $product['height']
          );
				}

        $p = array(
					'shipment' => array(
						'delivery' => array(
							'city' => $del_city, 'state' => $del_state, 'postcode' => $del_postcode, 'country' => $del_country
						),
						'parcels' => $parcels
					)        
        );
				
				$payload = json_encode($p, JSON_NUMERIC_CHECK);

        $headers = array(
          'Content-Type:application/json', 'X-Interparcel-Auth:'.$auth_key
        );
        
        $ch  = curl_init('https://'.$region.'.interparcel.com/api/shipping-tools/woocommerce-quote');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);

        if ($this->debug == 1) {
          wc_add_notice('<strong>Interparcel Debug Output</strong><br>Request: '.$payload.'<br><br>Response: '.$response);
        }
        

        if (!$response) { return null; }
        
        $result = json_decode($response, true);
        
        if ($result['status'] != 0) { return null; }
                
        foreach ($result['services'] as $service) {
        
          $label = $service['service'];
          if ($service['transitTime']) { $label.= $service['transitTime']; }
          
          $rate = array();
          $rate['id'] = $service['id'];
          $rate['label'] = $label;
          $rate['cost'] = $service['price'];
          $rate['calc_tax'] = 'per_order';
          $rate['meta_data'] = array('service' => $service['service']);
          $this->add_rate($rate);
            
        }
        
      }

    }

  }

} 