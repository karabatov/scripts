<?php
 
/*
 * 2011, Yuri Karabatov for Andrew Murray
 */

require('../../../wp-blog-header.php');
require_once('../../../wp-includes/registration.php');
require_once('../../../wp-admin/includes/user.php');
include("phpcreditcard.php");
include('EmailAddressValidator.php');

// get access to database object
global $wpdb;

require_once('KLogger.php');
$logging_level = KLogger::DEBUG;
$logging_filename = dirname(__FILE__)."/authnet_log.php";
$log = new KLogger ($logging_filename , $logging_level);

/*****************
 variables needed
 action
 ID (subscription ID)
 claim
 amount (optional)
 postname (optional)
 cc_number
 cc_expdate
 cc_v
 user_id
 *****************/

global $subscription, $claim, $postname, $amount, $article_id, $recurring;

// we have to attempt to populate these before possibly calling rollbackReturn
if (isset($_POST['amount'])) $amount = number_format(floatval($_POST['amount']), 2, '.', '');
if (isset($_POST['article_id'])) $article_id = $wpdb->escape($_POST['article_id']);
if (isset($_POST['postname'])) $postname = $wpdb->escape($_POST['postname']);
if (isset($_POST['subscription'])) $subscription = $_POST['subscription'];
if (isset($_POST['recurring'])) $recurring = $wpdb->escape($_POST['recurring']);

// validate input
$inputValid = true;
// first make sure we have a valid action
if (isset($_POST['action'])) $action = $wpdb->escape($_POST['action']);
else {
	die('Invalid action request');
	exit;
}

// verify valid input
if (isset($_POST['creditCardNumber'])) {
	//$log->LogDebug ("Credit Card number: ...".substr($_POST['creditCardNumber'], -1, 4));
	$log->LogInfo ("Credit Card type: ".$_POST['cc_name']);
	if (!checkCreditCard ($_POST['creditCardNumber'], $_POST['cc_name'], $ccerror, $ccerrortext)) {
		$inputValid = false;
		$log->LogError ("Credit Card error (".$ccerror.") ".$ccerrortext);
		rollbackReturn($ccerrortext);
	} else {
		$cc_number = $_POST['creditCardNumber'];
		$lastFourDigitsOfCreditCard = substr($cc_number, strlen($cc_number)-4, strlen($cc_number));;
		$log->LogDebug ("Credit card last four digits: ".$lastFourDigitsOfCreditCard);
	}
}

// verify expdate looks right
$cc_expdate = $_POST['exp_year']."-".$_POST['exp_month'];
if ($inputValid && isset($cc_expdate)) {
	$inputValid = (count(str_split($cc_expdate)) == 7) ? true:false;
  	$log->LogDebug ("Credit card expiration date: ".$cc_expdate);
	if (!$inputValid) {
		$log->LogError ("Credit Card expdate error: ".$cc_expdate);
		rollbackReturn("Invalid credit card expiration date");
	}
}

// verify that a phone was provided if required:
if (get_option('authnet_require_phone')) {
	if (!isset($_POST['billingPhone']) || ($_POST['billingPhone'] == '')) {
  		$log->LogInfo ("No phone was provided, but is required for this site");
		rollbackReturn("Please provide a valid phone number");
	}
}

// verify that address values are provided
if (!isset($_POST['billingFirstName']) || $_POST['billingFirstName'] == '' ||
	!isset($_POST['billingLastName']) || $_POST['billingLastName'] == '' ||
	!isset($_POST['billingAddress']) || $_POST['billingAddress'] == '' ||
	!isset($_POST['billingCity']) || $_POST['billingCity'] == '' ||
	!isset($_POST['billingState']) || $_POST['billingState'] == '' ||
	!isset($_POST['billingZip']) || $_POST['billingZip'] == '' ||
	!isset($_POST['billingCountry']) || $_POST['billingCountry'] == '') {
	$log->LogInfo ("Some required address value wasn't provided");
	rollbackReturn("You must provide all required values. Please update your details below and submit again.");
}

