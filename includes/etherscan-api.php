<?php
/**
 * Etherscan API Functions
 */

/* Service Url Mainnet */
function mcwallet_service_url_mainnet(){
  $service_url_mainnet = (get_option( 'mcwallet_use_testnet' ) === 'true') ? 'api-rinkeby.etherscan.io/api' : 'api.etherscan.io/api';
	return esc_url( $service_url_mainnet, 'https' );
}

function mcwallet_service_url_binance(){
  $service_url_mainnet = (get_option( 'mcwallet_use_testnet' ) === 'true') ? 'api-testnet.bscscan.com/api' : 'api.bscscan.com/api';
	return esc_url( $service_url_mainnet, 'https' );
}

function mcwallet_service_url_poligon(){
  // У полигона нет тестового апи (точнее оно не коректно работает) используем маиннет
  $service_url_mainnet = (get_option( 'mcwallet_use_testnet' ) === 'true') ? 'api.polygonscan.com/api' : 'api.polygonscan.com/api';
	return esc_url( $service_url_mainnet, 'https' );
}

/* Service Api Token */
function mcwallet_service_api_token($standart = 'erc20'){
	$service_api_token = 'X88AP9B52SENYPTR31W5SGRK5EGJZD2BJC';
  if ($standart == 'bep20') $service_api_token = 'WI4QEJSV19U3TF2H1DPQ2HR6712HW4MYKJ';
  if ($standart == 'erc20matic') $service_api_token = '8S2R45ZWG94HI7YK9RCXSK4VCASJ4XVA15';
	return $service_api_token;
}

/* Get Signature */
function mcwallet_get_signature( $signature = 'name' ){
	$signature_code = '0x06fdde03';
	if ( $signature == 'symbol' ) {
		$signature_code = '0x95d89b41';
	} elseif ( $signature == 'decimals' ) {
		$signature_code = '0x313ce567';
	}
	return $signature_code;
}

/* Get Args Url */
function mcwallet_get_args_url($standart = 'erc20'){
	$args = array(
		'module' => 'proxy',
		'action' => 'eth_call',
		'data'   => mcwallet_get_signature(),
		'apikey' => mcwallet_service_api_token($standart),
	);
	return $args;
}

/* Get Remote Url */
function mcwallet_get_remote_url( $result = 'name', $address = '', $standart = 'erc20' ){
	$args = mcwallet_get_args_url($standart);
	if ( $address ) {
		$args['to'] = $address;
	}
	if ( $result ) {
		$result = mcwallet_get_signature( $result );
		$args['data'] = $result;
	}

  $url = '';
	if ($standart === 'erc20') { $url = mcwallet_service_url_mainnet(); }
  if ($standart === 'bep20') { $url = mcwallet_service_url_binance(); }
  if ($standart === 'erc20matic') { $url = mcwallet_service_url_poligon(); }

	$swap_remote_url = add_query_arg(
		$args,
		$url
	);
	return $swap_remote_url;
}

/**
 * Is Address
 */
function mcwallet_is_address( $address = '', $standart = 'erc20' ){

	$url = mcwallet_get_remote_url( 'name', $address, $standart );
	$response = wp_remote_get( $url );
	if ( wp_remote_retrieve_response_code( $response ) === 200 ){
		$response_body = wp_remote_retrieve_body( $response );
		$body = json_decode( $response_body );
		if ( isset( $body->result ) ) {
			if ( $body->result !== '0x' ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Get Remote Result
 */
function mcwallet_get_remote_result( $result = 'name', $address, $standart = 'erc20' ){
	
	$url = mcwallet_get_remote_url( $result, $address, $standart );
	$response = wp_remote_get( $url );
	if ( wp_remote_retrieve_response_code( $response ) === 200 ){
		$response_body = wp_remote_retrieve_body( $response );
		$body = json_decode( $response_body );
		if ( isset( $body->result ) ) {
			return $body->result;
		}
	}
	return false;
}

/**
 * Hex To String
 *
 * @link http://www.jonasjohn.de/snippets/php/hex-string.htm
 */
function mcwallet_hex_to_string( $hex ) { 
	$string = '';
	$arr = explode("\n", trim( chunk_split( $hex, 2 ) ) );
	foreach( $arr as $h) {
		$string .= chr( hexdec( $h ) ); 
	}
	$string = preg_replace('/[^A-Za-z0-9]/', '', $string);
	return $string; 
}

/**
 * Convert Hex to Number
 *
 * @link http://php.net/manual/ru/function.hexdec.php#97172
 */
function mcwallet_hex_to_number( $hex ) {
	$hex = preg_replace( '/[^0-9A-Fa-f]/', '', $hex );
	$dec = hexdec( $hex );
	$max = pow(2, 4 * (strlen($hex) + (strlen($hex) % 2)));
	$_dec = $max - $dec;
	return $dec > $_dec ? -$_dec : $dec;
}
