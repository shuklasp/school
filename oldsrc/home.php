<?php
clearstatcache();
//$obj=new \SPP\Settings();
//$src=($obj->getSetting('src'));
//print_r($_SESSION);
\SPP\SPPError::destroyErrors();
require_once('server/class.person.php');
$p = new \School\Person();
//var_dump($p);
//var_dump($src);
//echo '<img src="'.$src[0]->dir.'/img/name-plate.jpeg" />';

    //  echo $var1;
    //  echo $var2;
    $var1='Dummy variable 1';
 $var2='Dummy variable 2';
                            $arr = array('var1'=>$var1,'var2'=>$var2);
  \SPP\SPPEvent::startEvent('test_event',$arr//,function(){
//     global $var1,$var2;
//      echo 'Inline event handler called';
//      echo $var1;
//      echo $var2;
//      $var1='Updated variable 1';
//      $var2='Updated variable 2';
//  }
 );
//  echo 'End of script';
//  echo $arr['var1'];
//  echo $arr['var2'];
//   $arr2=\SPP\SPPEvent::getParams('test_event');
  //print_r(\SPP\Registry::get('__events'));
//  echo $arr2['var1'];
//  echo $arr2['var2'];
//  echo 'End of script';
//  echo $var1;
//  echo $var2;
// ?>
<html>

<head>
    <title>Virtual Shiksha Community</title>
    <link href="lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/MDB5/css/mdb.min.css" rel="stylesheet">
    <script type="text/javascript" src="lib/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="lib/bootstrap/js/bootstrap.min.js"></script>"
    <script type="text/javascript">
        function getDomAsJson(element) {
            // Create an empty object to store the JSON data.
            const json = {};

            // Recursively traverse the DOM tree, adding each element to the JSON object.
            $(element).children().each(function() {
                const child = $(this);

                // Add the element's tag name to the JSON object.
                json[child.prop('tagName')] = {};

                // Add the element's attributes to the JSON object.
                $.each(child.prop('attributes'), function() {
                    json[child.prop('tagName')][this.name] = this.value;
                });

                // Recursively call the getDomAsJson function on the child element.
                json[child.prop('tagName')].children = getDomAsJson(child);
            });

            // Return the JSON object.
            return json;
        }

        // Get the JSON representation of the DOM tree.
        const json = getDomAsJson($('html'));

        // Do something with the JSON data.
        console.log(json);
    </script>
</head>

<body>
    <div id="menu-area">
        <?php require('comp/navbar.php'); ?>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-3" id="smenu-area"></div>
            <div class="col" id=main-region>
                <div class="container">
                    <div class="row" id="msg-area">
                        <div class="alert alert-warning alert-dismissible" role="alert" id="disp-msg">This is an alert
                            <span style="size: 50px;" align="right"><a name="" id="" data-bs-dismiss="alert" class="btn btn-primary" href="#" role="button">Dismiss</a></span>
                        </div>
                        <div class="row" id="working-area">
                            <?php //require('comp/login-form.php'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="lib/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="lib/MDB5/js/mdb.min.js"></script>
    <script src="js/home.js"></script>
    <script>
        //loadmain('src/comp/home-welcome.php');
    </script>
</body>

</html>