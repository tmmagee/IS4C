<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$cards = "(";
if (isset($_POST["cardno"])){
	foreach($_POST["cardno"] as $c){
		$cards .= $c.",";
	}
	$cards = rtrim($cards,",");
	$cards .= ")";
}

$cardsClause = " AND m.card_no IN $cards ";
if ($cards == "(") $cardsClause = "";

$selAddQ = "SELECT m.card_no, c.lastName,m.street, '',
           m.City, m.State, m.zip,n.balance
           FROM 
           meminfo m 
	   LEFT JOIN custdata as c on c.cardno=m.card_no and c.personnum=1
	   LEFT JOIN newBalanceToday_cust as n ON m.card_no=n.memnum
	   WHERE c.type not in ('TERM') and
	   c.memtype = 2
	   and n.balance > 0
	   $cardsClause 
           ORDER BY m.card_no";
$selAddR = $sql->query($selAddQ);

$selTransQ = "SELECT card_no, charges, payments, 
	convert(varchar(50),date,101), trans_num,description,dept_name  
	FROM AR_statementHistory as m WHERE 1=1 $cardsClause
	order by convert(int,card_no),date,trans_num,description,dept_name";
$selTransR = $sql->query($selTransQ);
$selTransN = $sql->num_rows($selTransR);

$today= date("d-F-Y");
$month = date("n");
$year = date("Y");

if($month != 1){
   $prevMonth = $month- 1;
}else{
   $prevMonth = 12;
   $year = $year - 1;
}
$prevYear = $year;

$prevPrevMonth = $prevMonth - 1;
$prevPrevYear = $year;
if ($prevPrevMonth == 0){
	$prevPrevMonth = 12;
	$prevPrevYear = $year - 1;
}

$endDay = get_month_day($prevMonth,$year);

//$stateDate = $endDay."/".$prevMonth."/".$year;
//$stateDate = $endDay." ".date("F",$prevMonth).", ".$year;
$dateFormat = "d F, Y";
$stateDate = date($dateFormat);
$pdf = new FPDF();

