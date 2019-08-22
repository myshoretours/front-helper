<?php

require 'recapchalib.php'; 
$config_var = [];

if(file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::create($_ENV['APP_BASE_PATH']);
    $dotenv->load();
}

function env($first, $second=null) {
    $return = getenv($first);
    if(!$return) {
        return $second;
    }
    return $return;
}

function config($variable, $value = null)
{
    global $config_var;
    if(!is_null($value)) {
        // Lets save the data
        $config_var[$variable] = $value;
        return;
    }
    if(isset($config_var[$variable])) {
        return $config_var[$variable];
    }
    $configs = include($_ENV['APP_BASE_PATH'].'/config/app.php');
    return $configs[$variable] ?? null;
}

function apiUrl($variable = null)
{
    $url = config('backend.url');
    if(!is_null($variable)) {
        $url = $url.$variable;
    }
    return $url.'?api_token='.config('backend.api_token');
}

function reviewDate($months) 
{
    $retVal = '';
    $anio = date('Y');
    if ((date('m') - $months) < 1)
    {
        $anio = $anio -1;
    }
    $fecha = strtotime('-'.$months.' month',strtotime(date('Y-m-j')));
    $retVal = date('F', $fecha)." ".$anio." "; 
    return $retVal;
}

function post($url, $data = [])
{
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return false;
    }
    return $result;
}

function get($url)
{
    $result = file_get_contents($url);
    if ($result === FALSE) {
        return false;
    }
    return $result;
}

function getTour()
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id'));
    $response = get($url);
    return json_decode($response);
}

function getTourOnDate($date, $pax)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/on-date');
    $response = post($url, compact('date', 'pax'));
    return json_decode($response);
}

function max_pax_option()
{
    $tour = getTour();
    $return = '';
    $return .= "<option value=''>--</option>\n\r";
    for($i = 1; $i <= $tour->max_pax; $i++) {
        if($i==100) {
            break;
        }
        $return .= '<option value="'.$i.'">'.$i."</option>\n\r";
    }
    return $return;
}

function is_available($date, $pax)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/available');
    $response = post($url, compact('date', 'pax'));
    return json_decode($response)->result;
}

function makeClient($name, $email, $phone, $date, $people)
{
    $data = [
        'name'      => $name,
        'email'     => $email,
        'telephone' => $phone,
        'tour_id'   => config('backend.tour_id'),
        'date'      => $date,
        'people'    => $people
    ];
    $url = apiUrl('/api/clients');
    $response = post($url, $data);
    return json_decode($response);
}

function createOptions($tour)
{
    $return = [];
    foreach($tour->tour_departures as $departure) {
        foreach ($departure->tour_options as $option) {
            $array = [
                'Option_ID' => $option->id,
                'Option_Name' => $option->name,
                'Base_Price' => $option->base_price,
                'Adult_Price' => $option->adult_price,
                'Kid_Price' => $option->kid_price,
                'Infant_Price' => $option->infant_price,
                'Senior_Price' => $option->senior_price,
                'Departure_ID' => $departure->id,
                'Departure_Hour' => $departure->name,
            ];
            $return[] = $array;
        }
    }
    return json_encode($return);
}

