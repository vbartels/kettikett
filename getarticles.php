<?php

require('./settings.php');
require('./barcode.php');
require('fpdf.php');



class PDF extends FPDF { //Multi Column PDF
	protected $col = 0; // Current column
	protected $y0 =10;      // Ordinate of column start
	
	function SetCol($col)
	{
		// Set position at a given column
		$this->col = $col;
		$x = 10+$col*65;
		$this->SetLeftMargin($x);
		$this->SetX($x);
	}
	
	function AcceptPageBreak()
	{
		// Method accepting or not automatic page break
		if($this->col<2)
		{
			// Go to next column
			$this->SetCol($this->col+1);
			// Set ordinate to top
			$this->SetY($this->y0);
			// Keep on page
			return false;
		}
		else
		{
			// Go back to first column
			$this->SetCol(0);
			// Page break
			return true;
		}
	}
	
	
}

function createPDF() {
	
	global $ean_numbers;
	global $schildwidth;
	global $schildheight;
	global $font;
	global $border_enabled;
	global $cosmetics;
	global $warning;
	
	if($cosmetics) { //half the width if cosmetics
		$schildwidth = $schildwidth / 2;
	}
	
	foreach($ean_numbers as $number) {
		$product = getProductByEAN((int) $number);
		
		$name = $product->name;
		
		$price = 0;
		$prices = null;
		$pricecounter = 0;
		foreach($product->prices as $price) { //collect cleaned prices into array
			$nextprice = $price->validFrom; 	//Date Format: 2021-07-26T11:46:37.062Z
			$nextprice = substr($nextprice, 0, -15);
			$nextprice = (int) preg_replace("/-/", "", $nextprice);
			
			if ($nextprice != 0) {
				$prices[$pricecounter] = $nextprice;
			}
			$pricecounter++;
		}
		
		$today = (int) date("Ymd");
		//print_r2("vorher: " . $price->value);
		$price = $product->prices[getClosest($today, $prices)]->value;	//set price closest to now
		//print_r2("nachher: " . $price);
		
		if (! is_null($product->subproducts)) {
			$pawn = $product->subproducts[0]->prices[0]->value;
		} else {
			$pawn = null;
		}
		$size = $product->packagingQuantity;
		$unit = $product->packagingUnit;
		$ean = $number;
		$koronanumber = $product->number;
		
		$schild = imagecreate($schildwidth, $schildheight); 
		
		$bg = imagecolorallocate($schild,255,255,255);  //backgroundcolor white
		$fg = imagecolorallocate($schild,0,0,0);		//foreground color black
		$bordercolor = imagecolorallocate($schild,80,80,80); //border color grey
		
		switch ($unit) {
			case "MILLILITER" :
				$unit = "ml";
				if((int) $size > 1)
					$reference = "100";
				break;
			case "KILOGRAM" :
				$unit = "Kg";
				$reference = "1";
				break;
			case "GRAM" :
				$unit = "g";
				if((int) $size > 1)
					$reference = "100";
				break;
		}
		
		if(! $cosmetics) { //print normal size
			//Productname
			imagettftext($schild, 35, 0, 40, 80, $fg, $font, $name);

			//Price
			imagettftext($schild, 90, 0, 350, 280, $fg, $font, $price . "€");

			//Pawn
			if (! is_null($pawn)) {
				$pawn = sprintf("%01.2f", $pawn); //fill with following zeros
				imagettftext($schild, 30, 0, 340, 320, $fg, $font, "Zzgl. Pfand: " . $pawn . "€");
			}	

			//Details Korona Article# and EAN (prettier than output from barcode.php)
			imagettftext($schild, 20, 0, 60, 250, $fg, $font, "K#:   " . $koronanumber);
			imagettftext($schild, 20, 0, 60, 220, $fg, $font, "EAN: " . $ean);

			//Productsize
			if (! is_null($size)) {
				imagettftext($schild, 30, 0, 60, 290, $fg, $font, $size . $unit);

				$reference_price = round(((float) $price / (float) $size) * (float)$reference,2); //round reference price
				$reference_price = sprintf("%01.2f", $reference_price); //fill with following zeroes
				imagettftext($schild, 20, 0, 60, 320, $fg, $font, $reference_price . "€ / " . $reference . $unit);
			} else { //price per piece
				imagettftext($schild, 30, 0, 60, 290, $fg, $font, "Stück");
			}
			//Barcode
			$barcode_options = array(
				"w" => 750,		//width of barcode
				"h" => 100,		//height of barcode
				"ts" => 5,		//Text Size
				"th" => 15,		//Text Distance to barcode
				"tc" => "FFFFFF", //Text color -> white
				"p" => 10,		//padding
			);
			$generator = new barcode_generator();
			$barcode = $generator->render_image('ean-13', $ean, $barcode_options);

			imagecopymerge($schild, $barcode,-10,90,0,0,750,100,100);

			//Border
			if ($border_enabled) {
				//top
				imageline($schild,0, 0, $schildwidth, 0, $bordercolor);
				//bottom
				imageline($schild,0, ($schildheight - 1), $schildwidth, ($schildheight - 1), $bordercolor);
				//left
				imageline($schild,0, 0, 0, $schildheight, $bordercolor);
				//right
				imageline($schild, ($schildwidth - 1), 0, ($schildwidth - 1), ($schildheight -1), $bordercolor);
			}
		} else { //everything smaller
			//Productname
			imagettftext($schild, 20, 0, 20, 80, $fg, $font, $name);

			//Price
			imagettftext($schild, 40, 0, 190, 270, $fg, $font, $price . "€");

			//Pawn
			//$pawn = round((float) $pawn, 2);
			if (! is_null($pawn)) {
				$pawn = sprintf("%01.2f", $pawn); //fill with following zeros
				imagettftext($schild, 20, 0, 130, 295, $fg, $font, "Zzgl. Pfand: " . $pawn . "€");
			}	

			//Details Korona Article# and EAN (prettier than output from barcode.php)
			imagettftext($schild, 18, 0, 30, 250, $fg, $font, "K#:   " . $koronanumber);
			imagettftext($schild, 18, 0, 30, 220, $fg, $font, "EAN: " . $ean);

			//Productsize
			if (! is_null($size)) {
				imagettftext($schild, 20, 0, 30, 295, $fg, $font, $size . $unit);

				$reference_price = round(((float) $price / (float) $size) * (float)$reference,2); //round reference price
				$reference_price = sprintf("%01.2f", $reference_price); //fill with following zeroes
				imagettftext($schild, 20, 0, 30, 325, $fg, $font, $reference_price . "€ / " . $reference . $unit);
			} else { //price per piece
				imagettftext($schild, 30, 0, 30, 295, $fg, $font, "Stück");
			}
			//Barcode
			$barcode_options = array(
				"w" => 750 / 2,		//width of barcode
				"h" => 100,		//height of barcode
				"ts" => 5,		//Text Size
				"th" => 15,		//Text Distance to barcode
				"tc" => "FFFFFF", //Text color -> white
				"p" => 10,		//padding
			);
			$generator = new barcode_generator();
			$barcode = $generator->render_image('ean-13', $ean, $barcode_options);

			imagecopymerge($schild, $barcode,-10,90,0,0,750,100,100);

			//Border
			if ($border_enabled) {
				//top
				imageline($schild,0, 0, $schildwidth, 0, $bordercolor);
				//bottom
				imageline($schild,0, ($schildheight - 1), $schildwidth, ($schildheight - 1), $bordercolor);
				//left
				imageline($schild,0, 0, 0, $schildheight, $bordercolor);
				//right
				imageline($schild, ($schildwidth - 1), 0, ($schildwidth - 1), ($schildheight -1), $bordercolor);
			}
		}
		
		
		imagepng($schild,"./tmp/" . (int) $number . ".png");
		imagedestroy($schild);
		unset($number);
	}
	$pdf = new PDF();
	$pdf->AddPage();
	
	foreach($ean_numbers as $number) {
		$filename = "./tmp/" . (int) $number . ".png";
		$pdf->Image($filename, null , null, -300);
		//unlink($filename); //remove tmp png file on filesystem
	}
	
	if (! is_null($warning)) {
		$pdf->AddPage(); //new page for warnings
		$pdf->SetFont('Arial','B',8);
		
		foreach ($warning as $warn) {
				$pdf->SetCol(0);
				$pdf->Cell(40,10,$warn);
				$pdf->ln();
		}
		
	}
	
	$pdf->Output();
}

