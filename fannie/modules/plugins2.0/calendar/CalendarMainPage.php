<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

include_once('../../../config.php');
if (!class_exists('FanniePage'))
	include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
if (!class_exists('CalendarPlugin'))
	include(dirname(__FILE__).'/CalendarPlugin.php');
if (!function_exists('getUID'))
	include($FANNIE_ROOT.'auth/login.php');
include('CalendarPluginDisplayLib.php');

class CalendarMainPage extends FanniePage {

	protected $must_authenticate = True;
	private $uid;

	function preprocess(){
		global $FANNIE_URL;
		$this->uid = ltrim(getUID($this->current_user),"0");
		$this->title = "Cal";
		$this->header = "Calendars";
		
		$plugin = new CalendarPlugin(); 
		$this->add_script($plugin->plugin_url().'/javascript/calendar.js');
		$this->add_script($plugin->plugin_url().'/javascript/ajax.js');

		$view = get_form_value('view','index');
		if ($view == 'month') 
			$this->window_dressing = False;
		else
			$this->add_script($FANNIE_URL.'src/CalendarControl.js');

		if (file_exists(dirname(__FILE__).'/css/'.$view.'.css'))
			$this->add_css_file($plugin->plugin_url().'/css/'.$view.'.css');

		return True;
	}
	
	function body_content(){
		$view = get_form_value('view','index');
		switch ($view){
		case 'month':
			$editable = True;

			$year = get_form_value('year',date('Y'));
			$month = get_form_value('month',date('n'));
			$calID = get_form_value('calID',0);

			echo CalendarPluginDisplayLib::monthView($calID,$month,$year,$this->uid);
			break;
		case 'prefs':
			$calID = get_form_value('calID','');
			echo CalendarPluginDisplayLib::prefsView($calID,$this->uid);
			break;
		case 'overlays':
			echo CalendarPluginDisplayLib::overlaysView($this->uid);
			break;
		case 'showoverlay':
			$cals = get_form_value('cals');
			$start = get_form_value('startdate');
			$end = get_form_value('enddate');
			echo CalendarPluginDisplayLib::showoverlayView($cals,$startdate,$enddate);
			break;
		case 'index':
		default:
			echo CalendarPluginDisplayLib::indexView($this->uid);
			break;
		}
	}

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new CalendarMainPage();
	$obj->draw_page();
}

?>