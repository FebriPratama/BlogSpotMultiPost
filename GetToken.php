<?php

define( 'ABSPATH', dirname(__FILE__) . '/' );
require_once ABSPATH . 'MultiPostBlogger.php';

$multi_post = new multi_post_blogger("http://localhost/blogspot-autopost/GetToken.php");

// mengambil token google client OAUTH2
$token = $multi_post->getToken();

echo json_encode($token);

echo "<hr>";

$blog_list = $multi_post->getBlogList();
foreach ($blog_list as $blog) {
  echo $blog["url"] ."<br>";
}