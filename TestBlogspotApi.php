<?php

set_time_limit(180);
ini_set('memory_limit','512M');

$multi_post = new multi_post_blogger("http://localhost/blog-autopost/bot/TestBlogspotApi.php");

// mengambil token google client OAUTH2
$token = $multi_post->getToken();

echo "<hr>";
$blog_list = $multi_post->getBlogList();
foreach ($blog_list as $blog) {
  var_dump($blog["url"]);
}