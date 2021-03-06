<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class ViewCashiersPage extends FanniePage {

	protected $title = "Fannie : View Cashiers";
	protected $header = "View Cashiers";
	protected $must_authenticate = True;
	protected $auth_classes = array('editcashiers');


	function javascript_content(){
		ob_start();
		?>
function deleteEmp(emp_no,filter){
	if (confirm('Deleting a cashier completely removes them. If you just want to disable the login, use "Edit" instead.')){
		window.location='ViewCashiersPage.php?filter='+filter+'&emp_no='+emp_no+'&delete=yes';	
	}
}
		<?php
		return ob_get_clean();
	}

	function preprocess(){
		global $FANNIE_OP_DB;
		$emp = FormLib::get_form_value('emp_no');
		if (FormLib::get_form_value('delete') !== '' && $emp !== ''){
			$dbc = FannieDB::get($FANNIE_OP_DB);
			$p = $dbc->prepare_statement("DELETE FROM employees WHERE emp_no=?");
			$dbc->exec_statement($p, array($emp));
		}
		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$filter = FormLib::get_form_value('filter',1);
		$order = FormLib::get_form_value('order','num');
		$orderby = '';
		switch($order){
		case 'num':
		default:
			$orderby = 'ORDER BY emp_no';
			break;
		case 'name':
			$orderby = 'ORDER BY FirstName';
			break;
		case 'pass':
			$orderby = 'ORDER BY CashierPassword';
			break;
		case 'fes':
			$orderby = 'ORDER BY frontendsecurity';
			break;
		}
		
		$ret = "Showing: <select onchange=\"location='ViewCashiersPage.php?filter='+this.value;\">";
		if ($filter == 1){
			$ret .= "<option value=1 selected>Active Cashiers</option>";
			$ret .= "<option value=0>Disabled Cashiers</option>";
		}
		else{
			$ret .= "<option value=1>Active Cashiers</option>";
			$ret .= "<option value=0 selected>Disabled Cashiers</option>";
		}
		$ret .= "</select><hr />";

		$ret .= "<table cellpadding=4 cellspacing=0 border=1><tr>";
		$ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=num>Emp#</th>";
		$ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=name>Name</th>";
		$ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=pass>Password</th>";
		$ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=fes>Privileges</th>";
		$ret .= "<th>&nbsp;</th><th>&nbsp;</th></tr>";

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$empP = $dbc->prepare_statement("SELECT emp_no,CashierPassword,FirstName,LastName,frontendsecurity
				FROM employees WHERE EmpActive=? $orderby");
		$empR = $dbc->exec_statement($empP,array($filter));
		while($row = $dbc->fetch_row($empR)){
			$ret .= sprintf("<tr><td>%d</td><td>%s</td><td>%d</td><td>%s</td>",
				$row[0],$row[2].' '.$row[3],$row[1],
				($row[4]<=20)?'Regular':'Manager');
			$ret .= sprintf("<td><a href=\"CashierEditor.php?emp_no=%d\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" 
				alt=\"Edit\" border=0 /></a></td>
				<td><a href=\"\" onclick=\"deleteEmp(%d,%d); return false;\"><img alt=\"Delete\"
				src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" border=0 /></a></td></tr>",
				$row[0],$row[0],$filter);
		}
		$ret .= "</table>";

		return $ret;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new ViewCashiersPage();
	$obj->draw_page();
}
?>