// verify CCV
if (isset($_POST['CreditCardCCV'])) $cc_v = $_POST['CreditCardCCV'];
//$log->LogDebug ("Credit card CCV: ".$cc_v);

// validate claim
$claim_valid = false;
if (isset($_POST['subscription']) && isset($_POST['claim'])) {
	$claim = $wpdb->escape($_POST['claim']);

	// first case is an integer subscription_id
	if (is_numeric($subscription)) {
		$subscription = intval($subscription);
		$subscription_id = $subscription;
		// validate the subscription claim
		$claim_valid = createCheckoutClaim($subscription, get_option('authnet_securityseed')) == $claim;
		if ($claim_valid) $subscription_details = $wpdb->get_row("SELECT * FROM $authnet_subscription_table_name WHERE ID = $subscription");

	// second case is a donation
	} else if (isset($_POST['amount']) && isset($_POST['postname']) && $_POST['postname']=='donation') {
		// prepare values needed for form to render
		$wpdb->escape($subscription);
		$subscription_id = 1;
		// validate the donation purchase claim
		$claim_valid =  md5($_POST['amount'].$_POST['postname']) == $_POST['claim'];
		if ($claim_valid) $subscription_details = $wpdb->get_row("SELECT * FROM $authnet_subscription_table_name WHERE ID = 1");

	// third case is a single post purchase
	} else if (isset($_POST['amount']) && isset($_POST['postname'])) {
		$wpdb->escape($subscription);
		$subscription_id = 1;
		// validate the single post purchase claim
		$claim_valid = createCheckoutClaim($_POST['amount'].$_POST['postname'], get_option('authnet_securityseed')) == $claim;
		if ($claim_valid) $subscription_details = $wpdb->get_row("SELECT * FROM $authnet_subscription_table_name WHERE ID = 1");
	}

	// finally, if I have a valid claim then display the checkout form, else error message
	if (!$claim_valid) {
		$log->LogError ("Invalid Claim");
		$inputValid = false;
	}
}

// finally, if input is invalid then return to checkout page for new details
if (!$inputValid) {
	$log->LogInfo ("Redirecting to checkout page due to error with credit card details");
	$message = "Credit+card+details+are+incorrect";
	rollbackReturn($message);
}

// load subscription object
// NOTE: it was loaded above, but may require modification in event of a single post purchase
if ($subscription == 'single' && $postname != 'donation') {
	$subscription_details->initialAmount = $amount;
	$subscription_details->initialDescription = $postname;
} else if ($subscription == 'single' && $postname == 'donation') {
	if ($recurring) {
		// change processing parameters to recurring
		$subscription_details->processSinglePayment = 0;
		$subscription_details->processRecurringPayment = 1;
		// manually set recurring values
		$subscription_details->name = "Recurring donation";
		$subscription_details->recurringIntervalLength = '1';
		$subscription_details->recurringIntervalUnit = 'months';
		$subscription_details->recurringTotalOccurrences = '9999';
		$subscription_details->recurringTrialOccurrences = '0';
		$subscription_details->recurringAmount = $amount;
		$subscription_details->recurringTrialAmount = '0';
	} else {
		$subscription_details->initialAmount = $amount;
		$subscription_details->initialDescription = $postname;
	}
}

// validate email address
$validator = new EmailAddressValidator;
if (isset($_POST['email']) && $validator->check_email_address($wpdb->escape($_POST['email']))) $email = $wpdb->escape($_POST['email']);
else {
	$message = "Invalid email address";
	rollbackReturn($message);
}

