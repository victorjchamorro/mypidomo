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
class gpio{

	/**
	 * @param int $pin GPIO Pin number
	 * @param int $value [1|0]
	 */
	public static function write($pin,$value){
		if (!file_exists('/sys/class/gpio/gpio'.$pin)){
			if (file_put_contents('/sys/class/gpio/export',$pin)){
				clearstatcache();
				sleep(1);
			}else{
				echo "Error. Check that the '".exec('whoami')."' user is within the gpio group";
			}
		}
		
		if (trim(file_get_contents('/sys/class/gpio/gpio'.$pin.'/direction'))!='out'){
			file_put_contents('/sys/class/gpio/gpio'.$pin.'/direction','out');
		}
		
		file_put_contents('/sys/class/gpio/gpio'.$pin.'/value',$value);
	}
	
	/**
	 * @param int $pin GPIO Pin number
	 * @return string [1|0|false]
	 */
	public static function read($pin){
		if (is_file('/sys/class/gpio/gpio'.$pin.'/value')){
			return trim(file_get_contents('/sys/class/gpio/gpio'.$pin.'/value'));
		}else{
			return false;
		}
		
	}
}

?>
