#!/usr/bin/php
<?php 
require_once __DIR__.'/lib/gpio.class.php';
require_once __DIR__.'/lib/UTLIni.php';


class DBConn extends SQLite3{
    function __construct(){
        $this->open(__DIR__.'/data/mypidomo.db');
    }
}

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
			'conf_temperature'=>(int) getTempConfig()
		);
		print_r($calculado);
		
		if ((int) $calculado['temperature']<$calculado['conf_temperature']){
			echo "To ON\n";
			$count_on++;
			if ($count_on>1){
				//mando siempre el valor de gpio por si ha fallado anteriormente
				gpio::write('20','1');
			}
			if ($count_on==2){
				$db->exec('INSERT INTO timer VALUES(datetime("now"),\'\')');
			}
		}else{
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
	sleep(10);
}
?>
