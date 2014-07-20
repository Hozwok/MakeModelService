<?php


class MakeModelParameter { 

    const LOCALE_ES 		= 'ES';
    const LOCALE_IT 		= 'IT';
    const LOCALE_DE 		= 'DE';
    const LOCALE_FR 		= 'FR';

    public $cacheFile		= "makemodels.cache";
    public $saveCacheToDisk	= true;

    public $locale 			= "IT"; //Default locale..

    public $Url 			= array(
    	'IT' => array(
			'ModelLineData'						=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleModelLineDataIT?callback=&countryISOCode=IT",
			'VehicleIdentificationData'			=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleIdentificationDataIT?callback=&countryISOCode=IT"
			),
    	'ES' => array(
			'ModelLineData'						=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleModelLineData?callback=&countryISOCode=ES",
			'VehicleIdentificationData'			=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleIdentificationData?callback=&countryISOCode=ES"
			),
    	'FR' => array(
			'ModelLineData'						=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleModelLineData?callback=&countryISOCode=FR",
			'VehicleIdentificationData'			=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleIdentificationData?callback=&countryISOCode=FR"
			),
    	'DE' => array(
			'ModelLineData'						=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleModelLineData?callback=&countryISOCode=DE",
			'VehicleIdentificationData'			=> "http://api.autoscout24.com/TaxonomyService/json/GetVehicleIdentificationData?callback=&countryISOCode=DE"
			)
    	);

    //function __construct() { ]

}


class MakeModelService {


	protected $parameter;
	protected $List;

	private $Fuels;
	private $BodyType;

	private $locale;

	function __construct() {

		if(func_num_args() == 1 && func_get_arg(0) instanceof MakeModelParameter) { // Overloading method is not possible in PHP .. so, if you not set as parameter an instance of MakeParameter, I will do it.
			$this->parameter = func_get_arg(0);
		} else {
			$this->parameter = new MakeModelParameter();	
		}

		$this->init();
		
	}

	public function GetMakeModelList() {
		return $this->List;
	}

	public function SetLocale($locale) {

		if(isset($this->parameter->Url[$locale])) {
			$this->locale = $locale;
		} else {
			throw new Exception("Error. Locale not found!", 1);
		}
	}

	public function GetModelNameFromModelId($modelId) {
		foreach ($this->List as $MakeId => $value) {
			if(isset($value["Models"][$modelId])) {
				return $value["Models"][$modelId];
			}
		}
		return "";
	}

	public function GetMakeIdByMakeName($makeName, $strict = false) { //Search the name in the make list... and if find it, return its ID, otherwise false.

		$found = $this->SearchMakeByName($makeName, true);
	
		if (count($found) == 1) {
			return $found[0]["Id"];
		}

		return false;
	}

	public function SearchMakeByName($makeName, $strict = false) {
		$found = array();
		
		foreach ($this->List as $IdMake => $value) {
			if((!$strict & stripos($value["Make"], $makeName) !== false) || ($strict && strtolower($value["Make"]) == strtolower($makeName))) {
				$found[] = array("Id" => $IdMake, "Make" => $value["Make"]);
			}
		}

		return $found;
	}

	public function GetModelLineData($make, $year, $month) {

		if(!isset($this->List[$make]))
			return array();

		if ($month < 10) {
			$month = "0" . $month;
		}

		$make =  str_replace("m", '', $make); //If found, remove the "m" before the id.

		$url = $this->parameter->Url[$this->locale]['ModelLineData'] . "&make=" . $make . "&year=" . $year . $month;

		echo "<br /> Chiamo ". $url;
		$res  = file_get_contents($url);


		if (strlen($res) == 0)
			return array();

		$res = json_decode($res, true);

		if ($res == NULL) //Error while parsing
			return array();

		$cleanResult = array();
		foreach ($res as $k => $value) {
			$_obj = array(
				"BodyTypeID" 	=> $value["BodyTypeID"],
				"BodyType"		=> $this->BodyType[$value["BodyTypeID"]],
				"ModelID" 		=> $value["ModelID"],
				"Model"			=> $this->GetModelNameFromModelId($value["ModelID"]),
				"NoOfDoors"		=> $value["NoOfDoors"]
				);
			$cleanResult[] = $_obj;
		}

		return $cleanResult;

		
	}

	public function GetVehicleIdentificationData($makeId, $modelId, $year, $month, $bodyTypeId, $noOfDoors) {

		if ($month < 10) {
			$month = "0" . $month;
		}

		$url = $this->parameter->Url[$this->locale]['VehicleIdentificationData'] . "&make=" . $makeId . '&year=' . $year . $month .'&modelID=' . $modelId . '&bodyTypeID='.$bodyTypeId.'&numberOfDoors='.$noOfDoors;
echo "<br />Chiamo ". $url;
		$res  = file_get_contents($url);

		//echo $url;

		if (strlen($res) == 0)
			return array();

		$res = json_decode($res, true);

		if ($res == NULL) //Error while parsing
			return array();


		$cleanResult = array();

		foreach ($res as $k => $value) {
			
			$_obj = array(
				"MakeID"		=> $makeId,
				"Make"			=> $this->List[$makeId]["Make"],
				"ModelID"		=> $modelId,
				"Model"			=> $this->GetModelNameFromModelId($modelId),
				"Year"			=> $value["ANNOXX"],
				"Month"			=> $value["MESEXX"],
				"BuildPeriod"	=> $value["BuildPeriod"],
				"SetupID"		=> $value["CODALL"],
				"BodyTypeID"	=> $bodyTypeId,
				"BodyType"		=> $this->BodyType[$bodyTypeId],
				"FuelTypeID"	=> $value["FuelTypeID"],
				"FuelType"		=> $this->Fuels[$value["FuelTypeID"]],
				"Version"		=> $value["Version"],
				"ModelLine"		=> $value["ModelLine"],
				"NoOfDoors"		=> $value["NoOfDoors"],
				"PowerKW"		=> $value["PowerKW"],
				"PowerPS"		=> $value["PowerPS"]
				);

			$cleanResult[] = $_obj;
		}


		return $cleanResult;
	}




