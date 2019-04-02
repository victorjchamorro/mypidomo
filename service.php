#!/usr/bin/php
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
require_once __DIR__.'/lib/gpio.class.php';
require_once __DIR__.'/lib/UTLIni.php';
require_once __DIR__.'/lib/database.class.php';

date_default_timezone_set('Europe/Madrid');

function getLiveData(){

	$exit=exec(__DIR__.'/service.py');
	$data=json_decode($exit,true);
	if ($data && $data['temp']>0){
		return $data;
	}else{
		return false;
	}

}

function getTempConfig(){

	UTLIni::addIniFile(__DIR__.'/data/temp.ini','TEMP');
	$dias=UTLIni::$conf['TEMP']['days'];
	$diaSemana=(date('w')==0 ? 6 : date('w')-1);
	$hora=date('G');
	
	$dia=($dias[$diaSemana][$hora]=='1') ? 'day': 'night';

	return UTLIni::$conf['TEMP']['temp'][$dia];
}

function initDB($db){
	$tableTemp='CREATE TABLE IF NOT EXISTS temp(
			date TEXT NOT NULL PRIMARY KEY,
			temperature REAL NOT NULL,
			humidity REAL NOT NULL,
			pressure REAL NOT NULL
			)';
	$tableTimer='CREATE TABLE IF NOT EXISTS timer(
			`on` TEXT NOT NULL,
			`off` TEXT NOT NULL
			)';
	$tableSolar='CREATE TABLE IF NOT EXISTS solar(
			date TEXT NOT NULL PRIMARY KEY,
			inversor TEXT NOT NULL,
			volt REAL NOT NULL,
			amp REAL NOT NULL,
			voltbat REAL NOT NULL,
			consumo REAL NOT NULL
			)';
		
}

$count_on=0;

while(true){

	$live=getLiveData();
	
	if (is_array($live)){

		$db=new DBConn();
		
		$db->exec('INSERT INTO temp VALUES(datetime("now"),'.$live['temp'].','.$live['humidity'].',0)');
		
		$calculado=array(
			'temperature'=>(float) $live['temp'],
			'humidity'=>(float) $live['humidity'],
			'conf_temperature'=>(float) getTempConfig()
		);
		echo date('d-m-Y H:i:s').":\n".str_replace(array("Array\n(\n",")\n"),'',print_r($calculado,true));
		
		if ($calculado['temperature']<$calculado['conf_temperature']){
			echo "To ON\n";
			$count_on++;
			if ($count_on>1){
				//mando siempre el valor de gpio por si ha fallado anteriormente
				gpio::write('20','1');
			}
			if ($count_on==2){
				//reflejo en bbdd solo una vez
				$db->exec('INSERT INTO timer VALUES(datetime("now"),\'\')');
			}
		}elseif($calculado['temperature']>($calculado['conf_temperature']+0.5)){
			echo "To OFF\n";
			if ($count_on>0){
				$count_on=0;
				$db->exec('UPDATE timer set `off`= datetime("now") WHERE `on`=(select max(`on`) from timer)');
			}
			//mando siempre el valor de gpio por si ha fallado anteriormente
			gpio::write('20','0');
		}
		$db->close();
	}
	
	$solarstr=file_get_contents('http://192.168.1.48/');
	if ($solarstr){
		$json=json_decode($solarstr,true);
		if ($json){
			/*
			estado	"on"
			v	"17.43"
			a	"3.74"
			w	"65.20"
			ap	"16.40"
			wp	"275.83"
			vb	"13.21"
			*/
			$json['consumo_inversor']=0;
		
			$db=new DBConn();
			$db->exec('INSERT INTO solar VALUES(datetime("now"),"'.$json['estado'].'",'.$json['v'].','.$json['a'].','.$json['vb'].','.$json['consumo_inversor'].'0)');
			$db->close();
		}
	}
	
	sleep(10);
}
?>
