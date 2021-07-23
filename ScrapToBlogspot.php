<?php

set_time_limit(180);
ini_set('memory_limit','512M');

define( 'ABSPATH', dirname(__FILE__) . '/' );

require_once ABSPATH . 'vendor/autoload.php';
require_once ABSPATH . 'MultiPostBlogger.php';

use Intervention\Image\ImageManagerStatic as Image;
use Buchin\GoogleImageGrabber\GoogleImageGrabber;
use Buchin\SentenceFinder\SentenceFinder;

function duplicateCheck($datas,$tmp,$total){

  return array('posts'=>$tmp,'datas'=>$datas);  

}

function removeSpecial($string){

    $string = implode(' ',array_unique(explode(' ',str_replace('.','',$string))));
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
 
    $string = preg_replace('/-+/', '-', $string);
   
    return str_replace('-', ' ', $string);
 
}

function filterKeywords($total){

    $r = array();
    $filepath = ABSPATH . 'keywords.txt';
  
    /*===== settings STOP  =====*/
    $flags_runinject = array();
    $kread = '';
  
    // check if file exists
    if( ! file_exists( $filepath ) ) {
      $flags_runinject[] = FALSE;
    } else {
      $kread = file_get_contents($filepath);  // read keyword inject file
    }
    
    // check if file is not empty
    if( strlen($kread) < 2 ) {
      $flags_runinject[] = FALSE;
    }
  
    // only run if no FALSE found in $flags_runinject
    if( ! in_array(FALSE, $flags_runinject) ) {
  
      $kreads = explode("\n", $kread);
      
      $tmp = array();
  
      foreach($kreads as $k){
        
          $tmp[] = $k;
      
      } 
  
      if(count($tmp) > 0){
        
        for($i=0;$i<$total;$i++){
  
          $r[] = $tmp[$i];
          unset($tmp[$i]);   
  
        }
  
      }
  
      //check duplicate
      $c = duplicateCheck($tmp,$r,$total);
  
      $r = $c['posts'];
      $tmp = $c['datas'];
  
      // prepare sliced data for file writing
      $kread_sliced = implode("\n", $tmp);
  
      $fh = fopen($filepath, "wb+");
  
      fwrite($fh, $kread_sliced);
      fclose($fh);
      
    }
  
    return $r;
  
}

function clean($str, $replace=array(), $delimiter='-') {
  if( !empty($replace) ) {
    $str = str_replace((array)$replace, ' ', $str);
  }

  $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
  $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
  $clean = strtolower(trim($clean, '-'));
  $clean = trim($clean);
  $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

  return trim($clean);
}

function suffleTitleMask($string){

  $bungkus = array(
    '<h2 class="single-header" itemprop="name">'.$string.'</h2>',
    '<h3 class="single-header" itemprop="name">'.$string.'</h3>',
    '<h4 class="single-header" itemprop="name">'.$string.'</h4>',
    '<h5 class="single-header" itemprop="name">'.$string.'</h5>',
    '<h6 class="single-header" itemprop="name">'.$string.'</h6>',
    '<p class="caption" itemprop="name"><strong>'.$string.'</strong></p>',
    '<p class="caption" itemprop="name"><u>'.$string.'</u>',
    '<p class="caption" itemprop="name"><em>'.$string.'</em>'
    );

  return $bungkus[array_rand($bungkus)];

}

/*===== settings START =====*/
$injectamount = isset($_GET['total']) ? $_GET['total'] : 1;
$imageperpost= isset($_GET['images']) ? $_GET['images'] : 30;
$proxy = isset($_GET['proxy']) ? $_GET['proxy'] : ''; 
$bdays = isset($_GET['bdays']) ? $_GET['bdays'] : 0; 
$sdays = isset($_GET['sdays']) ? $_GET['sdays'] : 0; 
$type = isset($_GET['type']) ? $_GET['type'] : 'backdate'; 
$hotlink = isset($_GET['hotlink']) ? $_GET['hotlink'] : 'on'; 

$r = filterKeywords($injectamount);

$tokens = array('febriarga.pratama@blogger.com');

$images = 0;
$errors = 0;
$tmp = array();
$url = array();

$dataContent = [];

$multi_post = new multi_post_blogger("http://localhost/blogspot-autopost/GetToken.php","AIzaSyCLSev9MudfkIvlA-MGd2jkSO2vgAhJ520",file_get_contents("token_argap.json"));
      
echo "<hr>";
$blog_list = $multi_post->getBlogList();

