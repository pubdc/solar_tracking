<HTML>
<HEAD>
<TITLE>Solar Position Algorithm</TITLE>
</HEAD>
<BODY>
<?php
//port to php from NREL's Solar Position Algorithm (SPA) http://www.nrel.gov/midc/spa/

date_default_timezone_set('UTC');


// INPUT VARIABLES ----------------------------------------------------------------

	//if you want to use actual datetime, use first line
	//in demo, using a fixed datetime, use second line
		$now = time();
		//$now = mktime(19, 30, 30, 10, 17, 2003); //demo = 19, 30, 30, 10, 17, 2003
	echo 'Date : '.date(c,$now).' UTC<BR>'.PHP_EOL;

	//http://www.php.net/manual/en/function.date.php
	$year = date(Y,$now); //year
	$month = date(m,$now); //month 1-12
	$day = date(j,$now); //day 1-31
	$hour = date(G,$now); //hour 0-23
	$minute = date(i,$now); //minutes 00-59
	$second = date(s,$now); //secs 0-59
	$ampm = date(A,$now); //AM-PM
	$timezone = -7 ; //negative west of Greenwich

	$latitude = 26.196190 ;// latitude -90 to 90
	$longitude = 50.466967 ;// longitude -180 to 180
	$elevation = 7 ;//elevation in meters
	$pressure = 1011 ;// average annual pressure in mbar
	$temperature = 35 ;//temperature in degrees Centigrade
	$slope = 0 ;//surface slope in degrees
	$azm_rotation = 0 ; //surface azimuth rotation in degrees, measured from southto projection of surface normal on horizontal plane; negative west
	$atmos_refract = .5667 ;//atmospheric refraction at sunrise and sunset -5 to 5 degrees

//demo values
/*
	$latitude = 39.742476 ;// latitude -90 to 90
	$longitude = -105.1786 ;// longitude -180 to 180
	$elevation = 1830.14 ;//elevation in meters
	$pressure = 820 ;//pressure in mbar
	$temperature = 11 ;//temperature in degrees Centigrade
	$slope = 30 ;//surface slope in degrees
	$azm_rotation = -10 ; //surface azimuth rotation in degrees, measured from southto projection of surface normal on horizontal plane; negative west
	$atmos_refract = .5667 ;//atmospheric refraction at sunrise and sunset -5 to 5 degrees
*/

// CONSTANTS AND LOOKUP TABLES ----------------------------------------------------------------
$PI = 3.1415926535897932384626433832795028841971;
$SUN_RADIUS = 0.26667;

//delta T
	// various functions available on http://www.staff.science.uu.nl/~gent0113/deltat/deltat.htm
	// various functions available on http://user.online.be/felixverbelen/dt.htm
	// http://maia.usno.navy.mil/ser7/deltat.data
	// http://eclipse.gsfc.nasa.gov/SEhelp/deltaT.html
	$delta_t = 67;
	//echo "delta_t = ".$delta_t.'<BR>'.PHP_EOL;

