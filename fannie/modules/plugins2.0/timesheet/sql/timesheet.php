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

$PLUGIN_CREATE['timesheet'] = "
	CREATE TABLE timesheet (
	emp_no INT,
	hours DOUBLE,
	area INT,
	tdate DATETIME,
	periodID INT,
	ID INT NOT NULL AUTO_INCREMENT,	
	VACATION DECIMAL(10,2),
	tstamp TIMESTAMP, 
	PRIMARY KEY (ID)
	)
";

if ($FANNIE_SERVER_DBMS == "MSSQL"){
	$PLUGIN_CREATE['timesheet'] = str_replace('NOT NULL AUTO_INCREMENT',
		'IDENTITY (1, 1) NOT NULL',
		$PLUGIN_CREATE['timesheet']);
}

?>
