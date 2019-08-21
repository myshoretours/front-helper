<?php 
$reservation = reservate(
	$_POST["customer_ID"],
	$_POST["date"],
	$_POST["departure"],
	$_POST["tour-option"],
	$_POST["hotel_name"],
	$_POST["adults"] ?? '0,0',
	$_POST["kids"] ?? '0,0',
	$_POST["infants"] ?? '0,0',
	$_POST["senior"] ?? '0,0'
);
pay($reservation);
?>