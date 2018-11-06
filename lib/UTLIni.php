<?php
//require_once 'v4/private/class/UTL/UTLTeException.php';
//require_once 'v4/private/class/UTL/UTLUtilidades.php';
require_once __dir__.'/UTLTeException.php';

/**
 * Clase estática para el manejo de INIs, tanto lectura como escritura
 * @author Victor J. Chamorro - victor@ipdea.com
 * @package UTL
 * @copyright Ipdea Land, S.L. / Teenvio
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
 */
class UTLIni{

	/**
	 * Datos del ini en un array asociativo
	 * @var array
	 */
	public static $conf=array();
	
	/**
	 * Array con las rutas de los ficheros que generaron cada namespace
	 * @var unknown
	 */
	private static $namespaces_files=array();
	
	/**
	 * Ésta clase no permite instanciacón, sus métodos son estáticos
	 */
	private function __construct(){ }
	
	
	/**
	 * Agrega un fichero ini para poder extraer sus parámetros
	 * Si no se selecciona un namespace, se agrega al GENERAL
	 * 
	 * @param string $file
	 * @param string $namespace
	 * @throws TeException
	 */
	public static function addIniFile($file,$namespace="GENERAL"){
		$file_orig=$file;
		//$file=UTLUtilidades::getFullPath($file);
		
		if (is_file($file)){
			//$parseini=parse_ini_string(file_get_contents($file,true), true);
			$parseini=parse_ini_file($file,true);		
			if ($parseini===false){
				throw new TeException('Error al agregar archivo INI, fallo al parsear - '.$file,1,__CLASS__);
			}else{
				UTLini::$conf[$namespace]=$parseini;
			}
			UTLIni::$namespaces_files[$namespace]=$file;
		}else{
			throw new TeException('Error al agregar archivo INI, no existe el fichero - '.$file_orig,2);
		}
	}
	
	/**
	 * Agrega un fichero ini en blanco para poder usarlo para guardado desde cero
	 * Si no se selecciona un namespace, se agrega al GENERAL
	 * La ruta no se comprobará
	 * 
	 * @param string $file
	 * @param string $namespace
	 */
	public static function addEmptyFile($file,$namespace="GENERAL"){
		UTLini::$conf[$namespace]=array();
		UTLIni::$namespaces_files[$namespace]=$file;
	}
	
	/**
	 * Devuelve el $parametro del $namespace especificado
	 * o false en caso de no existir,
	 * si se especifica $exception=true, devolverá una excepción en lugar de false
	 * 
	 * @param string $parametro
	 * @param string $namespace
	 * @param boolean $exception
	 * @return string
	 */
	public static function getConfig($parametro,$namespace="GENERAL",$exception=false){
		if (isset(UTLini::$conf[$namespace]) && isset(UTLini::$conf[$namespace][$parametro])){
			return UTLini::$conf[$namespace][$parametro];
		}else{
			if ($exception){
				throw new TeException('No se ha podido recuperar el dato '.$parametro.' con namespace '.$namespace.' del fichero ini '.UTLIni::$namespaces_files[$namespace],__LINE__,__CLASS__);
			}else{
				return false;
			}
		}
	}

	/**
	 * 
	 * Guarda el ini de un Namespace en su fichero. 
	 * Cuidado con los permisos, el usuario que ejecute el script (www-data) debe tener permisos de escritura sobre el fichero.
	 * Se sobreescibirá el fichero original
	 * 
	 * @param string $namespace
	 * @param boolean $backup Guarda un backup antes de guardar los nuevos datos en el fichero, por defecto true
	 * @throws TeException
	 * @return boolean
	 */
	public static function writeINI($namespace="GENERAL",$backup=true){
		if (!isset(UTLini::$conf[$namespace]))
			throw new TeException("El namespace $namespace no existe, necesita cargarlo previamente", __LINE__,__CLASS__);
		
		//Preparo los datos
		$res = array();
		foreach(UTLIni::$conf[$namespace] as $key => $val){
			if(is_array($val)){
				$res[] = "\n[$key]";
				foreach($val as $skey => $sval){
					if (is_array($sval)){
						foreach($sval as $n=>$ssval){
							$res[] = $skey."[$n] = ".(is_numeric($ssval) ? $ssval : '"'.$ssval.'"');
						}
					}else{
						$res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
					}
				}
			}else{
				$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
			}
		}
		//Guardo Backup si procede
		if ($backup && is_file(UTLIni::$namespaces_files[$namespace])){
			$cp=@copy(UTLIni::$namespaces_files[$namespace], UTLIni::$namespaces_files[$namespace].'.'.time());
			if ($cp===false) throw new TeException("Error al guardar la copia de seguridad antes de guardar el ini, revise los permisos. ".UTLIni::$namespaces_files[$namespace], __LINE__,__CLASS__);
		}
		
		//Escribo en el fichero
		$wrirte=file_put_contents(UTLIni::$namespaces_files[$namespace],implode("\n", $res));
		
		if ($wrirte===false) throw new TeException("Error al guardar el ini, revise los permisos. ".UTLIni::$namespaces_files[$namespace], __LINE__,__CLASS__);
		
		return true;
	}
	
