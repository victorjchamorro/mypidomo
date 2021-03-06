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
class aemet{

	const key='eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2aWN0b3JqY2hhbW9ycm9AZ21haWwuY29tIiwianRpIjoiMzBkMzcwMzEtZjMyOS00MmRmLWFkYmYtMGExMGE0ZmZkMzc4IiwiaXNzIjoiQUVNRVQiLCJpYXQiOjE1NDA4NTI1MzQsInVzZXJJZCI6IjMwZDM3MDMxLWYzMjktNDJkZi1hZGJmLTBhMTBhNGZmZDM3OCIsInJvbGUiOiIifQ.0xMTUKeFYshLjE54pVMDANATq-Vgj99fiIJuNpGWTew';

	private $estacion=28141;
	
	private $data;
	
	private function __construct($estacion){
		$this->estacion=$estacion;
		$this->getData();
	}
	
	private function getData(){
		$metadatos=$this->runPeticion('https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/'.$this->estacion.'/');
		if (is_array($metadatos) && $metadatos['estado']==200){
			$this->data=json_decode(file_get_contents('https://opendata.aemet.es/opendata/sh/b5216bf9'),true);
		}
	}

	static function init($estacion){
		return new self($estacion);
	}

	public function getTemperatura(){
		return $this->data[count($this->data)-1]['ta'];
	}
	
	public function getHumedad(){
		return $this->data[count($this->data)-1]['hr'];
	}
	
	private function runPeticion($url){
	
		$curl = curl_init();

		curl_setopt_array($curl, array(
			//CURLOPT_URL => 'https://opendata.aemet.es/opendata/api/prediccion/especifica/municipio/diaria/'.$this->localidad.'/?api_key='.self::key,
			CURLOPT_URL=> $url.'?api_key='.self::key,
			//CURLOPT_URL=> 'https://opendata.aemet.es/opendata/api/observacion/convencional/todas/?api_key='.self::key,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		if ($err==0){
			return json_decode($response,true);
		}else{
			return $err;
		}
		
	}
}

?>