//Earth periodic terms
$ept =
	array
	(
'L00'=>array('a' => 175347046,'b'=>0,'c'=>0),
'L01'=>array('a' => 3341656,'b'=>4.6692568,'c'=>6283.07585),
'L02'=>array('a' => 34894,'b'=>4.6261,'c'=>12566.1517),
'L03'=>array('a' => 3497,'b'=>2.7441,'c'=>5753.3849),
'L04'=>array('a' => 3418,'b'=>2.8289,'c'=>3.5231),
'L05'=>array('a' => 3136,'b'=>3.6277,'c'=>77713.7715),
'L06'=>array('a' => 2676,'b'=>4.4181,'c'=>7860.4194),
'L07'=>array('a' => 2343,'b'=>6.1352,'c'=>3930.2097),
'L08'=>array('a' => 1324,'b'=>0.7425,'c'=>11506.7698),
'L09'=>array('a' => 1273,'b'=>2.0371,'c'=>529.691),
'L010'=>array('a' => 1199,'b'=>1.1096,'c'=>1577.3435),
'L011'=>array('a' => 990,'b'=>5.233,'c'=>5884.927),
'L012'=>array('a' => 902,'b'=>2.045,'c'=>26.298),
'L013'=>array('a' => 857,'b'=>3.508,'c'=>398.149),
'L014'=>array('a' => 780,'b'=>1.179,'c'=>5223.694),
'L015'=>array('a' => 753,'b'=>2.533,'c'=>5507.553),
'L016'=>array('a' => 505,'b'=>4.583,'c'=>18849.228),
'L017'=>array('a' => 492,'b'=>4.205,'c'=>775.523),
'L018'=>array('a' => 357,'b'=>2.92,'c'=>0.067),
'L019'=>array('a' => 317,'b'=>5.849,'c'=>11790.629),
'L020'=>array('a' => 284,'b'=>1.899,'c'=>796.298),
'L021'=>array('a' => 271,'b'=>0.315,'c'=>10977.079),
'L022'=>array('a' => 243,'b'=>0.345,'c'=>5486.778),
'L023'=>array('a' => 206,'b'=>4.806,'c'=>2544.314),
'L024'=>array('a' => 205,'b'=>1.869,'c'=>5573.143),
'L025'=>array('a' => 202,'b'=>2.458,'c'=>6069.777),
'L026'=>array('a' => 156,'b'=>0.833,'c'=>213.299),
'L027'=>array('a' => 132,'b'=>3.411,'c'=>2942.463),
'L028'=>array('a' => 126,'b'=>1.083,'c'=>20.775),
'L029'=>array('a' => 115,'b'=>0.645,'c'=>0.98),
'L030'=>array('a' => 103,'b'=>0.636,'c'=>4694.003),
'L031'=>array('a' => 102,'b'=>0.976,'c'=>15720.839),
'L032'=>array('a' => 102,'b'=>4.267,'c'=>7.114),
'L033'=>array('a' => 99,'b'=>6.21,'c'=>2146.17),
'L034'=>array('a' => 98,'b'=>0.68,'c'=>155.42),
'L035'=>array('a' => 86,'b'=>5.98,'c'=>161000.69),
'L036'=>array('a' => 85,'b'=>1.3,'c'=>6275.96),
'L037'=>array('a' => 85,'b'=>3.67,'c'=>71430.7),
'L038'=>array('a' => 80,'b'=>1.81,'c'=>17260.15),
'L039'=>array('a' => 79,'b'=>3.04,'c'=>12036.46),
'L040'=>array('a' => 75,'b'=>1.76,'c'=>5088.63),
'L041'=>array('a' => 74,'b'=>3.5,'c'=>3154.69),
'L042'=>array('a' => 74,'b'=>4.68,'c'=>801.82),
'L043'=>array('a' => 70,'b'=>0.83,'c'=>9437.76),
'L044'=>array('a' => 62,'b'=>3.98,'c'=>8827.39),
'L045'=>array('a' => 61,'b'=>1.82,'c'=>7084.9),
'L046'=>array('a' => 57,'b'=>2.78,'c'=>6286.6),
'L047'=>array('a' => 56,'b'=>4.39,'c'=>14143.5),
'L048'=>array('a' => 56,'b'=>3.47,'c'=>6279.55),
'L049'=>array('a' => 52,'b'=>0.19,'c'=>12139.55),
'L050'=>array('a' => 52,'b'=>1.33,'c'=>1748.02),
'L051'=>array('a' => 51,'b'=>0.28,'c'=>5856.48),
'L052'=>array('a' => 49,'b'=>0.49,'c'=>1194.45),
'L053'=>array('a' => 41,'b'=>5.37,'c'=>8429.24),
'L054'=>array('a' => 41,'b'=>2.4,'c'=>19651.05),
'L055'=>array('a' => 39,'b'=>6.17,'c'=>10447.39),
'L056'=>array('a' => 37,'b'=>6.04,'c'=>10213.29),
'L057'=>array('a' => 37,'b'=>2.57,'c'=>1059.38),
'L058'=>array('a' => 36,'b'=>1.71,'c'=>2352.87),
'L059'=>array('a' => 36,'b'=>1.78,'c'=>6812.77),
'L060'=>array('a' => 33,'b'=>0.59,'c'=>17789.85),
'L061'=>array('a' => 30,'b'=>0.44,'c'=>83996.85),
'L062'=>array('a' => 30,'b'=>2.74,'c'=>1349.87),
'L063'=>array('a' => 25,'b'=>3.16,'c'=>4690.48),
'L10'=>array('a' => 628331966747,'b'=>0,'c'=>0),
'L11'=>array('a' => 206059,'b'=>2.678235,'c'=>6283.07585),
'L12'=>array('a' => 4303,'b'=>2.6351,'c'=>12566.1517),
'L13'=>array('a' => 425,'b'=>1.59,'c'=>3.523),
'L14'=>array('a' => 119,'b'=>5.796,'c'=>26.298),
'L15'=>array('a' => 109,'b'=>2.966,'c'=>1577.344),
'L16'=>array('a' => 93,'b'=>2.59,'c'=>18849.23),
'L17'=>array('a' => 72,'b'=>1.14,'c'=>529.69),
'L18'=>array('a' => 68,'b'=>1.87,'c'=>398.15),
'L19'=>array('a' => 67,'b'=>4.41,'c'=>5507.55),
'L110'=>array('a' => 59,'b'=>2.89,'c'=>5223.69),
'L111'=>array('a' => 56,'b'=>2.17,'c'=>155.42),
'L112'=>array('a' => 45,'b'=>0.4,'c'=>796.3),
'L113'=>array('a' => 36,'b'=>0.47,'c'=>775.52),
'L114'=>array('a' => 29,'b'=>2.65,'c'=>7.11),
'L115'=>array('a' => 21,'b'=>5.34,'c'=>0.98),
'L116'=>array('a' => 19,'b'=>1.85,'c'=>5486.78),
'L117'=>array('a' => 19,'b'=>4.97,'c'=>213.3),
'L118'=>array('a' => 17,'b'=>2.99,'c'=>6275.96),
'L119'=>array('a' => 16,'b'=>0.03,'c'=>2544.31),
'L120'=>array('a' => 16,'b'=>1.43,'c'=>2146.17),
'L121'=>array('a' => 15,'b'=>1.21,'c'=>10977.08),
'L122'=>array('a' => 12,'b'=>2.83,'c'=>1748.02),
'L123'=>array('a' => 12,'b'=>3.26,'c'=>5088.63),
'L124'=>array('a' => 12,'b'=>5.27,'c'=>1194.45),
'L125'=>array('a' => 12,'b'=>2.08,'c'=>4694),
'L126'=>array('a' => 11,'b'=>0.77,'c'=>553.57),
'L127'=>array('a' => 10,'b'=>1.3,'c'=>6286.6),
'L128'=>array('a' => 10,'b'=>4.24,'c'=>1349.87),
'L129'=>array('a' => 9,'b'=>2.7,'c'=>242.73),
'L130'=>array('a' => 9,'b'=>5.64,'c'=>951.72),
'L131'=>array('a' => 8,'b'=>5.3,'c'=>2352.87),
'L132'=>array('a' => 6,'b'=>2.65,'c'=>9437.76),
'L133'=>array('a' => 6,'b'=>4.67,'c'=>4690.48),
'L20'=>array('a' => 52919,'b'=>0,'c'=>0),
'L21'=>array('a' => 8720,'b'=>1.0721,'c'=>6283.0758),
'L22'=>array('a' => 309,'b'=>0.867,'c'=>12566.152),
'L23'=>array('a' => 27,'b'=>0.05,'c'=>3.52),
'L24'=>array('a' => 16,'b'=>5.19,'c'=>26.3),
'L25'=>array('a' => 16,'b'=>3.68,'c'=>155.42),
'L26'=>array('a' => 10,'b'=>0.76,'c'=>18849.23),
'L27'=>array('a' => 9,'b'=>2.06,'c'=>77713.77),
'L28'=>array('a' => 7,'b'=>0.83,'c'=>775.52),
'L29'=>array('a' => 5,'b'=>4.66,'c'=>1577.34),
'L210'=>array('a' => 4,'b'=>1.03,'c'=>7.11),
'L211'=>array('a' => 4,'b'=>3.44,'c'=>5573.14),
'L212'=>array('a' => 3,'b'=>5.14,'c'=>796.3),
'L213'=>array('a' => 3,'b'=>6.05,'c'=>5507.55),
'L214'=>array('a' => 3,'b'=>1.19,'c'=>242.73),
'L215'=>array('a' => 3,'b'=>6.12,'c'=>529.69),
'L216'=>array('a' => 3,'b'=>0.31,'c'=>398.15),
'L217'=>array('a' => 3,'b'=>2.28,'c'=>553.57),
'L218'=>array('a' => 2,'b'=>4.38,'c'=>5223.69),
'L219'=>array('a' => 2,'b'=>3.75,'c'=>0.98),
'L30'=>array('a' => 289,'b'=>5.844,'c'=>6283.076),
'L31'=>array('a' => 35,'b'=>0,'c'=>0),
'L32'=>array('a' => 17,'b'=>5.49,'c'=>12566.15),
'L33'=>array('a' => 3,'b'=>5.2,'c'=>155.42),
'L34'=>array('a' => 1,'b'=>4.72,'c'=>3.52),
'L35'=>array('a' => 1,'b'=>5.3,'c'=>18849.23),
'L36'=>array('a' => 1,'b'=>5.97,'c'=>242.73),
'L40'=>array('a' => 114,'b'=>3.142,'c'=>0),
'L41'=>array('a' => 8,'b'=>4.13,'c'=>6283.08),
'L42'=>array('a' => 1,'b'=>3.84,'c'=>12566.15),
'L50'=>array('a' => 1,'b'=>3.14,'c'=>0),
'B00'=>array('a' => 280,'b'=>3.199,'c'=>84334.662),
'B01'=>array('a' => 102,'b'=>5.422,'c'=>5507.553),
'B02'=>array('a' => 80,'b'=>3.88,'c'=>5223.69),
'B03'=>array('a' => 44,'b'=>3.7,'c'=>2352.87),
'B04'=>array('a' => 32,'b'=>4,'c'=>1577.34),
'B10'=>array('a' => 9,'b'=>3.9,'c'=>5507.55),
'B11'=>array('a' => 6,'b'=>1.73,'c'=>5223.69),
'R00'=>array('a' => 100013989,'b'=>0,'c'=>0),
'R01'=>array('a' => 1670700,'b'=>3.0984635,'c'=>6283.07585),
'R02'=>array('a' => 13956,'b'=>3.05525,'c'=>12566.1517),
'R03'=>array('a' => 3084,'b'=>5.1985,'c'=>77713.7715),
'R04'=>array('a' => 1628,'b'=>1.1739,'c'=>5753.3849),
'R05'=>array('a' => 1576,'b'=>2.8469,'c'=>7860.4194),
'R06'=>array('a' => 925,'b'=>5.453,'c'=>11506.77),
'R07'=>array('a' => 542,'b'=>4.564,'c'=>3930.21),
'R08'=>array('a' => 472,'b'=>3.661,'c'=>5884.927),
'R09'=>array('a' => 346,'b'=>0.964,'c'=>5507.553),
'R010'=>array('a' => 329,'b'=>5.9,'c'=>5223.694),
'R011'=>array('a' => 307,'b'=>0.299,'c'=>5573.143),
'R012'=>array('a' => 243,'b'=>4.273,'c'=>11790.629),
'R013'=>array('a' => 212,'b'=>5.847,'c'=>1577.344),
'R014'=>array('a' => 186,'b'=>5.022,'c'=>10977.079),
'R015'=>array('a' => 175,'b'=>3.012,'c'=>18849.228),
'R016'=>array('a' => 110,'b'=>5.055,'c'=>5486.778),
'R017'=>array('a' => 98,'b'=>0.89,'c'=>6069.78),
'R018'=>array('a' => 86,'b'=>5.69,'c'=>15720.84),
'R019'=>array('a' => 86,'b'=>1.27,'c'=>161000.69),
'R020'=>array('a' => 65,'b'=>0.27,'c'=>17260.15),
'R021'=>array('a' => 63,'b'=>0.92,'c'=>529.69),
'R022'=>array('a' => 57,'b'=>2.01,'c'=>83996.85),
'R023'=>array('a' => 56,'b'=>5.24,'c'=>71430.7),
'R024'=>array('a' => 49,'b'=>3.25,'c'=>2544.31),
'R025'=>array('a' => 47,'b'=>2.58,'c'=>775.52),
'R026'=>array('a' => 45,'b'=>5.54,'c'=>9437.76),
'R027'=>array('a' => 43,'b'=>6.01,'c'=>6275.96),
'R028'=>array('a' => 39,'b'=>5.36,'c'=>4694),
'R029'=>array('a' => 38,'b'=>2.39,'c'=>8827.39),
'R030'=>array('a' => 37,'b'=>0.83,'c'=>19651.05),
'R031'=>array('a' => 37,'b'=>4.9,'c'=>12139.55),
'R032'=>array('a' => 36,'b'=>1.67,'c'=>12036.46),
'R033'=>array('a' => 35,'b'=>1.84,'c'=>2942.46),
'R034'=>array('a' => 33,'b'=>0.24,'c'=>7084.9),
'R035'=>array('a' => 32,'b'=>0.18,'c'=>5088.63),
'R036'=>array('a' => 32,'b'=>1.78,'c'=>398.15),
'R037'=>array('a' => 28,'b'=>1.21,'c'=>6286.6),
'R038'=>array('a' => 28,'b'=>1.9,'c'=>6279.55),
'R039'=>array('a' => 26,'b'=>4.59,'c'=>10447.39),
'R10'=>array('a' => 103019,'b'=>1.10749,'c'=>6283.07585),
'R11'=>array('a' => 1721,'b'=>1.0644,'c'=>12566.1517),
'R12'=>array('a' => 702,'b'=>3.142,'c'=>0),
'R13'=>array('a' => 32,'b'=>1.02,'c'=>18849.23),
'R14'=>array('a' => 31,'b'=>2.84,'c'=>5507.55),
'R15'=>array('a' => 25,'b'=>1.32,'c'=>5223.69),
'R16'=>array('a' => 18,'b'=>1.42,'c'=>1577.34),
'R17'=>array('a' => 10,'b'=>5.91,'c'=>10977.08),
'R18'=>array('a' => 9,'b'=>1.42,'c'=>6275.96),
'R19'=>array('a' => 9,'b'=>0.27,'c'=>5486.78),
'R20'=>array('a' => 4359,'b'=>5.7846,'c'=>6283.0758),
'R21'=>array('a' => 124,'b'=>5.579,'c'=>12566.152),
'R22'=>array('a' => 12,'b'=>3.14,'c'=>0),
'R23'=>array('a' => 9,'b'=>3.63,'c'=>77713.77),
'R24'=>array('a' => 6,'b'=>1.87,'c'=>5573.14),
'R25'=>array('a' => 3,'b'=>5.47,'c'=>18849.23),
'R30'=>array('a' => 145,'b'=>4.273,'c'=>6283.076),
'R31'=>array('a' => 7,'b'=>3.92,'c'=>12566.15),
'R40'=>array('a' => 4,'b'=>2.56,'c'=>6283.08)
	);

