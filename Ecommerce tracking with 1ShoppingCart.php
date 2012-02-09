
<!-- BEGIN GATC Ecommerce -->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-685850-1']);
  _gaq.push(['_setDomainName', '.thestretchinghandbook.com']);
  _gaq.push(['_addIgnoredRef', '.mcssl.com']); 
  _gaq.push(['_setAllowLinker', true]);
  _gaq.push(['_setAllowHash', false]);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_addTrans',
    '<?php print $_REQUEST['orderID']?>',       	// order ID - required
    '<?php print $_REQUEST['referrerid']?>',  		// affiliation or store name
    '<?php print $_REQUEST['grandTotal']?>',    	// total - required
    '<?php print $_REQUEST['tax']?>',           	// tax
    '<?php print $_REQUEST['shippingAmount']?>',	// shipping
    '<?php print $_REQUEST['shipCity']?>',       	// city
    '<?php print $_REQUEST['shipState']?>',     	// state or province
    '<?php print $_REQUEST['shipCountry']?>'        // country
  ]);

   // add item might be called for every item in the shopping cart
   // where your ecommerce engine loops through each item in the cart and
   // prints out _addItem for each
<?php
// find out how many SKUs we have
$reqmax = 1;
foreach ($_REQUEST as $key => $value)
   {if (intval(str_replace('sku', '', $key)) > $reqmax)
	   {$reqmax ++;}}

// print out addItem for each
for ($i = 1; $i <= $reqmax; $i++)
{?>
	_gaq.push(['_addItem',
    '<?php print $_REQUEST['orderID']?>',	// order ID - required
    '<?php print preg_replace('/[^a-zA-Z0-9]/', '_', $_REQUEST['product'.$i])?>',      // SKU/code - required
    '<?php print $_REQUEST['product'.$i]?>',  // product name
    '0',   									// category or variation
    '<?php print printf('%.2F', floatval($_REQUEST['price'.$i] / $_REQUEST['quantity'.$i]))?>',    // unit price - required
    '<?php print $_REQUEST['quantity'.$i]?>'  // quantity - required
  ]);
<?php } ?>
  _gaq.push(['_trackTrans']); //submits transaction to the Analytics servers

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<!-- END GATC Ecommerce -->
