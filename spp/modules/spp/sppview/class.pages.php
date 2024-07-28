<?php
namespace SPPMod\SPPView;
use Symfony\Component\Yaml\Yaml;

class Pages extends \SPP\SPPObject{
    public static function getPage($page=null){
        $q = ($_GET['q']!=null)?$_GET['q']:$page;
        $file=APP_ETC_DIR.SPP_DS.'pages.yml';
        $yaml=Yaml::parseFile($file);
        $spl=explode('/', $q);
        $spl=$spl[0];
        //print_r($yaml['specials']);
        foreach($yaml['specials'] as $special){
            if($special['name']==$spl){
                //echo 'Pages::' . $special['method'] . '<br />';
                $page['url']= call_user_func('self::'.$special['method'], $q);
                $page['special']=1;
                return $page;
            }
        }
        //print_r($yaml['pages
        //print_r($yaml['pages']);
        foreach($yaml['pages'] as $page)
        {
            // echo $page['name'].'<br />';
            // echo substr_compare($page['name'], $q, 0, strlen($page['name'])).'<br />';
            if(substr_compare(trim($page['name']), $q, 0, strlen($page['name']))==0)
            {
                $url=$page['url'];
                $pg=[];
                if(str_starts_with(strtolower($url), '/'))
                {
                    $url=ltrim($url, '/');
                }
                $pg['url']=$url;
                $pg['name']=$page['name'];
                $pr='';
                if($page['name']!=$q){
                    // echo $page['name'].'<br />';
                    // echo $q.'<br />';
                    $pos = strpos($q, $page['name']);
                    // var_dump($pos);
                    if ($pos !== false) {
                        $pr = substr_replace($q, '', $pos, strlen($page['name']));
                    }
                    //$pr=trim(str_replace($page['name'], '', $q));
                    // echo $pr.'<br />';
                    if (str_starts_with(strtolower($pr), '/')) {
                        $pr = ltrim($pr, '/');
                    }
                    $params=explode('/', $pr);
                    $pg['params']=$params;
                }
                else{
                    $pg['params']=array();
                }
                $pg['named_params']=[];
                foreach($_GET as $parm=>&$value)
                {
                    if($parm=='q')
                    {
                        continue;
                    }
                    $pg['named_params'][$parm]=$value;
                }

                $pg['special'] = 0;
                return $pg;
            }
        }
        $arr=['page'=>$q];
        \SPP\SPPEvent::fireEvent('PageNotFound',$arr,function(){
            throw new \SPP\SPPException('Page not found');
        });
        $page=['url'=>'','params'=>[],'named_params'=>[]];
        $page['special'] = 0;
        return $page;
    }

    public static function getResource($url){
        $dir=self::getDefault('resdir');
        if (str_starts_with($dir, '/')) {
            $dir = ltrim($dir, '/');
        }
        if (str_starts_with($url, '/')) {
            $dir = ltrim($url, '/');
        }
        $spl=explode('/', $url);
        $spl=$spl[0];
        $url = substr_replace($url, '', 0, strlen($spl));
       return $dir.$url;
    }

    public static function getDefault($def){
        $file = APP_ETC_DIR . SPP_DS . 'pages.yml';
        $yaml = Yaml::parseFile($file);
        $defaults = $yaml['defaults'];
        if(array_key_exists($def, $defaults)){
            return $defaults[$def];
        }
        $arr['def']=$def;
        \SPP\SPPEvent::fireEvent('DefaultNotFound', $arr, function (&$arr) {
            throw new \SPP\SPPException('Default '.$arr['def'].' not found');
        });
        return false;
    }
}