function processForm() {
	global $border_enabled;
	global $cosmetics;
	global $ean_numbers;
	$border_enabled = (bool)$_POST['border'];
	$cosmetics = (bool)$_POST['cosmetics'];
	$ean_numbers = explode("\n", $_POST['EANs']); //explode given EANs into array
	
	array_walk($ean_numbers, 'trim_value'); //remove empty entries (for example trailing \n from barcode scanner at the end of the input)
	$ean_numbers = array_filter($ean_numbers, 'strlen'); 
	$ean_numbers = array_values($ean_numbers);
}

function checkProductSizes() {
	global $ean_numbers;
	global $warning;
	$i = 0;
	foreach($ean_numbers as $ean) {
		$product = getProductByEAN((int) $ean);
		if($product->packagingUnit == null) {
			$warning[$i] = "Kein Vergleichswert: " . $product->name . " / " . $product->number;
		}
		$i++;
	}
}

function curlRequest($command) {
	global $username, $password, $apikey, $endpoint;
	
	// create & initialize a curl session
	
	$curl = curl_init();

	//set username and password
	curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);  

	// return the transfer as a string, also with setopt()
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	//set apikey
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		  'APIKEY: ' . $apikey,
		  'Content-Type: application/json',
	   ));

	//set basic authetication
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

	// set our url with curl_setopt()
	curl_setopt($curl, CURLOPT_URL, $endpoint . $command);

	// curl_exec() executes the started curl session
	// $output contains the output string
	$output = curl_exec($curl);
	// close curl resource to free up system resources
	// (deletes the variable made by curl_init)
	curl_close($curl);
	return json_decode($output, false);
	unset($curl);
}