	protected function init(){
		$this->loadMakeModel();
		$this->loadFuelsAndBodyType();
		echo "setto il locale con " . $this->parameter->locale;
		$this->SetLocale($this->parameter->locale);
	}

	protected function loadFuelsAndBodyType() {

		// You can update this or extend if necessary from the values in http://www.autoscout24.eu/Search.aspx
		$_fuels = json_decode('[{"Key":"2","Value":"Electric/Gasoline"},{"Key":"3","Value":"Electric/Diesel"},{"Key":"B","Value":"Gasoline"},
			{"Key":"C","Value":"CNG"},{"Key":"D","Value":"Diesel"},{"Key":"E","Value":"Electric"},{"Key":"G","Value":"Gas"},{"Key":"H","Value":"Hydrogene"},
			{"Key":"L","Value":"LPG"},{"Key":"M","Value":"Ethanol"},{"Key":"O","Value":"Others"}]', true);

		$_bodyType = json_decode('[{"Key":"1","Value":"Compact"},{"Key":"2","Value":"Convertible"},
			{"Key":"3","Value":"Coupe"},{"Key":"4","Value":"Off-Road"},{"Key":"5","Value":"Station wagon"},
			{"Key":"6","Value":"Sedans"},{"Key":"7","Value":"Other"},{"Key":"12","Value":"Van"},
			{"Key":"13","Value":"Transporter"}]', true);

		foreach ($_fuels as $k => $value) {
			$this->Fuels[$value["Key"]] = $value["Value"];
		}

		foreach ($_bodyType as $k => $value) {
			$this->BodyType[$value["Key"]] = $value["Value"];
		}

	}

	private function loadMakeModel() {

		if (file_exists($this->parameter->cacheFile) && filesize($this->parameter->cacheFile)>0) {
			$this->List = $this->loadMakeModelFromDisk();
		} else {
			$this->List = $this->loadMakeModelFromService();
		}

		return $this->List;
	}

	private function loadMakeModelFromDisk() {
		$returnValue = array();

		$fileContents = file_get_contents($this->parameter->cacheFile);
		
		if ($fileContents === false)
			return $returnValue;

		$fileContents = json_decode($fileContents, true);

		$returnValue = $fileContents;

		return $returnValue;
	}

	private function loadMakeModelFromService() {
		$result = "";
		$returnValue = array();

		try {
			$result = file_get_contents('http://www.autoscout24.com/makemodelservice.js.aspx?cultureCode=en-GB&atype=C');
		} catch (Exception $e) {
			return $returnValue;
		}
		
		if (strlen($result) == 0)
			return $returnValue;

		$limit = 50;
		$i = -1;
		$found = false;

		do {

			++$i;
			$char = $result[$i];

			if ($char == "{")
				$found = true;

		} while ($i<$limit && !$found);

		if (!$found)
			return $returnValue;

		$result = substr($result, $i, -1); //Remove the "var .. = " at the begin and the ";" at the end.
		
		$res = json_decode($result);

		if ($res == NULL) //Error while parsing
			return $returnValue;

		$List = array();

		foreach ($res->root->c as $k => $IdMake) { //Cycle all Id of Make

			$NameMake = $res->$IdMake->t;
			$MakeModels = array();

			foreach ($res->$IdMake->c as $k => $IdModel) { //Cycle all models of this Make 

				if (isset($res->$IdModel->c)) { //if the model has child
					foreach ($res->$IdModel->c as $k => $IdModelChild) { // cycle all child of this model (usually only make like BMW "1 - Series", etc.. )
						//$IdModelChild =  str_replace("d", '', $IdModelChild);
						$MakeModels[str_replace("d", '', $IdModelChild)] = $res->$IdModel->t . " - " . $res->$IdModelChild->t;	
					}
				} else {
					//$IdModel =  
					$MakeModels[str_replace("d", '', $IdModel)] = $res->$IdModel->t;	//no child. Add it directly.
				}

			}
			
			$List[str_replace("m", '', $IdMake)] = array(
				"Make"		=> $NameMake,
				"Models"	=> $MakeModels
				);
		}


		if ($this->parameter->saveCacheToDisk) {
			if(is_writable($this->parameter->cacheFile)) {
				file_put_contents($this->parameter->cacheFile, json_encode($List));
			}
		}

		$returnValue = $List;

		return $returnValue;

	}

}
