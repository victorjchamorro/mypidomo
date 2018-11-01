<?php
require_once './lib/aemet.class.php';
ini_set('display_errors',1);
error_reporting(E_ALL);
function addRoute($file){
	if (is_file($file)){
		return $file.'?'.filemtime($file);
	}else{
		throw Excepction("Not found: $file");
	}
}
function tempParts($temp, $index) {
	$parts = explode('.', number_format($temp, 1));
	return $parts[$index];
}
if (isset($_GET['module'])){
	switch($_GET['module']){
		case 'api_temp':
			$datos=$aemet=aemet::init('3343Y')->getTemperatura();
			echo json_encode(array(
				'temp'=>$datos,
				'entero'=>tempParts($datos,0),
				'decimal'=>tempParts($datos,1),
				'humedad'=>aemet::init('3343Y')->getHumedad()
			));
			die();
		break;
	}
}
$aemet=aemet::init('3343Y');
?><!DOCTYPE html>
<html>
	<head>
		<title>Temperatures</title>
		<link rel="stylesheet" type="text/css" href="<?php echo addRoute('./css/style.css');?>" />
		<link href="https://fonts.googleapis.com/css?family=Dosis" rel="stylesheet">
		<link href="./fontawesome/css/all.min.css" rel="stylesheet">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<script src="./js/jquery-1.9.1.min.js"></script>
		<!--<script src="./amcharts/amcharts.js" type="text/javascript"></script> -->
		<script type="text/javascript" src="<?php echo addRoute('./js/common.js');?>"></script>
		
	</head>
	<body>
		<div class="content windowMain">
			<div class="thermometers">
				<div class="label">Salón</div><div class="label">Exterior</div>
				<div class="de sensorUno">
					<div class="den">
					  <div class="dene">
						<div class="denem">
						  <div class="deneme">
							<span class="temp1">--</span><span class="temp2">.-</span><strong>&deg;</strong>
						  </div>
						</div>
					  </div>
					</div>
				</div>
				<div class="de sensorDos">
					<div class="den">
					  <div class="dene">
						<div class="denem">
						  <div class="deneme">
							<span class="temp1">--</span><span class="temp2">.-</span><strong>&deg;</strong>
						  </div>
						</div>
					  </div>
					</div>
				</div>
				<div class="label humedad1"></div><div class="label humedad2"></div>
				<div class="date"><span class="hour"></span> <span class="date"></span></div>
			</div>
			
			
		</div>
		
		<div class="content windowAjustTemp">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<button class="btnSmall btnRefreshApp"><i class="fas fa-sync-alt"></i></button>
			
			<div class="tempAjust dia">
				<button class="btn btnUp"><i class="fas fa-caret-up"></i></button>
				<input type="number" value="20" name="tempDia" />
				<button class="btn btnDown"><i class="fas fa-caret-down"></i></button>
			</div>
			
			<div class="tempAjust noche">
				<button class="btn btnUp"><i class="fas fa-caret-up"></i></button>
				<input type="number" value="18" name="tempNoche" />
				<button class="btn btnDown"><i class="fas fa-caret-down"></i></button>
			</div>
			
			<div class="dayAjust">
				<button class="btn btnDay">L</button>
				<button class="btn btnDay">M</button>
				<button class="btn btnDay">X</button>
				<button class="btn btnDay">J</button>
				<button class="btn btnDay">V</button>
				<button class="btn btnDay">S</button>
				<button class="btn btnDay">D</button>
			</div>
		</div>
		
		<div class="content windowPredicionAemet">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<br><br><br><br>
			<script type='text/javascript' src='http://www.aemet.es/es/eltiempo/prediccion/municipios/launchwidget/sevilla-la-nueva-id28141?w=g4p01110001ohmffffffw600z190x4f86d9t95b6e9r1s8n2'></script>
		</div>
	</body>
</html>
