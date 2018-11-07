<?php
require_once './lib/aemet.class.php';
require_once './lib/gpio.class.php';
require_once './lib/UTLIni.php';
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
		case 'rele_on':
			gpio::write('20','1');
			die();
		case 'rele_off':
			gpio::write('20','0');
			die();
		case 'rele_status':
			echo gpio::read('20');
			die();
		case 'api_temp':
			$datos=$aemet=aemet::init('3343Y')->getTemperatura();
			header('Content-Type: application/json');
			echo json_encode(array(
				'temp'=>$datos,
				'entero'=>tempParts($datos,0),
				'decimal'=>tempParts($datos,1),
				'humedad'=>aemet::init('3343Y')->getHumedad()
			));
			die();
		case 'halt':
			if ($_SERVER['REMOTE_ADDR']==$_SERVER['SERVER_ADDR']){
				echo system('sudo /sbin/halt');
				echo "apagando...";
			}else{
				echo $_SERVER['REMOTE_ADDR']."<br>";
				echo $_SERVER['SERVER_ADDR'];
			}
			die();
		case 'getDataTemp':
			UTLIni::addIniFile('./data/temp.ini','TEMP');
			header('Content-Type: application/json');
			echo json_encode(UTLIni::$conf['TEMP']);
			die();
		case 'setDataTemp':
			UTLIni::addIniFile('./data/temp.ini','TEMP');
			UTLIni::$conf['TEMP']['days']=$_GET['days'];
			UTLIni::$conf['TEMP']['temp']['day']=$_GET['temp']['day'];
			UTLIni::$conf['TEMP']['temp']['night']=$_GET['temp']['night'];
			try{
				$ok=UTLIni::writeINI('TEMP',false);
				header('Content-Type: application/json');
				echo json_encode(array('ok'=>$ok));
			}catch(Exception $e){
				header('Content-Type: application/json');
				echo json_encode(array('ok'=>false,'error'=>$e->getMessage()));
			}
			die();
		break;
	}
}
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
	<div class="all">
		<div class="mainMenu">
			<button class="btn menuTemperatura">Temperatura</button>
			<button class="btn menuPrediccion">Predicción</button>
			<button class="btn">Históricos</button>
			<button class="btn">Luces</button>
			<button class="btn">Piscina</button>
			<button class="btnSmall btnRefreshApp"><i class="fas fa-sync-alt"></i></button>
			<button class="btnSmall btnHaltApp"><i class="fas fa-power-off"></i></button>
		</div>
		<div class="content windowMain">
			<div class="statusBar">
				<div class="menu"><i class="fas fa-ellipsis-h"></i></div>
				<div class="modeDay"><i class="fas fa-sun"></i></div>
				<div class="modeNight hide"><i class="far fa-moon"></i></div>
				<div class="manual hide"><i class="far fa-hand-paper"></i></div>
				<div class="sheduled"><i class="far fa-clock"></i></div>
				<div class="runing"><i class="fas fa-cog"></i></div>
			</div>
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
			
			<div class="tempAjust dia">
				<label><i class="fas fa-sun"></i></label>
				<button class="btn btnUp"><i class="fas fa-caret-up"></i></button>
				<input type="number" value="20" name="tempDay" />
				<button class="btn btnDown"><i class="fas fa-caret-down"></i></button>
			</div>
			
			<div class="tempAjust noche">
				<label><i class="fas fa-moon"></i></label>
				<button class="btn btnUp"><i class="fas fa-caret-up"></i></button>
				<input type="number" value="18" name="tempNight" />
				<button class="btn btnDown"><i class="fas fa-caret-down"></i></button>
			</div>
			
			<div class="hourAjust">
				<span class="lineHour">00</span>
				<span class="lineHour">1</span>
				<span class="lineHour">2</span>
				<span class="lineHour">3</span>
				<span class="lineHour">4</span>
				<span class="lineHour">5</span>
				<span class="lineHour">6</span>
				<span class="lineHour">7</span>
				<span class="lineHour">8</span>
				<span class="lineHour">9</span>
				<span class="lineHour">10</span>
				<span class="lineHour">11</span>
				<span class="lineHour">12</span>
				<span class="lineHour">13</span>
				<span class="lineHour">14</span>
				<span class="lineHour">15</span>
				<span class="lineHour">16</span>
				<span class="lineHour">17</span>
				<span class="lineHour">18</span>
				<span class="lineHour day">19</span>
				<span class="lineHour day">20</span>
				<span class="lineHour day">21</span>
				<span class="lineHour day">22</span>
				<span class="lineHour day">23</span>
			</div>
			
			<div class="dayAjust">
				<button class="btn btnDay" data-weekday="0">L</button>
				<button class="btn btnDay" data-weekday="1">M</button>
				<button class="btn btnDay" data-weekday="2">X</button>
				<button class="btn btnDay" data-weekday="3">J</button>
				<button class="btn btnDay active" data-weekday="4">V</button>
				<button class="btn btnDay" data-weekday="5">S</button>
				<button class="btn btnDay" data-weekday="6">D</button>
			</div>
		</div>
		
		<div class="content windowPredicionAemet">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<br><br><br><br>
			<div class="widget"></div>
		</div>
	</div>
	</body>
</html>