// only run if no FALSE found in $flags_runinject
if( count($r) ) {
  
    $awal = array('Stunning','Dazzling','Delightful','Trendy','Wonderful','Lovely','Charming','Good Looking','Outstanding','Attracktive','Engaging','Impressive','Gorgeous','Fascinating','Magnificent','Alluring','Excelent','Elegant','New', 'Cool', 'Unique', 'Nice', 'Luxury', 'Modest', 'Awesome', 'Amazing', 'Fresh', 'Popular', 'Awesome', 'Custom', 'Modern', 'Inspiring' , 'Simple', 'Classic');
    $lastUrl = "";
  
    foreach($r as $p){
      
          $p = removeSpecial($p);
  
          $images = GoogleImageGrabber::grab($p);
  
          $datas = [];

          foreach($images as $image){
          
              $exist = false;
          
              foreach($datas as $tmp){
                  if($image['url'] == $tmp['url']){
                      $exist = true;
                  }
              }
          
              if(!$exist){
                  $datas[] = array(
                    'title' => $image['title'],			
                    'url' => $image['url'],			
                    'height' => $image['height'],
                    'width' => $image['width'],			
                    'thumb' => $image['thumbnail']
                  );
              }
          
          }
  
        $ai = 0;

        $content = "";

        foreach($datas as $a) 
        {

            if(trim($a["url"]) !== ''){
                $content .= hotlinkImage($a, $imageperpost, $bdays, $sdays, $awal, $p, $type, $ai);
            }
  
            $ai++;
  
        } // foreach datas

        $contentData = array(
            'title' => ucwords( $p ),
            'body' => $content
        );

        $kirim = $multi_post->inputPostBanyak($blog_list,$contentData['title'],$contentData['body']);
        var_dump($kirim);
  
    }
  
    echo "<br>";
    echo json_encode([ 'post' => $url, 'images' => count($images), 'message' => 'Ok', 'erros' => $errors, 'tot' => count($r) ]);
    echo "<br>";
    echo "<br>";
  
  }else{
  
    echo json_encode([ 'post' => array(), 'images' => 0, 'message' => 'Keywords Sudah Habis', 'erros' => 0]);
  
  }

  function hotlinkImage($a, $imageperpost, $bdays, $sdays, $awal, $p, $type, $ai){

    $content = "";

        $title = ucwords($a['title']);

        $title = removeSpecial($title);
        
        $title = $awal[rand(0,29)].' '.$p.' '.$title;

        $date = strtotime(date('Y-m-d H:m:s'));
        $date = date('Y-m-d H:m:s', strtotime('+'.$bdays.' day', $date));

        // if($type == 'backdate'){

        //   $date = date('Y-m-d H:m:s', strtotime('-'.$bdays.' day', $date));

        // }else{

        //   $date = date('Y-m-d H:m:s', strtotime('+'.$sdays.' day', $date));

        // }

        $sqlChild = array(

          'term'  => trim( $title  ),
          'slug'  => trim( clean( $title ) ),
          'se'    => 'fileinject',
          'type' => 'child',
          'last_robot_access' => date('Y-m-d H:m:s'),
          'last_human_access' => $date,
          'access_count'      => '0',
          'term_status'       => '1'

          ); 

            /**
            *
            * IMAGE GRABBER
            * 
            **/
            $sqlImg = array(

                'term'  => $title,
                'url'  => $a['url'],
                'height' => $a['height'],
                'width' => $a['width'],
                'thumb' => $a['thumb'],
                'type'    => 'full'

                ); 
            
            $desc = "";
            
            if($ai == 0){

                $desc .= '<p  itemprop="description">';
                $url  = 'http://www.bing.com/search?q='.urlencode($p).'&format=rss&count=1';
                $data = simplexml_load_file($url);
                if(@simplexml_load_file($url)){
                    foreach($data->channel->item as $a){          
                
                        $desc .= strtolower($a->description)." ";
    
                    }
                }
                $desc .= '</p>'.'<!--more-->';

            }

            $content = '<div itemscope itemtype="http://schema.org/Product" style="margin-bottom:20px;"><div itemid="'.$a['url'].'" itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject"><img src="'.$a['url'].'" title="'.$title.'" width="'.$a['width'].'" height="'.$a['width'].'" alt="'.$title.'" itemprop="contentUrl" style="width: 100%;"/></div>'.suffleTitleMask($title).$desc.'<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"> Rated <span itemprop="ratingValue">'. rand(3,5).'</span>/5 based on <span itemprop="reviewCount">'. rand(10,15).'</span> customer reviews</div></div>';

        return $content;
    }