// create WordPress user record or load existing user record
global $newuser;
$newuser = false;
$user_id = email_exists ($email);
if ( !$user_id ) {
	// set global newuser flag
	$newuser = true;
	// sort out password
	if (isset($_POST['desiredPassword']) && $_POST['desiredPassword'] != '') $password = $wpdb->escape($_POST['desiredPassword']);
	else $password = wp_generate_password( 8, false );
	// sort out username
	$i=1;
	if (isset($_POST['desiredUsername']) && $_POST['desiredUsername'] != '') {
		$username = $_POST['desiredUsername'];
	} else $username = $email;
	// make sure username is unique
	while (username_exists($username))
		$username = $username . $i++;
	$log->LogInfo ("Create user with username: [".$username."] password: [".$password."] email: [".$email."]");
	$user_id = wp_create_user($username, $password, $email );
	update_user_meta( $user_id, 'first_name', $_POST['billingFirstName']);
	update_user_meta( $user_id, 'last_name', $_POST['billingLastName']);
} else $log->LogInfo ("Found user with user_id: ".$user_id);

if (!is_numeric($user_id)) {
	$message = "No valid user_id could be found/created.";
	$log->LogError ("user_id contains: ".$user_id->get_error_message());
	rollbackReturn($message);
}

// load authnet global settings
global $authnet_transactionkey, $authnet_apikey, $authnet_aimposturl;
$authnet_transactionkey = get_option('authnet_transactionkey');
$authnet_apikey = get_option('authnet_apikey');
$authnet_aimposturl = get_option('authnet_aimposturl');
$authnet_arbhost = get_option('authnet_arbhost');
$authnet_arbpath = get_option('authnet_arbpath');

// extract personal/billing details from post
$billingFirstName = $wpdb->escape($_POST['billingFirstName']);
$billingLastName = $wpdb->escape($_POST['billingLastName']);
$billingAddress = $wpdb->escape($_POST['billingAddress']);
$billingCity = $wpdb->escape($_POST['billingCity']);
$billingState = $wpdb->escape($_POST['billingState']);
$billingZip = $wpdb->escape($_POST['billingZip']);
$billingPhone = $wpdb->escape($_POST['billingPhone']);
$billingCountry = $wpdb->escape($_POST['billingCountry']);

// extract subscription notes (if any)
$subscriptionNotes = (isset($_POST['subscriptionNotes'])) ? $wpdb->escape($_POST['subscriptionNotes']):'';
$subscriptionNotes .= compileSurvey();

// execute authorize.net shipping AIM transaction
$orderDetails = new AuthNetOrder;
$orderDetails->x_card_num = $cc_number;
$orderDetails->x_exp_date = $cc_expdate;
$orderDetails->x_card_code = $cc_v;
$orderDetails->x_invoice_num = "ORDER-".$user_id;
$orderDetails->x_first_name = $billingFirstName;
$orderDetails->x_last_name = $billingLastName;
$orderDetails->x_address = $billingAddress;
$orderDetails->x_city = $billingCity;
$orderDetails->x_state = $billingState;
$orderDetails->x_zip = $billingZip;
$orderDetails->x_phone = $billingPhone;
$orderDetails->x_country = $billingCountry;
$orderDetails->x_email = $email;

// if processSinglePayment is true then processAIM
if ($subscription_details->processSinglePayment == 1) {
	$orderDetails->x_amount = $subscription_details->initialAmount;
	$orderDetails->x_description = $subscription_details->initialDescription;
	$AIMProcessingResults = processAIM($orderDetails);
	if ($AIMProcessingResults['resp_code'] != 1) {
		$log->LogError ("Credit Card transaction error with authorize.net (".$AIMProcessingResults['resp_code'].") Reason: ".$AIMProcessingResults['resp_reason_code']." Response text - ".$AIMProcessingResults['resp_reason_text']);
		$message = $AIMProcessingResults['resp_reason_text'];
		rollbackReturn($message, $user_id);
	}
}

// create user subscription record
$user_sub_insert = "INSERT INTO " . $authnet_user_subscription_table_name . " SET user_id = $user_id,
		subscription_id = $subscription_id,
		billingFirstName = '$billingFirstName',
		billingLastName = '$billingLastName',
		billingCompany = '$billingCompany',
		billingAddress = '$billingAddress',
		billingCity = '$billingCity',
		billingState = '$billingState',
		billingZip = '$billingZip',
		billingCountry = '$billingCountry',
		billingPhone = '$billingPhone',
		emailAddress = '$email',
		subscriptionNotes = '$subscriptionNotes',
		lastFourDigitsOfCreditCard = '$lastFourDigitsOfCreditCard'";
