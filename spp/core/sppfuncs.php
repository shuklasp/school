<?php
function tsToD($ts)
{
	$dt=getdate($ts);
	return $dt['mday'].'/'.$dt['mon'].'/'.$dt['year'];
}

function tsToHHMM($ts)
{
	return date('G:i',$ts);
}


function getVisitorIP()
{
    //GLOBALS OFF WORK ROUND
    if (!ini_get('register_globals')) {
        $reg_globals = array($_POST, $_GET, $_FILES, $_ENV, $_SERVER, $_COOKIE);
        if (isset($_SESSION)) {
            array_unshift($reg_globals, $_SESSION);
        }
        foreach ($reg_globals as $reg_global) {
            extract($reg_global, EXTR_SKIP);
        }
    }

    //FIND THE VISITORS IP
     if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
     {
        $rip = getenv("HTTP_CLIENT_IP");
     }
     else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
     {
        $rip = getenv("HTTP_X_FORWARDED_FOR");
     }
     else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
     {
        $rip = getenv("REMOTE_ADDR");
     }
     else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
     {
        $rip = $_SERVER['REMOTE_ADDR'];
     }
     else
     {
        $rip = "unknown";
     }

//RETURN THE VISITORS IP
    return $rip;
}

function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
    /*
    # $interval can be:
    # yyyy - Number of full years
    # q - Number of full quarters
    # m - Number of full months
    # y - Difference between day numbers
    # (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
    # d - Number of full days
    # w - Number of full weekdays
    # ww - Number of full weeks
    # h - Number of full hours
    # n - Number of full minutes
    # s - Number of full seconds (default)
    # */
    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto = strtotime($dateto, 0);
    }
    $difference = $dateto - $datefrom; // Difference in seconds
    switch($interval) {
        case 'yyyy': // Number of full years

            $years_difference = floor($difference / 31536000);
            if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                $years_difference--;
            }
            if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                $years_difference++;
            }
            $datediff = $years_difference;
            break;

        case "q": // Number of full quarters

            $quarters_difference = floor($difference / 8035200);
            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }
            $quarters_difference--;
            $datediff = $quarters_difference;
            break;

        case "m": // Number of full months

            $months_difference = floor($difference / 2678400);
            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }
            $months_difference--;
            $datediff = $months_difference;
            break;

        case 'y': // Difference between day numbers

            $datediff = date("z", $dateto) - date("z", $datefrom);
            break;

        case "d": // Number of full days

            $datediff = floor($difference / 86400);
            break;

        case "w": // Number of full weekdays

            $days_difference = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day = date("w", $datefrom);
            $days_remainder = floor($days_difference % 7);
            $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }
            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }
            $datediff = ($weeks_difference * 5) + $days_remainder;
            break;

        case "ww": // Number of full weeks

            $datediff = floor($difference / 604800);
            break;

        case "h": // Number of full hours

            $datediff = floor($difference / 3600);
            break;

        case "n": // Number of full minutes

            $datediff = floor($difference / 60);
            break;

        default: // Number of full seconds (default)

            $datediff = $difference;
            break;
    }

    return $datediff;

}

/*function showValidPayTypeDropDown($memid,$cname='paytype',$onchange='',$disabled=false)
{
    $mem=new Member($memid);
    if($disabled==true)
    {
        $html='<select id="'.$cname.'" name="'.$cname.'" onchange="'.$onchange.'" disabled="true">';
    }
    else
    {
        $html='<select id="'.$cname.'" name="'.$cname.'" onchange="'.$onchange.'">';
    }
    $html.='<option value="0">Select</option>';
    if($mem->get('PolicyPaymentEligible'))
    {
        $html.='<option value="1">Policy Fee</option>';
    }
    if($mem->get('MembershipPaymentEligible'))
    {
        $html.='<option value="2">Membership Fee</option>';
    }
    $html.='</select> ';
    return $html;
}*/

function sql_date_shift($date, $shift)
{
    return date("Y-m-d H:i:s" , strtotime($shift, strtotime($date)));
}

function date_shift($date, $shift)
{
    return date("Y-m-d" , strtotime($shift, strtotime($date)));
}

/*function exportChain($parid,$rowval,$colval)
{
    $db=new Database();
    $sql='select memberid, mname from member_master where memberid<>\'1000M0000000000\' and introcode=?';
    $result=$db->execute_query($sql, Array($parid));
    $db=0;
    if(sizeof($result)<=0)
    {
        return ++$rowval;
    }
    else
    {
        foreach($result as $res)
        {
            xlsWriteLabel($rowval++,$colval,$res['memberid'].'('.$res['mname'].')');
            $rowval=exportChain($res['memberid'], $rowval, $colval+1);
            //$rowval++;
        }
    }
}*/

/*function exportChain($parid,$col,$color)
{
    $db=new Database();
    $sql='select memberid, mname from member_master where memberid<>\'1000M0000000000\' and introcode=?';
    $result=$db->execute_query($sql, Array($parid));
    $db=0;
    if(sizeof($result)<=0)
    {
        return ++$col;
    }
    else
    {
        foreach($result as $res)
        {
            echo '<tr bgcolor="'.$color.'">';
            for($i=0;$i<$col;$i++)
            {
                echo '<td></td>';
            }
            echo '<td bgcolor="'.($col/2==ceil($col/2)?'red':'green').'">'.$res['memberid'].'('.$res['mname'].')'.'</td>';
            echo '</tr>';
            //xlsWriteLabel($rowval++,$colval,$res['memberid'].'('.$res['mname'].')');
            exportChain($res['memberid'], $col+1, $color);
            //$rowval++;
        }
    }
}*/

?>