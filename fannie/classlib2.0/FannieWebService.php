<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists('JsonLib')) include(dirname(__FILE__).'/../src/JsonLib.php');

class FannieWebService {
	
	public $type = 'json'; // json/plain by default

	/**
	  constructor will run the webservice automatically
	*/
	public function __construct(){
		$info = new ReflectionClass($this);
		if (basename($_SERVER['PHP_SELF']) == basename($info->getFileName())){
			$output = $this->run();
			$render_func = 'render_'.strtolower($this->type);
			if (method_exists($this, $render_func))
				echo $this->$render_func($output);
			else
				echo $this->render_plain($output);
		}
	}

	/**
	  Do whatever the service is supposed to do.
	  Should override this.
	  @return an array of data
	*/
	protected function run(){
		return array();
	}

	/**
	  Create JSON representation of array
	  @param $arr an array
	  @return JSON string
	*/
	protected function render_json($arr){
		return JsonLib::array_to_json($arr);
	}
	
	/**
	  Simple render concatenate array to string
	  @param $arr an array
	  @return string
	*/
	protected function render_plain($arr){
		$ret = '';
		foreach($arr as $a) $ret .= $a;
		return $ret;
	}
}

?>