//Meat of the statement
$rowNum=0;
while($selAddW = $sql->fetch_row($selAddR)){
   $pdf->AddPage();
   $pdf->SetFont('Arial','B','14');
   $pdf->Cell(20,10,'Whole Foods Community Co-op',0);
   $pdf->Image($FANNIE_ROOT.'legacy/images/WFCLogoCThru1.jpg',130,10,50,25);
   $pdf->Ln(5);
   $pdf->SetFont('Arial','','12');
   $pdf->Cell(20,10,'610 East Fourth Street',0);
   $pdf->Ln(5);
   $pdf->Cell(20,10,'Duluth, MN  55805',0);
   $pdf->Ln(18);

   $pdf->Cell(10,10,$stateDate,0);
   $pdf->Ln(15);


   //Member address
   $pdf->Cell(10,10,trim($selAddW[0]),0);
   $pdf->Ln(5);
   $pdf->Cell(50,10,trim($selAddW[1]),0);
   $pdf->Ln(5);

   if (strstr($selAddW[2],"\n") === False){
	   $pdf->Cell(80,10,$selAddW[2],0);
	   $pdf->Ln(5);
   }
   else {
	$pts = explode("\n",$selAddW[2]);
	$pdf->Cell(80,10,$pts[0],0);
	$pdf->Ln(5);
	$pdf->Cell(80,10,$pts[1],0);
	$pdf->Ln(5);
   }
   $pdf->Cell(90,10,$selAddW[4] . ', ' . $selAddW[5] . '   ' . $selAddW[6],0);
   $pdf->Ln(25);
 
   $txt = "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(10);

   $startYear = date('Y');
   $lastMonth = date("n")-1;
   $lastMonth = $lastMonth . '/01/'. $startYear;
   $twoMonth = date("n")-2;
   $twoMonth = $twoMonth . '/01/'.$startYear;

/*
   $span = date("F Y");
   $dateStart = date('F',strtotime($lastMonth));
   $pdf->Cell(0,8,"Balance summary $span",0,1,'C'); 
   $pdf->SetFillColor(200);
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(40,8,'Beginning Balance',0,0,'L',1);
   $pdf->Cell(20,8,'Charges',0,0,'L',1);
   $pdf->Cell(25,8,'Payments',0,0,'L',1);
   $pdf->Cell(35,8,'Ending Balance',0,0,'L',1);
   //$pdf->SetFillColor(255,0,0);
   $pdf->SetFont('Arial','B','14');   
   $pdf->Cell(35,8,'Amount Due',0,1,'L',1);
   $pdf->SetFont('Arial','','12');
   
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(40,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(20,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",0),0,0,'L');
   $pdf->Cell(35,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Ln(20);
*/

   $priorQ = "SELECT sum(charges) - sum(payments) FROM ar_history
		WHERE datediff(dd,getdate(),tdate) < -90
		AND card_no = $selAddW[0]";
   $priorR = $sql->query($priorQ);
   $priorBalance = array_pop($sql->fetch_row($priorR));

   $pdf->Cell(20,8,'');
   $pdf->SetFillColor(200);
   $pdf->SetFont('Arial','B','12');   
   $pdf->Cell(40,8,'Balance Forward',0,0,'L',1);
   $pdf->SetFont('Arial','','12');   
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$priorBalance),0,0,'L');
   $pdf->Ln(8);
 
   $date2Month = date('F',strtotime($twoMonth));
   $pdf->Cell(0,8,"90-Day Billing History",0,1,'C');
   $pdf->SetFillColor(200);
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(60,8,'Date',0,0,'L',1);
   $pdf->Cell(30,8,'Receipt',0,0,'L',1);
   $pdf->Cell(25,8,'',0,0,'L',1);
   $pdf->Cell(25,8,'Amount',0,1,'L',1);
 
   //$selTransQ = "SELECT * FROM ar_history WHERE datediff(mm,getdate(),tdate) = -1
   //              AND card_no = $selAddW[0]";

   $prevD = "";
   $prevT = "";
   $prev = "";
   if($selTransN == 0){
      $date = '';
      $trans = '';
      $charges = '0.00';
      $payment = '0.00';

      $pdf->Cell(20,8,'',0,0,'L');
      $pdf->Cell(60,8,$date,0,0,'L');
      $pdf->Cell(30,8,$trans,0,0,'L');
      $pdf->Cell(25,8,'',0,0,'L');
      if ($charges != 0)
	      $pdf->Cell(25,8,'$ ' . $charges,0,0,'L');
      elseif ($payments != 0)
	      $pdf->Cell(25,8,'($ ' . $payments.")",0,0,'L');
      $pdf->Ln(5);
   } 

   $gazette = False;
   $first = True;
   $isPayment = False;

   $lineitem="";
   while($selTransW = $sql->fetch_row($selTransR)){
	if ($selTransW[0] != $selAddW[0]){
		mssql_data_seek($selTransR,$rowNum);
		break;
	}
	else $rowNum++;

	if ($selTransW[5] == "Gazette Ad" || $selTransW[6] == "Gazette Ad")
		$gazette = True;	

      if($selTransN != 0){
	 $date = $selTransW[3];
         $trans = $selTransW[4];
         $charges = $selTransW[1];
         $payment =  $selTransW[2];
      
         //list($year, $month, $day) = split("-", $date);
         //$date = date('M-d-Y', mktime(0, 0, 0, $month, $day, $year));
     }
      if ($date != $prevD || $trans != $prevT){
		if (!empty($lineitem)){
			$pdf->SetFontSize(10);
			$pdf->Cell(30,8,'',0,0,'L');
			$pdf->Cell(60,8,$lineitem,0,0,'L');
			if ($pdf->GetY() > 265){
				addBackPage($pdf);
				$pdf->AddPage();
			}
			else
				$pdf->Ln(5);
			$pdf->SetFontSize(12);
		}
		$lineitem = "";
		$prev = "";

	      $pdf->Cell(20,8,'',0,0,'L');
	      $pdf->Cell(60,8,$date,0,0,'L');
	      //$pdf->Cell(40,8,date('M-d-Y',$date),0,0,'L');
	      $pdf->Cell(55,8,$trans,0,0,'L');
		if ($payment > $charges)
		      $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$payment-$charges),0,0,'L');
		else
		      $pdf->Cell(25,8,'$ ' . sprintf('(%.2f)',abs($payment-$charges)),0,0,'L');
		if ($pdf->GetY() > 265){
			addBackPage($pdf);
			$pdf->AddPage();
		}
		else
		      $pdf->Ln(5);

      }
      if ($selTransW[5] != "" && $selTransW[5] != $prev){
        $lineitem = (empty($lineitem))?$selTransW[5]:'(Multiple items)';
        $prev = $selTransW[5];
      }
      elseif ($selTransW[6] != "" && $selTransW[6] != $prev){
        $lineitem = (empty($lineitem))?$selTransW[6]:'(Multiple items)';
        $prev = $selTransW[6];
      }
      $prevD = $date;
      $prevT = $trans;
   }
	if (!empty($lineitem)){
		$pdf->SetFontSize(10);
		$pdf->Cell(30,8,'',0,0,'L');
		$pdf->Cell(60,8,$lineitem,0,0,'L');
		if ($pdf->GetY() > 265){
			addBackPage($pdf);
			$pdf->AddPage();
		}
		else
			$pdf->Ln(5);
		$pdf->SetFontSize(12);
	}

   $pdf->Ln(15);
   $pdf->Cell(20,8,'');
   $pdf->SetFillColor(200);
   $pdf->SetFont('Arial','B','14');   
   $pdf->Cell(35,8,'Amount Due',0,0,'L',1);
   $pdf->SetFont('Arial','','14');   
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');

   if ($gazette){
	$pdf->Image($FANNIE_ROOT.'legacy/images/WFCLogoCThru1.jpg',75,214,50,25);

	$pdf->SetY(205);
	$pdf->Cell(0,8,'','B',1);
	$pdf->Ln(5);
	
	$pdf->Cell(30,5,'Whole Foods Co-op');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Invoice Date:',0,0,'R');
	$pdf->Cell(20,5,date("m/d/Y"),0,1,'L');
	$pdf->Cell(30,5,'610 East 4th Street');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Customer Number:',0,0,'R');
	$pdf->Cell(20,5,$selAddW[0],0,1,'L');
	$pdf->Cell(30,5,'Duluth, MN 55805');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Invoice Total:',0,0,'R');
	$pdf->Cell(20,5,$selAddW[7],0,1,'L');

	$pdf->Ln(5);
	$pdf->Cell(10,10,trim($selAddW[0]),0);
	$pdf->Ln(5);
	$pdf->Cell(50,10,trim($selAddW[1]),0);
	$pdf->Ln(5);
	$pdf->Cell(80,10,$selAddW[2],0);
	$pdf->Ln(5);
	if($selAddW[3]!= ''){  //if there is an address2 add it
	$pdf->Cell(80,10,$selAddW[3],0);
	$pdf->Ln(5);
	}
	$pdf->Cell(90,10,$selAddW[4] . ', ' . $selAddW[5] . '   ' . $selAddW[6],0);

	$pdf->SetXY(80,240);
	$pdf->SetFontSize(10);
	$pdf->MultiCell(110,6,"( ) Please continue this ad in the next issue.
( ) I would like to make some changes to my ad for the next issue.
( ) I do not wish to continue an ad in the next issue.
( ) I will contact you at a later date with my advertising decision.");
	$pdf->Ln(3);
	
	$pdf->SetFontSize(12);
	$pdf->Cell(0,8,'Please Return This Portion With Your Payment',0,0,'C');

   }

   addBackPage($pdf);
}

