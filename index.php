<?php
/**
 * @author Victor J. Chamorro <victorjchamorro@gmail.com>
 *
 * LGPL v3 - GNU LESSER GENERAL PUBLIC LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU LESSER General Public License as published by
 * the Free Software Foundation, either version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU LESSER General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
require_once './lib/aemet.class.php';
require_once './lib/gpio.class.php';
require_once './lib/database.class.php';
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
		case 'getLastData':
			UTLIni::addIniFile('./data/temp.ini','TEMP');
			$dias=UTLIni::$conf['TEMP']['days'];
			$diaSemana=(date('w')==0 ? 6 : date('w')-1);
			$hora=date('G');			
			$dia=($dias[$diaSemana][$hora]=='1') ? 'day': 'night';
			
			$db=new DBConn();
			$rs=$db->query('SELECT date,temperature,humidity FROM `temp` ORDER BY date DESC LIMIT 1');
			$data=$rs->fetchArray();
			header('Content-Type: application/json');
			echo json_encode(array(
				'rele_status'=>gpio::read('20'),
				'temp'=>$data['temperature'],
				'humidity'=>$data['humidity'],
				'date'=>$data['date'],
				'mode'=>UTLIni::$conf['TEMP']['temp']['mode'],
				'current_mode'=>$dia
			));
			die();
		case 'getHistory':
			$db=new DBConn();
			$rs=$db->query('SELECT `on`,`off` FROM `timer` ORDER BY `on` DESC');
			$html='<table><tr><td>On</td><td>Off</td><td>Time</td></tr>';
			
			$datetimezone=new DateTimeZone('Europe/Madrid');
			$datetimeutc=new DateTimeZone('UTC');
			
			while($row=$rs->fetchArray()){
			
				$dateOn=new DateTime($row[0],$datetimeutc);
				$dateOff=new DateTime($row[1], $datetimeutc);
				
				$interval=$dateOff->diff($dateOn);
				
				$strdiff=$interval->format('%h:%i:%sh');
				
				$dateOn->setTimezone($datetimezone);
				$dateOff->setTimezone($datetimezone);
				
				$html.="<tr>";
				$html.="<td>".$dateOn->format('d/m/Y H:i:s')."</td><td>".(($row[1]!='') ? $dateOff->format('d/m/Y H:i:s') : '')."</td>";
				$html.="<td>$strdiff</td>";
				$html.="</tr>";
			}
			$html.="</table>";
			echo $html;
			die();
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
		case 'getSolar':
			
			$data=array();
			$data['estado']='off';
			$data['v']=0;
			$data['a']=0;
			$data['w']=0;
			$data['ap']=0;
			$data['wp']=0;
			$data['vb']=0;
			$data['soc']=0;
			//Sistema antiguo
			$data1=@file_get_contents('http://192.168.1.48/');
			if ($data1){
				$data=json_decode($data1,true);
			}
			
			//emoncms
			$data2=file_get_contents('http://192.168.1.49/emoncms/input/get/?&apikey=8b3a05b077bfa5ed9fc395b3ec166bc6');
			if ($data2){
				$json=json_decode($data2,true);
				$json2=$json['emontx'];
				$json3=$json['bmv700'];
				$data['v']=$json2['fv_v']['value'];
				$data['a']=$json3['I']['value']/1000;
				$data['w']=$json2['fv_sum_w']['value'];
				$data['ap']=$json2['fv_i']['value'];
				$data['wp']=$json2['load_w']['value'];
				$data['vb']=$json2['bat_v']['value'];
				$data['soc']=$json3['SOC']['value']/10;
			}
			
			header('Content-Type: application/json');
			echo json_encode($data);
			
			die();
		case 'inversor_on':
			header('Content-Type: application/json');
			echo file_get_contents('http://192.168.1.48/on');
			die();
		case 'inversor_off':
			header('Content-Type: application/json');
			echo file_get_contents('http://192.168.1.48/off');
			die();
		case 'getSolarHistory':
			$db=new DBConn();
			$rs=$db->query("select strftime('%H',datetime(date,'localtime')),avg(voltbat),avg(volt*amp) from solar where date(date)=date('now') or date(date)=date(datetime('now', '-1 day')) group by strftime('%d%H',date) order by date desc limit 25");
			$dataB=array();
			$dataB['labels']=array();
			$dataB['series']=array();
			$dataS=$dataB;
			$dataS['series'][0]=array();
			$dataB['series'][0]=array();
			while($row=$rs->fetchArray()){
				$dataB['labels'][]=$row[0];
				$dataB['series'][0][]=$row[1];
				$dataS['series'][0][]=$row[2];
			}
			//Invierto los valores para que se pinte al final lo nuevo
			$dataB['series'][0]=array_reverse($dataB['series'][0]);
			$dataS['series'][0]=array_reverse($dataS['series'][0]);
			
			$dataB['labels']=array_reverse($dataB['labels']);
			$dataS['labels']=$dataB['labels'];
			
			header('Content-Type: application/json');
			echo json_encode(array('bateria'=>$dataB,'produccion'=>$dataS));
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
		<link rel="stylesheet" type="text/css" href="<?php echo addRoute('./css/chartist.min.css');?>" />
		
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<script src="./js/jquery-1.9.1.min.js"></script>
		<!--<script src="./amcharts/amcharts.js" type="text/javascript"></script> -->
		<script type="text/javascript" src="<?php echo addRoute('./js/common.js');?>"></script>
		<script type="text/javascript" src="<?php echo addRoute('./js/chartist.min.js');?>"></script>
		
		<meta name="mobile-web-app-capable" content="yes">
		
	</head>
	<body>
	<div class="all">
		<div class="mainMenu">
			<button class="btn menuTemperatura">Temperatura</button>
			<button class="btn menuPrediccion">Predicción</button>
			<button class="btn menuHistorial">Históricos</button>
			<button class="btn menuSolar">Solar</button>
			<button class="btn">Piscina</button>
			<button class="btn">Riego</button>
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
		
		<div class="content windowHistory">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<div class="tableHistory">
			</div>
		</div>
		
		<div class="content windowSolarOld">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<button class="btnSmall btnChart"><i class="fas fa-chart-line"></i></button>
			<div class="data">
				<div class="dataSolar">
					<span class="data-A"></span>&nbsp;<span class="data-W"></span><br>
					<span class="data-V"></span>&nbsp;<span class="data-P"></span>
				</div>
				<div class="imgSolar">
					<span class="estado sol hide"><i class="fas fa-sun"></i></span>
					<span class="estado nublado hide"><i class="fas fa-cloud-sun"></i></span>
					<span class="estado noche hide"><i class="fas fa-moon"></i></span>
					<div style="margin-top:12px;text-align:left;">
						<i class="fas fa-solar-panel"></i>&nbsp;<span class="data-porc">0</span> <span style="font-family:'Bold LED Board-7';font-size: 50px;">%</span><br>
						<i class="fas fa-car-battery" style="font-size:75px"></i>&nbsp;<span class="data-pVb">0</span> <span style="font-family:'Bold LED Board-7';font-size: 50px;">%</span>
					</div>
				</div>
				<div class="actions">
				
					<i class="fas fa-plug red"></i>
					<img class="inversor" src="/imgs/home-no-door.png" style="margin-top:4px;">
					<i class="inversor fas fa-sun"></i>
					<!--
					<i class="inversor fas fa-battery-full"></i>
					<i class="inversor fas fa-battery-three-quarters"></i>
					<i class="inversor fas fa-battery-half"></i>
					<i class="inversor fas fa-battery-quarter"></i>
					<i class="inversor fas fa-battery-empty"></i>
					-->
					<div style="height:30px;"></div>
					
					<button class="btnTransparent inversorOn hide"><i class="fas fa-power-off"></i></button>
					<button class="btnTransparent inversorOff hide"><i class="fas fa-power-off"></i></button>
				</div>
			</div>
			<div class="chart">
				<i class="fas fa-car-battery"></i>
				<div class="chart-bateria" style="height:185px;"></div>
				
				<i class="fas fa-solar-panel"></i>
				<div class="chart-produccion" style="height:185px;"></div>
			</div>
		</div>
		<div class="content windowSolar">
			<button class="btnSmall btnToMain"><i class="fas fa-arrow-left"></i></button>
			<iframe src="http://192.168.1.49/emoncms/dashboard/view?id=7" scrolling="no" width="100%" height="190" frameborder="0"></iframe>
		</div>
	</div>
	</body>
</html>
