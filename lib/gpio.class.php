<?php

class gpio{

	public static function write($pin,$value){
		if (!file_exists('/sys/class/gpio/gpio'.$pin)){
			if (file_put_contents('/sys/class/gpio/export',$pin)){
				clearstatcache();
				sleep(1);
			}else{
				echo "Error. check that the www-data user is within the gpio group";
			}
		}
		
		if (trim(file_get_contents('/sys/class/gpio/gpio'.$pin.'/direction'))!='out'){
			file_put_contents('/sys/class/gpio/gpio'.$pin.'/direction','out');
		}
		
		file_put_contents('/sys/class/gpio/gpio'.$pin.'/value',$value);
	}
	
	public static function read($pin){
		if (is_file('/sys/class/gpio/gpio'.$pin.'/value')){
			return trim(file_get_contents('/sys/class/gpio/gpio'.$pin.'/value'));
		}else{
			return false;
		}
		
	}
}

?>
