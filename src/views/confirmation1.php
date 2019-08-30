<?php
if(!isset($_POST['date']) || !isset($_POST['travelers'])) {
	header('location:'.config('app.url'));
}
$tour = getTourOnDate($_POST['date'], $_POST['travelers']);
$client = makeClient($_POST["name"], $_POST["email"], $_POST["phone"], $_POST['date'], $_POST['travelers']); 
?>
<div class="mb48">
	<div class="form-group" id="group_date">
	    <label for="date">Select your tour date</label>
	    <input type="text" class="form-control" name="date" id="date" aria-describedby="date" value="<?= $_POST["date"] ?>" autocomplete="off">
	    <img alt="Image" class="ui-datepicker-trigger" src="<?= config('cdn.url') ?>/assets/img/calendar.gif">
	</div>
	<div class="form-group" id="group_departure">
	    <label for="departure">Select depart time</label>
	    <select class="custom-select border border-primary" id="departure" name="departure" onchange="calc_form('deaperture');">
	        <option value="0,0">First Select Depart Time</option>
	        <?php foreach($tour->tour_departures as $departure) : ?>
	            <option value="<?= $departure->id; ?>,<?= $departure->name ?>"><?= $departure->name ?></option>
	        <?php endforeach; ?>
	    </select>
	</div>
	<div class="form-group" id="group_tour-option">
	    <label for="tour-option">Select tour option</label>
	    <select class="custom-select" id="tour-option" name="tour-option" onchange="calc_form()" disabled="">
	        <option value="0,0">Please Select Depart Time</option>
	    </select>
	</div>
	<?php if($tour->accept_adults) : ?>
	    <div class="form-group" id="group_adults">
	        <label for="adults">Adults - (Age 13+)</label>
	        <select class="custom-select" id="adults" name="adults" onchange="calc_form('adults')" disabled="">
	            <option value="0,0">Please Select Depart Time</option>
	        </select>
	    </div>
	<?php endif; ?>
	<?php if($tour->accept_seniors) : ?>
	    <div class="form-group" id="group_senior">
	        <label for="senior">Senior - (Age 65+)</label>
	        <select class="custom-select" id="senior" name="senior" onchange="calc_form('senior')" disabled="">
	            <option value="0,0">Please Select Depart Time</option> 
	        </select>
	    </div>
	<?php endif; ?>
	<?php if($tour->accept_kids) : ?>
	    <div class="form-group" id="group_kids">
	        <label for="kids">Kids - (Age 1-12)</label>
	        <select class="custom-select" id="kids" name="kids" onchange="calc_form('kids')" disabled="">
	            <option value="0,0">Please Select Depart Time</option>
	        </select>
	    </div>
	<?php endif; ?>
	<?php if($tour->accept_infants) : ?>
	    <div class="form-group" id="group_infants">
	        <label for="infants">Infants - (Age 0-1)</label>
	        <select class="custom-select" id="infants" name="infants" onchange="calc_form('infants')" disabled="">
	            <option value="0,0">Please Select Depart Time</option>
	        </select>
	    </div>
	<?php endif; ?>
	<div class="form-group" id="group_comments">
	    <label for="comments">Cruise / Hotel Name</label>
	    <input type="text" class="form-control" id="comments" name="hotel_name" placeholder="e.g. Hilton Resort / Carnival Valor" disabled="">
	</div>
</div>
<div class="alert bg-grey grey bold font16 d-flex align-items-center mb10" role="alert">
    <div class="mr-auto">
        <span>Total</span>
        <?php if($tour->charge_type!='all') : ?>
        	 <br /><span >Balance <small>(Paid at Meeting Point)</small></span>
        <?php endif; ?>
    </div>
    <div>
        <span id="reservation_total">--</span>
        <?php if($tour->charge_type!='all') : ?>
        	<br /><span id="reservation_balance" >--</span>
        <?php endif; ?>
    </div>
</div>
<div class="alert bg-grey grey bold font26 d-flex align-items-center mb42" role="alert">
    <div class="mr-auto">

        <span>You Pay Now</span>
        
    </div>
    <div>
        <span id="reservation_pay">--</span>
    </div>
</div>
<div class="controls">
    <input type="hidden" id="customer_ID" name="customer_ID" value="<?= $client->id ?>">
    <input type="hidden" id="Base_Pax_Number" value="<?= $tour->min_pax ?>">
    <input type="hidden" id="Max_Pax_Number" value="<?= $_POST['travelers'] ?>">
    <input type="hidden" id="options" value='<?= createOptions($tour) ?>'>
    <input type="hidden" id="tour_charge_type" value='<?= $tour->charge_type ?>'>
    <input type="hidden" id="tour_charge_data" value='<?= $tour->charge_data ?>'>
</div>