# define Periodic Terms Nutation Longitude and Obliquity
    
$ptnlo =
	array
	(
'1'=>array('y0' => 0,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>1,'a'=>-171996,'b'=>-174.2,'c'=>92025,'d'=>8.9),
'2'=>array('y0' => -2,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-13187,'b'=>-1.6,'c'=>5736,'d'=>-3.1),
'3'=>array('y0' => 0,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-2274,'b'=>-0.2,'c'=>977,'d'=>-0.5),
'4'=>array('y0' => 0,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>2,'a'=>2062,'b'=>0.2,'c'=>-895,'d'=>0.5),
'5'=>array('y0' => 0,'y1'=>1,'y2'=>0,'y3'=>0,'y4'=>0,'a'=>1426,'b'=>-3.4,'c'=>54,'d'=>-0.1),
'6'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>712,'b'=>0.1,'c'=>-7,'d'=>0),
'7'=>array('y0' => -2,'y1'=>1,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-517,'b'=>1.2,'c'=>224,'d'=>-0.6),
'8'=>array('y0' => 0,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>1,'a'=>-386,'b'=>-0.4,'c'=>200,'d'=>0),
'9'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>2,'a'=>-301,'b'=>0,'c'=>129,'d'=>-0.1),
'10'=>array('y0' => -2,'y1'=>-1,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>217,'b'=>-0.5,'c'=>-95,'d'=>0.3),
'11'=>array('y0' => -2,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>-158,'b'=>0,'c'=>0,'d'=>0),
'12'=>array('y0' => -2,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>1,'a'=>129,'b'=>0.1,'c'=>-70,'d'=>0),
'13'=>array('y0' => 0,'y1'=>0,'y2'=>-1,'y3'=>2,'y4'=>2,'a'=>123,'b'=>0,'c'=>-53,'d'=>0),
'14'=>array('y0' => 2,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>0,'a'=>63,'b'=>0,'c'=>0,'d'=>0),
'15'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>1,'a'=>63,'b'=>0.1,'c'=>-33,'d'=>0),
'16'=>array('y0' => 2,'y1'=>0,'y2'=>-1,'y3'=>2,'y4'=>2,'a'=>-59,'b'=>0,'c'=>26,'d'=>0),
'17'=>array('y0' => 0,'y1'=>0,'y2'=>-1,'y3'=>0,'y4'=>1,'a'=>-58,'b'=>-0.1,'c'=>32,'d'=>0),
'18'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>1,'a'=>-51,'b'=>0,'c'=>27,'d'=>0),
'19'=>array('y0' => -2,'y1'=>0,'y2'=>2,'y3'=>0,'y4'=>0,'a'=>48,'b'=>0,'c'=>0,'d'=>0),
'20'=>array('y0' => 0,'y1'=>0,'y2'=>-2,'y3'=>2,'y4'=>1,'a'=>46,'b'=>0,'c'=>-24,'d'=>0),
'21'=>array('y0' => 2,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-38,'b'=>0,'c'=>16,'d'=>0),
'22'=>array('y0' => 0,'y1'=>0,'y2'=>2,'y3'=>2,'y4'=>2,'a'=>-31,'b'=>0,'c'=>13,'d'=>0),
'23'=>array('y0' => 0,'y1'=>0,'y2'=>2,'y3'=>0,'y4'=>0,'a'=>29,'b'=>0,'c'=>0,'d'=>0),
'24'=>array('y0' => -2,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>2,'a'=>29,'b'=>0,'c'=>-12,'d'=>0),
'25'=>array('y0' => 0,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>0,'a'=>26,'b'=>0,'c'=>0,'d'=>0),
'26'=>array('y0' => -2,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>0,'a'=>-22,'b'=>0,'c'=>0,'d'=>0),
'27'=>array('y0' => 0,'y1'=>0,'y2'=>-1,'y3'=>2,'y4'=>1,'a'=>21,'b'=>0,'c'=>-10,'d'=>0),
'28'=>array('y0' => 0,'y1'=>2,'y2'=>0,'y3'=>0,'y4'=>0,'a'=>17,'b'=>-0.1,'c'=>0,'d'=>0),
'29'=>array('y0' => 2,'y1'=>0,'y2'=>-1,'y3'=>0,'y4'=>1,'a'=>16,'b'=>0,'c'=>-8,'d'=>0),
'30'=>array('y0' => -2,'y1'=>2,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-16,'b'=>0.1,'c'=>7,'d'=>0),
'31'=>array('y0' => 0,'y1'=>1,'y2'=>0,'y3'=>0,'y4'=>1,'a'=>-15,'b'=>0,'c'=>9,'d'=>0),
'32'=>array('y0' => -2,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>1,'a'=>-13,'b'=>0,'c'=>7,'d'=>0),
'33'=>array('y0' => 0,'y1'=>-1,'y2'=>0,'y3'=>0,'y4'=>1,'a'=>-12,'b'=>0,'c'=>6,'d'=>0),
'34'=>array('y0' => 0,'y1'=>0,'y2'=>2,'y3'=>-2,'y4'=>0,'a'=>11,'b'=>0,'c'=>0,'d'=>0),
'35'=>array('y0' => 2,'y1'=>0,'y2'=>-1,'y3'=>2,'y4'=>1,'a'=>-10,'b'=>0,'c'=>5,'d'=>0),
'36'=>array('y0' => 2,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>2,'a'=>-8,'b'=>0,'c'=>3,'d'=>0),
'37'=>array('y0' => 0,'y1'=>1,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>7,'b'=>0,'c'=>-3,'d'=>0),
'38'=>array('y0' => -2,'y1'=>1,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>-7,'b'=>0,'c'=>0,'d'=>0),
'39'=>array('y0' => 0,'y1'=>-1,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-7,'b'=>0,'c'=>3,'d'=>0),
'40'=>array('y0' => 2,'y1'=>0,'y2'=>0,'y3'=>2,'y4'=>1,'a'=>-7,'b'=>0,'c'=>3,'d'=>0),
'41'=>array('y0' => 2,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>6,'b'=>0,'c'=>0,'d'=>0),
'42'=>array('y0' => -2,'y1'=>0,'y2'=>2,'y3'=>2,'y4'=>2,'a'=>6,'b'=>0,'c'=>-3,'d'=>0),
'43'=>array('y0' => -2,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>1,'a'=>6,'b'=>0,'c'=>-3,'d'=>0),
'44'=>array('y0' => 2,'y1'=>0,'y2'=>-2,'y3'=>0,'y4'=>1,'a'=>-6,'b'=>0,'c'=>3,'d'=>0),
'45'=>array('y0' => 2,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>1,'a'=>-6,'b'=>0,'c'=>3,'d'=>0),
'46'=>array('y0' => 0,'y1'=>-1,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>5,'b'=>0,'c'=>0,'d'=>0),
'47'=>array('y0' => -2,'y1'=>-1,'y2'=>0,'y3'=>2,'y4'=>1,'a'=>-5,'b'=>0,'c'=>3,'d'=>0),
'48'=>array('y0' => -2,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>1,'a'=>-5,'b'=>0,'c'=>3,'d'=>0),
'49'=>array('y0' => 0,'y1'=>0,'y2'=>2,'y3'=>2,'y4'=>1,'a'=>-5,'b'=>0,'c'=>3,'d'=>0),
'50'=>array('y0' => -2,'y1'=>0,'y2'=>2,'y3'=>0,'y4'=>1,'a'=>4,'b'=>0,'c'=>0,'d'=>0),
'51'=>array('y0' => -2,'y1'=>1,'y2'=>0,'y3'=>2,'y4'=>1,'a'=>4,'b'=>0,'c'=>0,'d'=>0),
'52'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>-2,'y4'=>0,'a'=>4,'b'=>0,'c'=>0,'d'=>0),
'53'=>array('y0' => -1,'y1'=>0,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>-4,'b'=>0,'c'=>0,'d'=>0),
'54'=>array('y0' => -2,'y1'=>1,'y2'=>0,'y3'=>0,'y4'=>0,'a'=>-4,'b'=>0,'c'=>0,'d'=>0),
'55'=>array('y0' => 1,'y1'=>0,'y2'=>0,'y3'=>0,'y4'=>0,'a'=>-4,'b'=>0,'c'=>0,'d'=>0),
'56'=>array('y0' => 0,'y1'=>0,'y2'=>1,'y3'=>2,'y4'=>0,'a'=>3,'b'=>0,'c'=>0,'d'=>0),
'57'=>array('y0' => 0,'y1'=>0,'y2'=>-2,'y3'=>2,'y4'=>2,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'58'=>array('y0' => -1,'y1'=>-1,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'59'=>array('y0' => 0,'y1'=>1,'y2'=>1,'y3'=>0,'y4'=>0,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'60'=>array('y0' => 0,'y1'=>-1,'y2'=>1,'y3'=>2,'y4'=>2,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'61'=>array('y0' => 2,'y1'=>-1,'y2'=>-1,'y3'=>2,'y4'=>2,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'62'=>array('y0' => 0,'y1'=>0,'y2'=>3,'y3'=>2,'y4'=>2,'a'=>-3,'b'=>0,'c'=>0,'d'=>0),
'63'=>array('y0' => 2,'y1'=>-1,'y2'=>0,'y3'=>2,'y4'=>2,'a'=>-3,'b'=>0,'c'=>0,'d'=>0)
	);

	//print_r2($ptnlo);
	//print_r2($ptnlo2);

	//echo 'test ept = ' . ($ept['L01'][a]).'<BR>'.PHP_EOL;
	//echo 'test ptnlo = ' .($ptnlo[1][a]).'<BR>'.PHP_EOL;

// PROCEDURE ----------------------------------------------------------------

// 3.1 JULIAN DAY ----------------------------------------------------------------

$day_decimal = $day + ($hour*3600+$minute*60+$second)/(24*3600);

if ($month<3) 
	{
	$Y=$year-1;
	$M=$month+12;
	}
	else
	{
	$Y=$year;
	$M=$month;
	}

$a= (int)($Y/100);
$b = (2- $a + (int)($a/4));
$JD = (int)(365.25*($Y+4716))+(int)(30.6001*($M+1))+$day_decimal+$b-1524.5;
	//echo "JD = ".$JD.'<BR>'.PHP_EOL;
$JDE = $JD + ($delta_t / 86400);
	//echo "JDE = ".$JDE.'<BR>'.PHP_EOL;
$JC = ($JD - 2451545)/36525;
	//echo "JC = ".$JC.'<BR>'.PHP_EOL;
$JCE = ($JDE - 2451545)/36525;
	//echo "JCE = ".$JCE.'<BR>'.PHP_EOL;
$JME = $JCE/10;
	//echo "JME = ".$JME.'<BR>'.PHP_EOL;

// 3.2 earth heliocentric lon, lat and radius L, B and R  --------------------------------------------------------------------

$L0 = sum_ept ('L0',63,$ept,$JME);
	//echo "L0 = ".$L0.'<BR>'.PHP_EOL;
$L1 = sum_ept ('L1',33,$ept,$JME);
	//echo "L1 = ".$L1.'<BR>'.PHP_EOL;
$L2 = sum_ept ('L2',19,$ept,$JME);
	//echo "L2 = ".$L2.'<BR>'.PHP_EOL;
$L3 = sum_ept ('L3',6,$ept,$JME);
	//echo "L3 = ".$L3.'<BR>'.PHP_EOL;
$L4 = sum_ept ('L4',2,$ept,$JME);
	//echo "L4 = ".$L4.'<BR>'.PHP_EOL;
$L5 = $ept['L50'][a]*cos($ept['L50'][b]+$ept['L50'][c]*$JME);
	//echo "L5 = ".$L5.'<BR>'.PHP_EOL;
$L_rad = ($L0 + $L1 * $JME + $L2 * pow($JME,2) + $L3 * pow($JME,3) + $L4 * pow($JME,4) + $L5 * pow($JME,5) )/pow(10,8);
$L_deg = fmod(rad2deg($L_rad),360);
if($L_deg<0) $L_deg = $L_deg+360;
	//echo "L_deg = ".$L_deg.'<BR>'.PHP_EOL;

$B0 = sum_ept ('B0',4,$ept,$JME);
	//echo "B0 = ".$B0.'<BR>'.PHP_EOL;
$B1 = sum_ept ('B1',1,$ept,$JME);
	//echo "B1 = ".$B1.'<BR>'.PHP_EOL;
$B_rad = ($B0 + $B1 * $JME)/pow(10,8);
$B_deg = fmod(rad2deg($B_rad),360);
if($B_deg<0) $B_deg = $B_deg+360;
	//echo "B_deg = ".$B_deg.'<BR>'.PHP_EOL;
		
$R0 = sum_ept ('R0',39,$ept,$JME);
	//echo "R0 = ".$R0.'<BR>'.PHP_EOL;
$R1 = sum_ept ('R1',9,$ept,$JME);
	//echo "R1 = ".$R1.'<BR>'.PHP_EOL;
$R2 = sum_ept ('R2',5,$ept,$JME);
	//echo "R2 = ".$R2.'<BR>'.PHP_EOL;
$R3 = sum_ept ('R3',1,$ept,$JME);
	//echo "R3 = ".$R3.'<BR>'.PHP_EOL;
$R4 = $ept['R40'][a]*cos($ept['R40'][b]+$ept['R40'][c]*$JME);
	//echo "R4 = ".$R4.'<BR>'.PHP_EOL;
$R_AU = ($R0 + $R1 * $JME + $R2 * pow($JME,2) + $R3 * pow($JME,3) + $R4 * pow($JME,4))/pow(10,8);
	//echo "R_AU = ".$R_AU.'<BR>'.PHP_EOL;

// 3.3 geocentric lon and lat  ------------------------------------------------------------------------------------------------------

$geocentric_lon_deg = fmod($L_deg+180, 360);
if($geocentric_lon_deg<0) $geocentric_lon_deg = $geocentric_lon_deg+360;
	//echo "O = ".$geocentric_lon_deg.'<BR>'.PHP_EOL;
$geocentric_lat_deg = -$B_deg;   // of moet ik hier ook een fmod doen ?????????????????????
	//echo "B = ".$geocentric_lat_deg.'<BR>'.PHP_EOL;
	
// 3.4 Calculate the nutation in longitude and obliquity ()R and )g) ---------------------------------------------------

$X0_deg= 297.85036 + 445267.111480 * $JCE - 0.0019142 * pow($JCE,2)  + pow($JCE,3) / 189474;
	//echo "X0 = ".$X0_deg.'<BR>'.PHP_EOL;
$X1_deg= 357.52772 + 35999.050340 * $JCE - 0.0001603 * pow($JCE,2)  - pow($JCE,3) / 300000;
	//echo "X1 = ".$X1_deg.'<BR>'.PHP_EOL;
$X2_deg= 134.96298 + 477198.867398 * $JCE + 0.0086972 * pow($JCE,2)  + pow($JCE,3) / 56250;
	//echo "X2 = ".$X2_deg.'<BR>'.PHP_EOL;
$X3_deg= 93.27191 + 483202.017538 * $JCE - 0.0036825 * pow($JCE,2)  + pow($JCE,3) / 327270;
	//echo "X3 = ".$X3_deg.'<BR>'.PHP_EOL;
$X4_deg= 125.04452 - 1934.136261 * $JCE + 0.0020708 * pow($JCE,2)  + pow($JCE,3) / 450000;
	//echo "X4 = ".$X4_deg.'<BR>'.PHP_EOL;

$dWi = 0;
$dEi = 0;
for($i=1; $i <= 63; $i++)
	{
	$tempprod = 0;
	$tempprod = $X0_deg * $ptnlo[$i][y0] + $X1_deg * $ptnlo[$i][y1] + $X2_deg * $ptnlo[$i][y2] + $X3_deg * $ptnlo[$i][y3] + $X4_deg * $ptnlo[$i][y4];
	$tempprod=fmod($tempprod,360);
	$tempprod = deg2rad($tempprod);
		//echo "tempprod = ".$tempprod.'<BR>'.PHP_EOL;
	$dWi += ($ptnlo[$i][a]+$ptnlo[$i][b]*$JCE)  *sin($tempprod);
	$dEi += ($ptnlo[$i][c]+$ptnlo[$i][d]*$JCE)  *cos($tempprod);
	}

$dW_deg = $dWi / 36000000;
	//echo "dW = ".$dW_deg.'<BR>'.PHP_EOL;
$dE_deg = $dEi / 36000000;
	//echo "dE = ".$dE_deg.'<BR>'.PHP_EOL;

// 3.5 Calculate the true obliquity of the ecliptic, g (in degrees) -------------------------------------------------------

$U = $JME/10;
$E0 = 84381.448 - 4680.93 *$U - 1.55 * pow($U,2) + 1999.25 * pow($U,3) - 51.38 * pow($U,4) - 249.67 * pow($U,5);
$E0 += - 39.05 * pow($U,6) + 7.12 * pow($U,7) + 27.87 * pow($U,8) + 5.79 * pow($U,9) + 2.45 * pow($U,10) ;
	//echo "E0 = ".$E0.'<BR>'.PHP_EOL;

$E = $E0/3600 + $dE_deg;
	//echo "E = ".$E.'<BR>'.PHP_EOL;

// 3.6 Calculate the aberration correction, )J (in degrees) ----------------------------------------------------------------

$dT = -20.4898 / (3600 * $R_AU);
	//echo "dT = ".$dT.'<BR>'.PHP_EOL;

// 3.7 Calculate the apparent sun longitude, Lambda (in degrees) -------------------------------------------------------------

$app_sun_lon = $geocentric_lon_deg + $dW_deg + $dT ;
	//echo "Lambda = ".$app_sun_lon.'<BR>'.PHP_EOL;

// 3.8 Calculate the apparent sidereal time at Greenwich at any given time, Nu (in degrees):------------------

$mean_sidereal_time = 280.46061837 + 360.98564736629 * ($JD - 2451545) + 0.000387933 * pow($JC,2) - pow($JC,3) /38710000;
$mean_sidereal_time = fmod($mean_sidereal_time,360);
if($mean_sidereal_time<0) $mean_sidereal_time = $mean_sidereal_time+360;
	//echo "mean sid time = ".$mean_sidereal_time.'<BR>'.PHP_EOL;
$app_sidereal_time = $mean_sidereal_time + $dW_deg * cos(deg2rad($E));
	//echo "app sid time = ".$app_sidereal_time.'<BR>'.PHP_EOL;

// 3.9 Calculate the geocentric sun right ascension, alpha (in degrees): ---------------------------------------------------

$geo_sun_ra = atan2(sin(deg2rad($app_sun_lon))*cos(deg2rad($E))-tan(deg2rad($geocentric_lat_deg))*sin(deg2rad($E)),cos(deg2rad($app_sun_lon)));
$geo_sun_ra = rad2deg($geo_sun_ra);
$geo_sun_ra = fmod($geo_sun_ra,360);
if($geo_sun_ra<0) $geo_sun_ra = $geo_sun_ra+360;
	//echo "alpha = ".$geo_sun_ra.'<BR>'.PHP_EOL;

// 3.10 Calculate the geocentric sun declination, delta (in degrees): ------------------------------------------------------

$geo_sun_dec = asin(sin(deg2rad($geocentric_lat_deg))*cos(deg2rad($E))+cos(deg2rad($geocentric_lat_deg))*sin(deg2rad($E))*sin(deg2rad($app_sun_lon)));
$geo_sun_dec = rad2deg($geo_sun_dec);
	//echo "delta = ".$geo_sun_dec.'<BR>'.PHP_EOL;

// 3.11 Calculate the observer local hour angle, H (in degrees)-------------------------------------------------------

$H_deg = $app_sidereal_time + $longitude  - $geo_sun_ra;
$H_deg = fmod($H_deg,360);
if($H_deg<0) $H_deg = $H_deg+360;
	//echo "H = ".$H_deg.'<BR>'.PHP_EOL;

// 3.12 Calculate the topocentric sun right ascension "’ (in degrees):-------------------------------------------------

$eq_hor_par_deg = 8.794 / (3600 * $R_AU);
	//echo "eq_hor_par_deg = ".$eq_hor_par_deg.'<BR>'.PHP_EOL;
$term_u = atan(0.99664719*tan(deg2rad($latitude)));
$term_x = cos($term_u)+ ($elevation/6378140) * cos(deg2rad($latitude));
$term_y = .99664719 * sin($term_u) +  ($elevation/6378140) * sin(deg2rad($latitude));
$par_sun_ra = atan2(-$term_x *  sin(deg2rad($eq_hor_par_deg))*  sin(deg2rad($H_deg)),cos(deg2rad($geo_sun_dec))-$term_x* sin(deg2rad($eq_hor_par_deg))*  cos(deg2rad($H_deg)));
$par_sun_ra = rad2deg($par_sun_ra);
	//echo "par_sun_ra = ".$par_sun_ra.'<BR>'.PHP_EOL;
$top_sun_ra = $geo_sun_ra + $par_sun_ra;
	//echo "top_sun_ra = ".$top_sun_ra.'<BR>'.PHP_EOL;
$top_sun_dec = atan2((sin(deg2rad($geo_sun_dec))-$term_y*sin(deg2rad($eq_hor_par_deg)))*cos(deg2rad($par_sun_ra)),cos(deg2rad($geo_sun_dec))-$term_x * sin(deg2rad($eq_hor_par_deg)) * cos(deg2rad($H_deg)));
$top_sun_dec = rad2deg($top_sun_dec);
	//echo "top_sun_dec = ".$top_sun_dec.'<BR>'.PHP_EOL;

// 3.13 Calculate the topocentric local hour angle, H’ (in degrees) ----------------------------------------------------

$topo_lha_deg = $H_deg -  $par_sun_ra;
	//echo "topo_lha_deg = ".$topo_lha_deg.'<BR>'.PHP_EOL;

// 3.14 Calculate the topocentric zenith angle, theta (in degrees) ----------------------------------------------------------

$top_elev_noarc = asin(sin(deg2rad($latitude))*sin(deg2rad($top_sun_dec))+cos(deg2rad($latitude))*cos(deg2rad($top_sun_dec))*cos(deg2rad($topo_lha_deg)));
$top_elev_noarc = rad2deg($top_elev_noarc);
	//echo "top_elev_noarc = ".$top_elev_noarc.'<BR>'.PHP_EOL;

$arc = ($pressure/1010) * (283 / (273+$temperature))*(1.02/(60*tan(deg2rad($top_elev_noarc+(10.3/($top_elev_noarc+5.11))))));
	//echo "arc = ".$arc.'<BR>'.PHP_EOL;

$topo_elev_deg = $top_elev_noarc + $arc;
	echo "<B>topo_elev_deg = ".round($topo_elev_deg,1).'</B><BR>'.PHP_EOL;
$topo_zenith_deg = 90 - $topo_elev_deg;
	echo "<B>topo_zenith_deg = ".round($topo_zenith_deg,1).'</B><BR>'.PHP_EOL;
	
// 3.15 Calculate the topocentric azimuth angle, M (in degrees) -------------------------------------------------------

$astro_azi = atan2(sin(deg2rad($topo_lha_deg)),cos(deg2rad($topo_lha_deg))*sin(deg2rad($latitude))-tan(deg2rad($top_sun_dec))*cos(deg2rad($latitude)));
$astro_azi = rad2deg($astro_azi);
$astro_azi = fmod($astro_azi,360);
if($astro_azi<0) $astro_azi = $astro_azi+360;

$topo_azi = $astro_azi + 180;
$topo_azi = fmod($topo_azi,360);
	echo "<B>topo_azi = ".round($topo_azi,1).'</B><BR>'.PHP_EOL;

// 3.16 Calculate the incidence angle for a surface oriented in any direction, I (in degrees) -------------------

$incidence = acos(cos(deg2rad($topo_zenith_deg))*cos(deg2rad($slope))+sin(deg2rad($slope))*sin(deg2rad($topo_zenith_deg))*cos(deg2rad($astro_azi- $azm_rotation )));
$incidence = rad2deg($incidence);
$incidence = fmod($incidence,360);
if($incidence<0) $incidence = $incidence+360;
	//echo "<B>incidence = ".$incidence.'</B><BR>'.PHP_EOL;


//functions ----------------------------------------------------------------------------------------------------------------------------------
function sum_ept ($term,$iters,$ept,$JME) 
	{
	$tempsum = 0 ;
	for($i=0; $i <= $iters; $i++)
		{$ref = $term.$i;
		$tempsum += $ept[$ref][a]*cos($ept[$ref][b]+$ept[$ref][c]*$JME);
		}
	return $tempsum;
	}

function print_r2($val)
	{
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
	}

// references ------------------------------------------------------------------------------------------------------------------------------
	// http://www.php.net/manual/en/ref.math.php
	// online calculator including intermediate outputs : http://www.nrel.gov/midc/solpos/spa.html 
?>
</BODY>
</HTML>