$results = $wpdb->query($user_sub_insert);
if ($results === false) {
	$log->LogError ("wpdb query failed for user_subscription: [".$user_sub_insert."]");
	$message = "Unable to complete transaction. Please contact support.";
	rollbackReturn($message, $user_id, $AIMProcessingResults['resp_transaction_id']);
}
$user_subscription_id = $wpdb->insert_id;
$log->LogDebug ("Create user_subscription record with user_subscription_id: [".$user_subscription_id."]");

// create payment record (only if this is a single payment)
if ($subscription_details->processSinglePayment == 1) {
	$user_payment_insert = "INSERT INTO " . $authnet_payment_table_name . " SET user_subscription_id = $user_subscription_id,
			xAuthCode = '{$AIMProcessingResults['resp_auth_code']}',
			xTransId= '{$AIMProcessingResults['resp_transaction_id']}',
			xAmount = {$AIMProcessingResults['resp_amount']},
			xMethod = '{$AIMProcessingResults['resp_method']}',
			xType = '{$AIMProcessingResults['resp_transaction_type']}',
			paymentDate = '".date('Y-m-d H:i:s')."',
			fullAuthorizeNetResponse = '".$wpdb->escape(serialize($AIMProcessingResults))."'";
	$results = $wpdb->query($user_payment_insert);
	if ($results === false) {
		$log->LogError ("wpdb query failed for payment record: [".$user_payment_insert."]");
		$message = "Unable to complete transaction. Please contact support.";
		rollbackReturn($message, $user_id, $AIMProcessingResults['resp_transaction_id'], $user_subscription_id);
	}
}

// if processRecurringPayment is true then processARB
if ($subscription_details->processRecurringPayment == 1) {
	// execute authorize.net recurring billing transaction
	$recurringTransactionResults = processARB ($orderDetails, $authnet_arbhost, $authnet_arbpath, $subscription_details);
	if (!$recurringTransactionResults) {
		$log->LogError ("Failed to create ARB transaction");
		$message = "Order processing failed. Please contact support.";
		rollbackReturn($message, $user_id, $AIMProcessingResults['resp_transaction_id'], $user_subscription_id);
	}
	// break out results
	list ($refId, $resultCode, $code, $text, $xSubscriptionId) = $recurringTransactionResults;
	if ($resultCode == "Error") {
		$log->LogError ("Failed to create ARB transaction (".$code.") ".$text);
		$message = $text;
		rollbackReturn($message, $user_id, $AIMProcessingResults['resp_transaction_id'], $user_subscription_id);
	}
	// update user subscription record to have subscription ID (for cancellations/updates)
	$user_sub_update = "UPDATE " . $authnet_user_subscription_table_name . " SET xSubscriptionId = $xSubscriptionId,
			startDate = '".date('Y-m-d')."',
			isRecurring = 1
			WHERE ID = $user_subscription_id";
	$results = $wpdb->query($user_sub_update);
}