function getProductList() {
	$command = "/accounts/" . $apikey . "/products";
	$products = curlRequest($command);
	
	while (! is_null($products->links->next)) { //Pagination
		foreach($products->results as $product) {
			if (! is_null($product->codes)) {
				foreach($product->codes as $code) {
					$eanlist[(int) $code->productCode] = $product->number;
				}
			}
		}
		
		$command = substr($products->links->next, 11); //cut the "/web/api/v3" at the beginning of the URL
		$products = curlRequest($command);	
	}
	foreach($products->results as $product) { //last page - this feels ugly af
		if (! is_null($product->codes)) {
			foreach($product->codes as $code) {
				$eanlist[(int) $code->productCode] = $product->number;
			}
		}
	}
	return $eanlist;
}

function getProductByEAN(int $ean) {
	global $list;
	if(! is_null($list)) {
		
		if(! is_null($list[$ean])) {
			$command = "/accounts/" . $apikey . "/products/" . $list[$ean];
			$product = curlRequest($command);
		} else {
			#exit("Something went wrong :(");
		}
	} else {
		exit("perform 'getProductList()' first!");
	}
	return $product;
}

function getClosest($search, $arr) {
   $closest = null;
   $counter = 0;
   foreach ($arr as $item) {
      if ($closest === null || abs($search - $closest) > abs($item - $search)) {
		 $closest = $item;
         $closest_index = $counter;
      }
	  $counter++;
   }
   return $closest_index;
}



### MAIN CODE ###

$list = getProductList(); 	//get all products via Korona API
processForm();			//get EANs and settings
checkProductSizes();		//check if given products have all needed values in KORONA backend, if not->print warning
createPDF();

### TEST STUFF ###

function print_r2($val){
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
}

function trim_value(&$value) 
{ 
    $value = trim($value); 
}

?>
