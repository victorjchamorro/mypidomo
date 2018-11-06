<?php 
/**
 * @author Victor J. Chamorro <victor@ipdea.com>
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
 *
 */
 class TeException extends Exception{
	
	private $classname="";
	
	function __construct($mensaje,$codigo,$clase=""){
		if ($clase=="") $clase=__CLASS__;
		parent::__construct($mensaje,(int) $codigo);
		$this->classname=$clase;
	}
}
?>