function reservate($client_id, $date, $tour_departure_id, $tour_option, $hotel_name, $pax_adults, $pax_kids, $pax_infant, $pax_senior)
{
    // User stack
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $access_key = 'e129c241b8a9e24224966ab364ae3147';

    $ch = curl_init('http://api.userstack.com/api/detect?access_key='.$access_key.'&ua='.urlencode($user_agent));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    curl_close($ch);

    $api_result = json_decode($json, true);
    $user_agent = $api_result['device']['type'];

    // Set variables
    $date              = date('Y-m-d', strtotime($date));
    $pax_adults        = explode(",",$pax_adults);
    $pax_kids          = explode(",",$pax_kids);
    $pax_infant        = explode(",",$pax_infant);
    $pax_senior        = explode(",",$pax_senior);  
    $tour_departure_id = explode(",",$tour_departure_id)[0];
    $tour_option       = explode(",",$tour_option);
    $sell_url          = $_SERVER["HTTP_HOST"];
  
    $total = $pax_adults[1] + $pax_kids[1] + $pax_senior[1] + $pax_infant[1]+ $tour_option[1];
    $adults = $pax_adults[0];
    $infants = $pax_infant[0];
    $kids = $pax_kids[0];
    $seniors = $pax_senior[0];
    $tour_option_id = $tour_option[0];
  
    $ip_costumer = $_SERVER['REMOTE_ADDR'];  
    
    $data = compact('client_id', 'tour_id', 'tour_departure_id', 'tour_option_id', 'kids', 'infants', 'adults', 'seniors', 'total', 'hotel_name', 'date', 'sell_url', 'ip_costumer', 'user_agent');
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/reservate');
    $response = post($url, $data);
    return json_decode($response);
}

function pay($reservation)
{
    $names = explode(' ', $reservation->client->name);
  
    $datos_paypal = array(
        "cmd"             => "_xclick",
        "lc"              => "US",
        "currency_code"   => 'USD',
        "bn"              => "PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest",
        "first_name"      => $names[0] ?? 'ND',
        "last_name"       => $names[1] ?? 'ND',
        "payer_email"     => $reservation->client->email,
        "item_number"     => $reservation->id,
        'rm'              => 2,
    );

    $querystring = "?business=".urlencode(config('paypal.email'))."&";  
    $querystring .= "item_name=".urlencode($reservation->tour->name)."&";
    $querystring .= "amount=".urlencode($reservation->total)."&";

    //loop for posted values and append to querystring
    foreach($datos_paypal as $key => $value){
        $value = urlencode(stripslashes($value));
        $querystring .= "$key=$value&";
    }

    // Append paypal return addresses
    $querystring .= "return=".urlencode(stripslashes(config('app.url').'/thank-you'))."&";
    $querystring .= "cancel_return=".urlencode(stripslashes(config('app.url').'/payment-uncompleted'));

    // Redirect to paypal IPN
    $paypal_site = 'https://www.paypal.com';
    if(config('paypal.sandbox')) {
        $paypal_site = 'https://www.sandbox.paypal.com';
    }
    $url = $paypal_site.'/cgi-bin/webscr'.$querystring;
    header('location:'.$url);
    exit();
}

function confirmReservation($reservation_id, $confirmation, $email, $name)
{
    $url = apiUrl('/api/reservations/'.$reservation_id.'/confirm');
    $response = post($url, compact('confirmation', 'email', 'name'));
    return json_decode($response);
}

function contactForm($data)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/contact');
    $response = post($url, $data);
    return json_decode($response);
}

function getPopupMessage($number) 
{
    $config = 'popup.'.$number;
    $messages = config($config);
    $key = array_rand($messages, 1);
    return $messages[$key];
}

function showFrontView($name, $vars = []) 
{
    foreach ($vars as $key => $value) {
        $$key = $value;
    }
    return include __DIR__.'/views/'.$name.'.php';
}

function contactIsValid()
{
    // your secret key recapcha
    $secret = config('captcha.secret');
    // empty response
    $response = null;
    // check secret key
    $reCaptcha = new ReCaptcha($secret);
    // if submitted check response
    if ($_POST["g-recaptcha-response"]) {
        $response = $reCaptcha->verifyResponse(
            $_SERVER["REMOTE_ADDR"],
            $_POST["g-recaptcha-response"]
        );
    }
    $capcha = false;
    if ($response != null && $response->success) {
        $capcha = true;
    }
    
    $inputs = $_POST;
    unset($inputs['g-recaptcha-response']);
    unset($inputs['contact_submit']);
    
    $inputs = collect($inputs)->map(function($item) {
        return trim($item);
    });
    $total = $inputs->count();
    $inputs = $inputs->filter(function($item) {
        return strlen($item) > 0 && !is_null($item);
    });
    return $inputs->count() == $total && $capcha;
}

?>