// if memberwing callback present, create/update member account in memberwing
$mw_payment_notify_url = get_option('authnet_memberwingcallback');
if ($mw_payment_notify_url && $mw_payment_notify_url != '') {
	// determine memberwing event type
	$event_type = "authnet";
	if ($subscription_details->processSinglePayment == 1) $event_type .= "_single";
	if ($subscription_details->processRecurringPayment == 1) $event_type .= "_recurring";
	// choose a transaction_id
	$txn_id = (isset($AIMProcessingResults['resp_auth_code'])) ? $AIMProcessingResults['resp_auth_code']:$xSubscriptionId;

	$mw_payment_values = array(
							'event_type' => $event_type,
							'first_name' => $billingFirstName,
							'last_name' => $billingLastName,
							'email' => $email,
							'payment_amount' => ($subscription_details->processRecurringPayment == 1) ? $subscription_details->recurringAmount:$subscription_details->initialAmount,
							'xSubscriptionId' => (isset($xSubscriptionId)) ? $xSubscriptionId:'',
							'xAuthCode' => (isset($AIMProcessingResults['resp_auth_code'])) ? $AIMProcessingResults['resp_auth_code']:'',
							'txn_id' => $txn_id,
							'payment_currency' => 'USD',
							'desired_username' => $username,
							'desired_password' => $password,
							'verify_hash' => createCheckoutClaim (substr(md5($email),3,8), get_option('authnet_securityseed')));
	// update item_name based on type of call
	if ($subscription == 'single') {
		$mw_payment_values['item_name'] = $item_name = "Item: $postname (id:$article_id)";
	} else {
		$mw_payment_values['item_name'] = $subscription_details->name;
	}

	$get_string = "";
	foreach( $mw_payment_values as $key => $value ) { $get_string .= "$key=" . urlencode( $value ) . "&"; }
	$get_string = rtrim( $get_string, "& " );
	// This call should look like: http://localhost/wordpress-3.1/wp-content/plugins/membership-site-memberwing/extensions/authorize.net/ipn.php?event_type=subscr_signup&item_name=choicesoftwarezone_com%20Gold%20Membership&customer_first_name=John&customer_last_name=Doe&customer_email=dwmaillist@gmail.com&payment_amount=0&payment_currency=USD&desired_username=dwmaillist@gmail.com&desired_password=c07dfbea&verify_hash=c077a44b9afedacc1ac1ceff4f90643c
	$mw_payment_notify_call = $mw_payment_notify_url."?".$get_string;
	$log->LogInfo ("Calling to setup memberwing account using ".$mw_payment_notify_call);
	// use cURL to send notification to memberwing
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $mw_payment_notify_call); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_exec($ch);
	curl_close($ch);
}