/*
$pdf->AddPage();

while($selAdd1W = $sql->fetch_row($selAddR)){
   $cell = $selAdd1W[0] . '  ' . $selAdd1W[1] . ', ' . $selAdd1W[2];
   $pdf->Cell(0,5,$cell,0,1);
}
*/
$pdf->Output('makeStatement.pdf','D');

function addBackPage($pdf){
	return;
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',16);
	$pdf->SetTextColor(105);
	//$pdf->Cell(0,10,'',0,1,'C');
	$pdf->Cell(0,10,'IOU POLICY',0,1,'C');
	//$pdf->Ln(5);
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(0,10,'OF WHOLE FOODS COMMUNITY CO-OP, INC.',0,1,'C');
	$pdf->SetFont('Arial','',10);
	$txt = "WFC members may charge purchases to a maximum of $20.00 payable within two (2) weeks from the date incurred. IOU's must be signed by the maker. IOU's may not, under any circumstances, be paid with Food Stamps or EBT card. WFC asks that its members only use  the charge system for emergencies." ;

	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(5);

	$txt = "-Members with an IOU account credit balance will receive a 
		 reminder of that balance on each purchase receipt.
	 -Members with an IOU debit balance will receive a reminder
	  of that balance on each purchase receipt.

	If WFC is not reimbursed by a member within sixty (60) days from the date of an overdue IOU for the amount of that person's membership may be terminated by the Board and any remaining stock, after reimbursement for all indebtedness owed to WFC, will be converted to non-voting Class B stock.

	If WFC is not reimbursed by a member within sixty (60) days from the date of a bounced check for the amount of that check plus the amount of any administrative fee, that person's membership may be terminated by the Board and any remaining stock, after reimbursement for all indebtedness owed to WFC, will converted to non-voting Class B stock.  

	IOU credit balances over sixty (60) days will be credited to the Member's non-voting Class B stock and the IOU account will be adjusted to zero.   Members may request the return of Class B stock in excess of the amount required by the By-Laws by submitting to the Board a Request to Terminate that excess stock.

	At the discretion of the General Manager, member business and non-profit agency accounts may have higher IOU limits and/or extended payment terms.
	";
	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(1);

	$txt="Special Orders";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,20,'',0,0);

	$txt = "Special orders not picked up or paid for within thirty (30) days of the time items are received at WFC will be put out for sale or disposed of at management discretion.  Future special orders from members or from non-members who have not previously promptly paid for and/or picked up special orders, at management discretion, may require prepayment.";
	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(2);

	$txt="Newsletter Ads";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,20,'',0,0);

	$txt = "Members may charge the cost of advertising their business in WFC's newsletter under the same IOU payment terms as noted above but on an IOU account separate from the member's IOU account for inventory purchases.   

	Members will be mailed an invoice within ten (10) days of the date of publication for the amount of the advertising charge.  Failure to pay the amount due is then subject to the provisions of this IOU policy.
	";

	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(0);

	$txt="NOTE";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,15,'',0,0);

	$txt = 
	"Memberships with IOUs and/or other credit problems in excess of sixty (60) days may be placed on inactive status by management pending Board action.  Purchases by inactive members will not be recorded and will not count toward eligibility for a patronage rebate.   Purchases by inactive members are not eligible for member discounts or member specials.
	Memberships inactivated or terminated due to credit problems will be eligible for reactivation subject to Board discretion with respect to access to member credit benefits.
	";

	$pdf->MultiCell(0,4,$txt);

	$pdf->SetFont('Arial','B',10);
	$txt = "
	Memberships inactivated or terminated due to credit problems will be eligible for reactivation subject to Board discretion with respect to access to member credit benefits.";
	$pdf->Cell(15,20,'',0,0);
	$pdf->MultiCell(0,5,$txt);

	$pdf->Ln(5);
	$pdf->SetTextColor(0);
	$pdf->SetFont('Arial','',10);
}

?>