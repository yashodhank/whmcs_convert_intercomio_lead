<?php
/**
 * Example Hook Function
 *
 * Please refer to the documentation @ http://docs.whmcs.com/Hooks for more information
 * The code in this hook is commented out by default. Uncomment to use.
 *
 * @package    WHMCS Convert Intercom.io Lead to User
 * @author     Eric Baker <eric@ericbaker.me>
 * @copyright  GPLv2 (or later)
 * @license    http://www.fsf.org/
 * @version    1.0.0
 * @link       http://www.gowp.com/
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

// Set Intercom Access Token
$GLOBALS['access_token'] = '';
$GLOBALS['admin_username'] = '';

function curl_request($url, $method = 'GET', $post_fields = NULL) {
    // The framework was generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    // Set cURL options based on the method
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_POST, 1);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    }
    
    // Set the Headers to authorize the request and accept json
    $headers   = array();
    $headers[] = "Authorization: Bearer " . $GLOBALS['access_token'];
    $headers[] = "Accept: application/json";
    $headers[] = "Content-Type: application/json";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute the request
    $result = json_decode(curl_exec($ch));
    
    // Error handling
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // If no error, return the result.
        return $result;
    }
    
    // Close the request
    curl_close($ch);
}

function get_lead_user_id_by_email( $lead_email ) {
    // Craft the url we want - straight from https://developers.intercom.com/v2.0/reference#list-by-email
    $request_url = "https://api.intercom.io/contacts?email=" . urlencode( $lead_email );
    
    // Make the request
    $result = curl_request( $request_url );

    // Return the lead info
    return $result->contacts[0]->user_id;
}

function get_whmcs_client_email() {
	// Use the internal API to get the email of the client
	$command = 'GetClientsDetails';
	$postData = array(
	    'clientid' => $_SESSION['uid'],
	    'stats' => true,
	);

	$user_details = localAPI($command, $postData, $GLOBALS['admin_username']);
	
	return $user_details['client']['email'];
}

function convert_lead_to_user() {
	// Get the current users email
	$client_email = get_whmcs_client_email();

	// Craft the url we want - straight from https://developers.intercom.com/v2.0/reference#convert-a-lead
    $request_url = "https://api.intercom.io/contacts/convert";
    
    // Craft the POST Fields
    $post_fields = '{
        "contact":{
            "user_id":"' . get_lead_user_id_by_email( $client_email ) . '"},
        "user":{ "email":"' . $client_email . '" }
    }';
    
    // Make the Request
    $request = curl_request($request_url, 'POST', $post_fields);
}

add_hook( 'ClientAreaHomepage', 1, 'convert_lead_to_user' );