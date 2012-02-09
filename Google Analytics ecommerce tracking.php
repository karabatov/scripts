<?php 
session_start();
include_once "includes/db_connect.php";
include_once "includes/invoice_rel_functions.php";
include_once "includes/functions.php";
$custom = $_REQUEST['custom'];

//if(empty($inv_view) || $inv_view == ''){
	//$custom = $_REQUEST['custom'];
	if($custom == "") {
		echo "<script>window.location='index.php'</script>";
		} else {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
	<html><head><title>Xtrabed >> Invoice</title>   
	 <link rel="stylesheet" href="styles/sitm.css" type="text/css">
	 </head>
	 <body alink="NAVY" bgcolor="WHITE" style="font-family:Arial, Helvetica, sans-serif; font-size:11px;">
<?php 
// Uncomment the line below if e-commerce tracking does not work
	 
//include_once("analyticstracking.php"); 
	 
// If line below uncommented, comment e-commerce tracking code below -->
?> 
<?php
//$orderid = $_GET['orderid'];
$orderid = $custom;
define ("CURRENCY","$ ",1);

//$get_logoimage = "select `logoname` from `tbl_logo` where `id` = 1";
//mysql_query($$get_logoimage);
//$logo_item = mysql_fetch_array($get_logoimage);
//$sitelogo  = $logo_item['logoname'];

$orderdetails = "select * from `tbl_order` where `sessionId` = '".$orderid."' ORDER BY `orderDate` DESC LIMIT 1";
$orderdetailsres = mysql_query($orderdetails) or die("Error");
$orderdetailrow = mysql_fetch_array($orderdetailsres);

if($orderdetailrow['sessionId']==$orderid)
{
	$customerid = $orderdetailrow['customerId'];
	$deliverytype = $orderdetailrow['deliveryType'];
	$grandtotal = $orderdetailrow['totalPrice'];
	$shippingcost = $orderdetailrow['shippingcost'];
	
	$discountedamt = $orderdetailrow['discountedamt'];
	$tax           = $orderdetailrow['tax'];
	$maingrand = $orderdetailrow['GrandTotal'];
	$ordername = $orderdetailrow['orderName'];
	$orderdate = $orderdetailrow['orderDate'];
	$couponid = $orderdetailrow['coupon_code'];
	$freeproductid = $orderdetailrow['freeproduct_id'];
	
	$rand_num = $ordername;
	$getstate = "select * from `tbl_payment_info` where `rand_num` = '".$rand_num."'";
	
	
	$getstateres = mysql_query($getstate) or die("Error1");
	$getstaterow = mysql_fetch_array($getstateres);
	
	
	$stateid = $getstaterow['state'];
	
	$totalpricesql  = "select sum(totalprice) as totalcost from `tbl_order_details` where `orderId` = '".$rand_num."'";
	
	$totalpriceres = mysql_query($totalpricesql) or die("Error");
	$totalpricerow = mysql_fetch_array($totalpriceres);
	$totalprice = $totalpricerow['totalcost'];
	
	
	
	
	$getstatename    = "select `stateName` from `tbl_state` where `id` = '".$stateid."'";
	$getstatenameres = mysql_query($getstatename) or die("Error2");
	$getstatenamerow = mysql_fetch_array($getstatenameres);
	$statename = $getstatenamerow['stateName'];
	
	$getinvoicecontent = "select `content` from `tbl_invoice` where `id` = 1 ";
	$getinvoicecontentres = mysql_query($getinvoicecontent) or die("invoiceerror");
	$getinvoicerow = mysql_fetch_array($getinvoicecontentres);
	
	$getcoupontype    = "select `couponType` from `tbl_coupons` where `id` = '".$couponid."'";
	$getcoupontyperes = mysql_query($getcoupontype) or die("Errorcoupon");
	$getcoupontyperow = mysql_fetch_array($getcoupontyperes);
	$coupontype = $getcoupontyperow['couponType'];
	if($coupontype == 1)
	{
	 	$freetext = "Free Gift";
	 }else {
	 	$freetext = "";
	 } 
}

 
	 $invoice = html_entity_decode($getinvoicerow['content']);
	 
	 //$date = getDateFormatted($orderdate)." at ". GMTTime();
	 $date = $orderdate." at ". GMTTime();
	 $customername = $getstaterow['fname']." ".$getstaterow['lname'];
	 if($getstaterow['address2'] == "") { $streetaddress2 = "N/A"; } else { $streetaddress2 = $getstaterow['address2']; } 
	$customeraddress = $getstaterow['address1']."<br>".
								$streetaddress2."<br>".
								$getstaterow['citytown']."<br>" .
								$statename."<br>".
								"US";	
	$contents = preg_replace("/#logo#/","<img src='includes/createThumb.php?imageName=../gallery/logo/21626.jpg&w=150&h=150' border='0'>",$invoice);
	$contents = preg_replace("/#date#/",$date,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#receiptno#/",$ordername,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#name#/",$customername,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#address#/",$customeraddress,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#emailaddress#/",$getstaterow['email'],str_replace("&nbsp;"," ",$contents));
	
		
	
	/* separate end*/
	$tablestring = "<table width='100%' cellspacing = '0' cellpadding ='0' border='0'>
	  <tr>
		<td valign='top'><table width='100%' border='0' cellspacing='0' cellpadding='0'>
		  <tr>
			<td style='width:250px'>Product/Package Details</td>
			<td style='width:255px' >Quantity</td>
			<td >Total Price</td>
		  </tr>
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>";
		 $productdetails = "select * from `tbl_order_details` where `orderId` = '$rand_num'";
		  
		  $productdetailsres= mysql_query($productdetails) or die("productError");
		  while ($productdetailsrow = mysql_fetch_array($productdetailsres))
		  {
			  $price = '&#36;'.number_format($productdetailsrow['price'], 2);
			 
			$isproduct = $productdetailsrow['isProduct'];
			
			if($isproduct == 1)
			{
				$producttype = "Product";
				$productsql  = "select `name` as products from `tbl_products` where `id` = '".$productdetailsrow['productId']."'";
				$gallerypath = "gallery/products/";
			} else {
				$producttype = "Package";
				$productsql  = "select `packageName` as products from `tbl_packages` where `id` = '".$productdetailsrow['productId']."'";
				$gallerypath = "gallery/packages/";
			}
				$productres = mysql_query($productsql) or die("secondError");
				$productrow = mysql_fetch_array($productres);
			
			?>
			<?php
			$tablestring .="
		 <tr>
			<td><table width='100%' border='0' cellspacing='0' cellpadding='0'>
			  <tr>
				<td  valign='top'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					  <tr>
					<td style='width:60px'>Type:</td>
					<td >".$producttype."</td>
					</tr>
					<tr>
					<td  valign='top'>Name:</td>
					<td >".$productrow['products']."</td>
					</tr>
					<tr>
					<td >Color:</td>
					<td >";
					 
					$colorselect = "select `color` from `tbl_colors` where `id` = '".$productdetailsrow['colorId']."'";
					$colorselectres = mysql_query($colorselect) or die("colorError");
					$colorselectrow = mysql_fetch_array($colorselectres);
					if($colorselectrow['color'] == "")
					{
						$tablestring .="N/A";
					} else {
						$tablestring .="".$colorselectrow['color']."";
					}
					 $tablestring .="
					 </tr>
					<tr>
					<td style='padding-bottom:5px'>Price:</td>
					<td> ".$price."</td>
					</tr>
					</table>            </td>

			  </tr>
			</table></td>
			<td valign='top'>".$productdetailsrow['quantity']."</td>
			<td valign='top'>"." $ ".number_format($productdetailsrow['totalprice'],2)."</td>
		  </tr>";
			// Google Analytics e-commerce tracking
			$gaProductId = $productdetailsrow['productId'];
			$addItems[$gaProductId]['SKU'] = $gaProductId;
			$addItems[$gaProductId]['Name'] = $productrow['products'];
			$addItems[$gaProductId]['Category'] = $producttype;
			$addItems[$gaProductId]['UnitPrice'] = $productdetailsrow['price'];
			$addItems[$gaProductId]['Quantity'] = $productdetailsrow['quantity'];
			// End Google Analytics e-commerce tracking		  
		  }
		  if($freeproductid != 0)
		{
			$getproductname = "select `name` from `tbl_products` where `id` = '".$freeproductid."'";
			$getproductnameres = mysql_query($getproductname) or die("Error");
			$getproductnamerow = mysql_fetch_array($getproductnameres);
			$tablestring .="
			<tr>
			<td><table width='100%' border='0' cellspacing='0' cellpadding='0'>
			  <tr>
				<td  valign='top'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					  <tr>
					<td style='width:60px'>Type:</td>
					<td  >Product</td>
					</tr>
					<tr>
					<td  valign='top'>Name:</td>
					<td >".$getproductnamerow['name']."</td>
					</tr>
					<tr>
					<td >Status</td>
					<td>Free Gift </td>
					</tr>
					<tr>
					<td style='padding-bottom:5px'>&nbsp;</td>
					<td></td>
					</tr>
					</table>            </td>
			  </tr>
			</table></td>
			<td valign='top'>&nbsp;</td>
			<td valign='top'>&nbsp;</td>
		  </tr>
			";
			// Google Analytics e-commerce tracking
			$addItems[$freeproductid]['SKU'] = $freeproductid;
			$addItems[$freeproductid]['Name'] = $getproductnamerow['name'];
			$addItems[$freeproductid]['Category'] = "Product";
			$addItems[$freeproductid]['UnitPrice'] = 0;
			$addItems[$freeproductid]['Quantity'] = 1;
			// End Google Analytics e-commerce tracking		
		}
		  $tablestring .="
		</table>    </td>
	  </tr>
 
	  </table>";
	
	/*ends */
	//$shippingcost = $shipinfo['shipping_price'];
	
	$contents = preg_replace("/#productdetails#/",$tablestring,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#grosstotal#/", number_format($totalprice,2) ,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#discount#/", number_format($discountedamt,2) ,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#freegift#/", $freetext,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#tax#/", number_format($tax,2) ,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#shippingcost#/", number_format($shippingcost,2) ,str_replace("&nbsp;"," ",$contents));
	$contents = preg_replace("/#finalgrandtotal#/", number_format($maingrand,2) ,str_replace("&nbsp;"," ",$contents));
	echo $contents;
	//}
	
?>

<script type="text/javascript">
  var gaJsHost = (("https:" == document.location.protocol ) ? "https://ssl." : "http://www.");
  document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try{
  var pageTracker = _gat._getTracker("UA-22353739-1");
  pageTracker._trackPageview();
  pageTracker._addTrans(    
  
<?php
	echo "\"".$orderid."\",\n"; 				// order ID - required
	echo "\"Xtrabed\",\n"; 						// affiliation or store name
	echo "\"".$maingrand."\",\n"; 				// total - required
	echo "\"".$tax."\",\n"; 					// tax
	echo "\"".$shippingcost."\",\n"; 			// shipping
	echo "\"".$getstaterow['citytown']."\",\n";	// city
	echo "\"".$statename."\",\n"; 				// state or province
	echo "\"USA\",\n"; 							// country
?>
    );

<?php
   // add item might be called for every item in the shopping cart
   // where your ecommerce engine loops through each item in the cart and
   // prints out _addItem for each 
	foreach ($addItems as $gaItem) {
		echo "pageTracker._addItem(\n";
		foreach ($gaItem as $key => $value) {
			echo "\"".$orderid."\",\n";				// order ID - necessary to associate item with transaction	
			echo "\"".$gaItem['SKU']."\",\n";		// SKU - required
			echo "\"".$gaItem['Name']."\",\n";		// product name
			echo "\"".$gaItem['Category']."\",\n";	// category or variation
			echo "\"".$gaItem['UnitPrice']."\",\n";	// unit price - required
			echo "\"".$gaItem['Quantity']."\",\n";	// quantity - required
		};
		echo ");\n";		
	};

?>

   pageTracker._trackTrans(); //submits transaction to the Analytics servers
} catch(err) {}
</script>


<?php
	
unset($_SESSION['rand_num']);
die();


?>
</body></html>
<?php



}
?>