// if memberwing callback present, create/update member account in memberwing
$authnet_processwishlist = get_option('authnet_processwishlist');
if ($authnet_processwishlist && $authnet_processwishlist == 'on') {
	$log->LogDebug("************ BEGIN PROCESSING ************");

	// get wishlist member level details
	$levels = WLMAPI::GetOption("wpm_levels");
	$wishlistLevelName = $levels[$subscription_details->wishlistLevel];
	
	// Expectation: input is POST request
	// extract and condition values
	$product_sku = $subscription_details->wishlistLevel;
	$log->LogDebug("product_sku=".$product_sku);
	$transaction_id = (isset($AIMProcessingResults['resp_auth_code'])) ? $AIMProcessingResults['resp_auth_code']:'';
	$subscription_id = (isset($xSubscriptionId)) ? $xSubscriptionId:'';

	// build out generic URL and secret key values
	// the post URL
	$genericurl = get_bloginfo ('wpurl') . "/index.php/register/";
	$postURL = $genericurl . WLMAPI::GetOption("genericthankyou");
	$log->LogDebug("postURL = ".$postURL);

	// the Secret Key
	global $secretKey;
	$secretKey = WLMAPI::GetOption("genericsecret");

	// begin processing
	$log->LogDebug("preprocessing_success=".$processing_success);
	$log->LogDebug("***** process wishlist membership *****");
	// create WordPress user record or load existing user record
	$user_id = email_exists ($email);
	$data = array ();
	if ( $user_id ) {
		$log->LogInfo ("Found user with user_id: ".$user_id);
		// add user levels to existing user
		$data['transaction_id'] = ($subscription_id == null) ? $transaction_id:$subscription_id;
		$processing_success = addUserLevels ($user_id, array($product_sku), $data['transaction_id']);
	} else {
		// prepare the data
		$data['cmd'] = 'CREATE';
		$data['transaction_id'] = ($subscription_id == null) ? $transaction_id:$subscription_id;
		$data['lastname'] = $billingLastName;
		$data['firstname'] = $billingFirstName;
		$data['email'] = $email;
		$data['level'] = $product_sku;

		// generate the hash
		$hash = generateHash ($data, $secretKey);

		// include the hash to the data to be sent
		$data['hash'] = $hash;
		$log->LogDebug("\$data array = ".implode("|",$data ));			

		// send data to post URL
		$returnValue = postCURLRequest ($postURL, $data);

		// process return value
		list ($cmd, $url) = explode ("\n", $returnValue);
		$log->LogDebug("cmd: ".$cmd);
		$log->LogDebug("url: ".$url);
		$log->LogDebug("transaction type: ".$AIMProcessingResults['resp_transaction_type']);

		// check if the returned command is the same as what we passed
		if ($cmd != 'CREATE') {
			$log->LogError($AIMProcessingResults['resp_transaction_type']." transaction | failed to successfully use GENERIC integration with WishList");
			$processing_success = false;
		}
	}

	$log->LogDebug("processing_success=".$processing_success);

	// send email notification to user
	if ($processing_success) {
		$to = $email;
		$subject = "Transaction summary for ".get_bloginfo('name');
		$message = "A transaction has been successfully processed for your account.\n";
		$message = "The transaction ID is: ".$transaction_id."\n\n";
		$message .= "Your account has been created and or updated.\n\n";
		if ($url) $message .= "You may complete the registration process at this URL\n".$url."\n\n";
		$message .= "Login using your username and password provided below:\nusername: ".$username."\npassword: ".$password."\n";
		$message .= "You may login to your account for further details:\n".get_bloginfo('url')."/wp-login.php?redirect_to=/\n\n";
		$message .= "Sincerely,\nThe management";
		$headers = 'From: '.get_bloginfo('admin_email'). "\r\n" .
				   'Reply-To: '.get_bloginfo('admin_email'). "\r\n" .
				   'X-Mailer: PHP/' . phpversion();
		mail ($to, $subject, $message, $headers);
	}
	$log->LogDebug("************ END PROCESSING ************");
}

// forward to thank you page

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Example.com | Really!</title>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-1111111-1']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_addTrans',
<?php
	echo "    '".$AIMProcessingResults['resp_transaction_id']."',\n"; 			// order ID - required
	echo "    'Example Blog',\n"; 			// affiliation or store name
	echo "    '".(($subscription_details->processRecurringPayment == 1) ? $subscription_details->recurringAmount:$subscription_details->initialAmount)."',\n";	// total - required
	echo "    '',\n"; 							// tax
	echo "    '',\n"; 							// shipping
	echo "    '".$billingCity."',\n";			// city
	echo "    '".$billingState."',\n"; 			// state or province
	echo "    '".$billingCountry."',\n"; 		// country
?>
  ]);
  _gaq.push(['_addItem',
<?php
	echo "    '".$AIMProcessingResults['resp_transaction_id']."',\n";		// order ID - necessary to associate item with transaction	
	echo "    '".$subscription_id."',\n";		// SKU - required
	echo "    '".$subscription_details->name."',\n";		// product name
	echo "    '',\n";	// category or variation
	echo "    '".(($subscription_details->processRecurringPayment == 1) ? $subscription_details->recurringAmount:$subscription_details->initialAmount)."',\n";	// unit price - required
	echo "    '1',\n";	// quantity - required
?>
  ]);
  _gaq.push(['_trackTrans']); 

<?php
	echo "_gaq.push(function(){window.location = \"".get_bloginfo('url').'/'.get_option('authnet_thankyoupage')."\";});\n";
?>
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body>
<h1>Transaction successful!</h1>
<p>Redirecting you to the <strong>Thank You</strong> page...</p>
<br />
<?php
echo "<p><a href=\"".get_bloginfo('url').'/'.get_option('authnet_thankyoupage')."\">Click here</a> if your browser does not redirect you automatically.</p>"
?>
</body>
</html>

<?php

exit;

?>
