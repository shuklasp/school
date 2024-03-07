<?php
function ExcelGenerate()	
{
header("Content-Type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header('Content-Disposition: attachment; filename="report.xls"');
}
?>