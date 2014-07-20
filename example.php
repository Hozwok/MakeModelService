<?php

//Include the class file
include "makemodelservice.php";

//Instantiate the parameter class. Optional.
$params = new MakeModelParameter();
$params->locale = MakeModelParameter::LOCALE_IT;

echo "Hi!, Let's initialize our service class!\n";
$service = new MakeModelService($params);
//Alternative...
//$make = new MakeModelService();

$noOfMake = count($service->GetMakeModelList());
echo "I've found " . $noOfMake . " make!\n";

echo "Let's search by Make Name ... Like 'BMW'\n";
$makeId = $service->GetMakeIdByMakeName("Audi"); //Search for BMW .. 

//$makeId will be false if no make found, the id found otherwise
if ($makeId === false) {
	echo "No Make found!!\n";
	echo "Bye!";
	exit;
}

echo "Make Id of BMW found! Its id is ". $makeId . "\n";

echo "Let's search Model Line Data!\n";
// After, we heve to search for the Model Line.
$modelLine = $service->GetModelLineData($makeId, 2012, 1); // Make Id , Year , Month.

echo "I've just found ". count($modelLine) . " models!\n";

//list all model found!
foreach ($modelLine as $k => $value) {
	if($k>0)
		echo ", ";
	echo $value["Model"];
}
echo "\n";

echo "Take the first model line and get its setup!\n";

// $modelLine will be an ampty array if nothing found, otherwise will be a multidimensional associative array.
$firstModel = $modelLine[0]; //Take the first result. 

echo "Model line we are searching for => " . $firstModel["Model"] . "\n";
//Now, we have all information necessary. We can get all info about version / setup of specific model.
$setup = $service->GetVehicleIdentificationData($makeId, $firstModel["ModelID"], 2013, 1, $firstModel["BodyTypeID"],  $firstModel["NoOfDoors"] ); //Make Id, Year, Month, Body Type Id, N. of doors 

echo "Found " . count($setup) . " results.\n";


echo "The first result is:\n";
//var_dump($setup[0]);

foreach ($setup[0] as $property => $value) {
	echo $property . ": ". $value . ", ";
}
echo "\n\nHappy? ;)\n\n";

