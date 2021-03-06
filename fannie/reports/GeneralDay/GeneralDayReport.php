<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');

class GeneralDayReport extends FannieReportPage {

	function preprocess(){
		$this->title = "Fannie : General Day Report";
		$this->header = "General Day Report";
		$this->report_cache = 'none';
		$this->grandTTL = 1;
		$this->multi_report_mode = True;
		$this->sortable = False;

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(False);
			$this->report_headers = array('Desc','Qty','Amount');

			/**
			  Check if a non-html format has been requested
			*/
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return True;
	}

	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB, $FANNIE_EQUITY_DEPARTMENTS;
		$d1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$dates = array($d1.' 00:00:00',$d1.' 23:59:59');
		$data = array();

		$dlog = select_dlog($d1);
		$tenderQ = $dbc->prepare_statement("SELECT 
			TenderName,count(d.total),sum(d.total) as total
			FROM $dlog as d , tenders as t 
			WHERE d.tdate BETWEEN ? AND ?
			AND d.trans_subtype = t.TenderCode
			and d.total <> 0
			GROUP BY t.TenderName ORDER BY TenderName");
		$tenderR = $dbc->exec_statement($tenderQ,$dates);
		$report = array();
		while($tenderW = $dbc->fetch_row($tenderR)){
			$record = array($tenderW['TenderName'],$tenderW[1],
					sprintf('%.2f',$tenderW['total']));
			$report[] = $record;
		}
		$data[] = $report;

		$salesQ = $dbc->prepare_statement("SELECT m.super_name,sum(d.quantity) as qty,
				sum(d.total) as total FROM $dlog AS d LEFT JOIN
				MasterSuperDepts AS m ON d.department=m.dept_ID
				WHERE d.tdate BETWEEN ? AND ?
				AND d.department <> 0 AND d.trans_type <> 'T'
				GROUP BY m.super_name ORDER BY m.super_name");
		$salesR = $dbc->exec_statement($salesQ,$dates);
		$report = array();
		while($salesW = $dbc->fetch_row($salesR)){
			$record = array($salesW['super_name'],
					sprintf('%.2f',$salesW['qty']),
					sprintf('%.2f',$salesW['total']));
			$report[] = $record;
		}
		$data[] = $report;

		$discQ = $dbc->prepare_statement("SELECT m.memDesc, SUM(d.total) AS Discount,count(*)
				FROM $dlog d INNER JOIN
			       custdata c ON d.card_no = c.CardNo AND c.personNum=1
				INNER JOIN
			      memTypeID m ON c.memType = m.memTypeID
				WHERE d.tdate BETWEEN ? AND ?
			       AND d.upc = 'DISCOUNT'
				and total <> 0
				GROUP BY m.memDesc ORDER BY m.memDesc");
		$discR = $dbc->exec_statement($discQ,$dates);
		$report = array();
		while($discW = $dbc->fetch_row($discR)){
			$record = array($discW['memDesc'],$discW[2],$discW[1]);
			$report[] = $record;
		}
		$data[] = $report;

		$taxSumQ = $dbc->prepare_statement("SELECT  sum(total) as tax_collected
			FROM $dlog as d 
			WHERE d.tdate BETWEEN ? AND ?
			AND (d.upc = 'tax')
			GROUP BY d.upc");
		$taxR = $dbc->exec_statement($taxSumQ,$dates);
		$report = array();
		while($taxW = $dbc->fetch_row($taxR)){
			$record = array('Sales Tax',null,$taxW['tax_collected']);
			$report[] = $record;
		}
		$data[] = $report;

		$transQ = $dbc->prepare_statement("select q.trans_num,sum(q.quantity) as items,transaction_type, sum(q.total) from
			(
			select trans_num,card_no,quantity,total,
			m.memdesc as transaction_type
			from $dlog as d
			left join custdata as c on d.card_no = c.cardno
			left join memTypeID as m on c.memtype = m.memTypeID
			WHERE d.tdate BETWEEN ? AND ?
			AND trans_type in ('I','D')
			and upc <> 'RRR'
			and c.personNum=1
			) as q 
			group by q.trans_num,q.transaction_type");
		$transR = $dbc->exec_statement($transQ,$dates);
		$trans_info = array();
		while($row = $dbc->fetch_array($transR)){
			if (!isset($transinfo[$row[2]]))
				$transinfo[$row[2]] = array(0,0.0,0.0,0.0,0.0);
			$transinfo[$row[2]][0] += 1;
			$transinfo[$row[2]][1] += $row[1];
			$transinfo[$row[2]][3] += $row[3];
		}
		$tSum = 0;
		$tItems = 0;
		$tDollars = 0;
		foreach(array_keys($transinfo) as $k){
			$transinfo[$k][2] = round($transinfo[$k][1]/$transinfo[$k][0],2);
			$transinfo[$k][4] = round($transinfo[$k][3]/$transinfo[$k][0],2);
			$tSum += $transinfo[$k][0];
			$tItems += $transinfo[$k][1];
			$tDollars += $transinfo[$k][3];
		}
		$transinfo["Totals"] = array($tSum,$tItems,round($tItems/$tSum,2),$tDollars,round($tDollars/$tSum,2));
		$report = array();
		foreach($transinfo as $title => $info){
			array_unshift($info,$title);
			$report[] = $info;
		}
		$data[] = $report;

		$ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
		if ($ret != 0){
			/* equity departments exist */
			$depts = array_pop($depts);
			$dlist = "(";
			foreach($depts as $d){
				$dates[] = $d; // add query param
				$dlist .= '?,';
			}
			$dlist = substr($dlist,0,strlen($dlist)-1).")";

			$equityQ = $dbc->prepare_statement("SELECT d.card_no,t.dept_name, sum(total) as total 
				FROM $dlog as d left join departments as t ON d.department = t.dept_no
				WHERE d.tdate BETWEEN ? AND ?
				AND d.department IN $dlist
				GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name");
			$equityR = $dbc->exec_statement($equityQ,$dates);
			$report = array();
			while($equityW = $dbc->fetch_row($equityR)){
				$record = array($equityW['card_no'],$equityW['dept_name'],
						sprintf('%.2f',$equityW['total']));
				$report[] = $record;
			}
			$data[] = $report;
		}
		
		return $data;
	}

	function calculate_footers($data){
		switch($this->multi_counter){
		case 1:
			$this->report_headers[0] = 'Tenders';
			break;
		case 2:
			$this->report_headers[0] = 'Sales';
			break;
		case 3:
			$this->report_headers[0] = 'Discounts';
			break;
		case 4:
			$this->report_headers[0] = 'Tax';
			break;
		case 5:
			$this->report_headers = array('Type','Trans','Items','Avg. Items','Amount','Avg. Amount');
			return array();
			break;
		case 6:
			$this->report_headers = array('Mem#','Equity Type', 'Amount');
			break;
		}
		$sumQty = 0.0;
		$sumSales = 0.0;
		foreach($data as $row){
			$sumQty += $row[1];
			$sumSales += $row[2];
		}
		return array(null,$sumQty,$sumSales);
	}

	function form_content(){
		$start = date('Y-m-d',strtotime('yesterday'));
		?>
		<form action=GeneralDayReport.php method=get>
		<table cellspacing=4 cellpadding=4>
		<tr>
		<th>Date</th>
		<td><input type=text id=date1 name=date1 onclick="showCalendarControl(this);" value="<?php echo $start; ?>" /></td>
		</tr><tr>
		<td>Excel <input type=checkbox name=excel /></td>
		<td><input type=submit name=submit value="Submit" /></td>
		</tr>
		</table>
		</form>
		<?php
	}

}

$obj = new GeneralDayReport();
$obj->draw_page();
?>