	/**
	 * Vuelca un ini (un namespace cargado) a una tabla temporal MySQL.
	 * Cada bloque [xxxx] del ini corresponderá con una fila.
	 * Agrega un campo `fecha_modificacion` siempre al final de la tabla
	 * @param BD $BD 
	 * @param string $namespace
	 * @param string $tableName
	 * @param array $structure Array con clave el nombre del campo y valor el tipo de dato
	 * @param array $exclude Array simple con los datos que no se incluirán en la tabla temporal
	 * @throws TeException
	 */
	public static function toTmpTable(BDB\BD $BD,$namespace="GENERAL",$tableName="",$structure=null,$exclude=null){
		if (!$BD instanceof BDB\BD){
			throw new TeException('La BD pasada no es una instancia',__LINE__,__CLASS__);
		}
		
		$BD->silencio=true;
		$BD->setExceptions(false);
		
		//Preparamos la tabla temporal
		$SQL="DROP TABLE IF EXISTS `$tableName`";
		if (!$BD->query($SQL)){
			throw new TeException('Fallo en el sql al truncar la tabla temporal: '.$BD->ultimo_error, __LINE__,__CLASS__);
		}
		
		if ($tableName=="") $tableName=$namespace;
		
		$campos=array();
		
		//miramos qué registro tiene la mayor cantidad de campos
		foreach (UTLIni::$conf[$namespace] as $row){
			if (count($row)>count($campos)) $campos=$row;
		}
		
		//Campos excluidos
		if (is_array($exclude)){
			foreach($exclude as $campo){
				if(isset($campos[$campo])) unset($campos[$campo]);
			}
		}

		$SQL="CREATE TABLE `$tableName` (\n";

		foreach ($campos as $campo=>$valor){

			if (is_array($structure) && isset($structure[$campo])){
				$SQL.="`$campo` ".$structure[$campo]." NOT NULL,\n";
			}elseif (is_numeric($valor)){
				$SQL.="`$campo` INT(11) NOT NULL,\n";
			}else{
				$SQL.="`$campo` VARCHAR(255) default '',\n";
			}
		}

		$SQL.="\n`fecha_modificacion` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
			) ENGINE=MEMORY CHARSET=utf8";

		if (!$BD->query($SQL)){
			throw new TeException('Fallo en el sql al construir la tabla temporal: '.$BD->ultimo_error, __LINE__,__CLASS__);
		}
		
		foreach (UTLIni::$conf[$namespace] as $item){
			if (!is_array($item) || count($item)==0) continue;
			
			//Campos excluidos
			if (is_array($exclude)){
				foreach($exclude as $campo){
					if(isset($item[$campo])) unset($item[$campo]);
				}
			}
			
			//Si el elemento actual tiene datos que no estan en la extructura, lo descarto
			$item_copy=$item;
			foreach ($item_copy as $campo=>$dato){
				if (!isset($campos[$campo])) unset($item[$campo]);
			}
			unset($item_copy);
			
			
			
			if (!$BD->InsertTabla($tableName, null, null, $item)){
				throw new TeException('Fallo al insertar un registro en la tabla temporal: '.$BD->ultimo_error, __LINE__,__CLASS__);
			}
		}
		return true;
	}
}

?>
