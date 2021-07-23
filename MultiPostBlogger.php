<?php

require_once ABSPATH . 'vendor/autoload.php';

class multi_post_blogger
{
  private $g_client = NULL;
  private $api_key  = NULL;
  private $callback_url = NULL;

  function __construct($callback_url = "http://localhost/blogspot-autopost/GetToken.php", $api_key = "AIzaSyCLSev9MudfkIvlA-MGd2jkSO2vgAhJ520", $token = array())
  {
    // authorisasi google OAUTH 2
    // parameter $callback_url harus menuju ke alamat redirect yang diatur di developer console
    // parameter $api_key diisi API key dari developer console
    $this->api_key = $api_key;
    $this->callback_url = $callback_url;
    session_start();

    $client = new Google_Client();
    $client->setAuthConfig('blogspot_api_febri.json'); // lokasi file client_secret.json
    $client->addScope('https://www.googleapis.com/auth/blogger');
    $client->addScope('https://www.googleapis.com/auth/userinfo.profile');
    $client->setRedirectUri($callback_url);
    $client->setAccessType('offline');        // offline access
    $client->setIncludeGrantedScopes(true);   // incremental auth
    $client->setDeveloperKey($api_key); // API key

    $tokenTmp = array();

    if (isset($_GET['code'])) { // we received the positive auth callback, get the token and store it in session

      // matikan ssl
      $guzzleClient = new \GuzzleHttp\Client(array( 'curl' => array( CURLOPT_SSL_VERIFYPEER => false, ), ));
      $client->setHttpClient($guzzleClient);

      $client->authenticate($_GET['code']);

      // authentikasi
      $_SESSION['token'] = $client->getAccessToken();

      header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

    }

    if (!empty($token) || isset($_SESSION['token'])) { // extract token from session and configure client

        $tokenReal = !empty($token) ? $token : $_SESSION['token'];
        $client->setAccessToken($tokenReal);

    }

    if (!$client->getAccessToken()) { // auth call to google
      $authUrl = $client->createAuthUrl();
      header("Location: ".$authUrl);
      die;
    }

    $this->g_client = $client;
    return $this->g_client;
  }

  public function reloadAuthentication()
  {
    // untuk login ulang (belum bisa)
    $this->client = NULL;
    $callback_url = $this->callback_url;
    $api_key = $this->api_key;
    $this->__construct($callback_url,$api_key);
  }

  public function getToken()
  {
    // ambil access_token dari variabel client
    $token = $this->g_client->getAccessToken(); //token client
    return $token;
  }

  public function getUser($user_id='self')
  {
    // ambil informasi profil user
    $token = $this->getToken();
    $ch = curl_init();
    $url = 'https://www.googleapis.com/blogger/v3/users/'.$user_id;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$token["access_token"].'',
      'Accept: application/json',
      'Content-Type: application/json',
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    if ($result === FALSE) {
      echo "ada error curl berikut : ".curl_error($ch);
    }
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $bentuk_json = json_decode($result,TRUE);
    return $bentuk_json;
  }

  public function getBlogList($user_id='self')
  {
    // ambil data daftar blog yang dimiliki user
    // parameter $user diisi user ID
    $token = $this->getToken();
    $ch = curl_init();
    $url = 'https://www.googleapis.com/blogger/v3/users/'.$user_id.'/blogs';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$token["access_token"].'',
      'Accept: application/json',
      'Content-Type: application/json',
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    if ($result === FALSE) {
      echo "ada error curl berikut : ".curl_error($ch);
    }
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $bentuk_json = json_decode($result,TRUE);
    $blog_list = $bentuk_json["items"];
    return $blog_list;
  }

  function getBlogId($blog_url='')
  {
    // ambil data id blog (string) dengan mengambil url blog
    // parameter $blog_url diisi alamat blog (string) lengkap dengan http://
    $url = 'https://www.googleapis.com/blogger/v3/blogs/byurl?url='.$blog_url.'&key='.$this->api_key;
    $result = file_get_contents($url);
    $hasil = json_decode($result, true);
    return $hasil['id'];
  }

  public function inputPostBanyak($blog_list = array(), $title = '', $content = '')
  {
    // kirim HTTP POST request ke blogger api
    // untuk memposting tulisan ke banyak blog milik satu akun google
    //
    // parameter $blog_list diisi associative array berisi daftar url dan id blog
    // url didapat dari key "url" dalam associative array
    // id blog didapat dari key "id" dalam associative array
    //
    // parameter $title berisi judul tulisan (string)
    // parameter $content berisi konten tullisan (string)
    $status = array();
    foreach ($blog_list as $blog) {
      $blog_id = $blog["id"];
      $token = $this->getToken();
      $ch = curl_init();
      $url = 'https://www.googleapis.com/blogger/v3/blogs/'.$blog_id.'/posts';
      $data_inputan = array(
        'title' => $title,
        'content' => $content
      );
      $data_inputan = json_encode($data_inputan);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$token["access_token"].'',
        'Accept: application/json',
        'Content-Type: application/json',
        'Content-Length: '.strlen($data_inputan),
      ));
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_inputan);
      $result = NULL;
      $result = curl_exec($ch);
      if ($result === FALSE) {
        echo "ada error curl berikut : ".curl_error($ch);
      }
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      $bentuk_json = json_decode($result,TRUE);
      $laporan_satuan = array(
        'blog_url' => $blog["url"],
        'blog_id'  => $blog_id,
        'http_code' => $httpcode,
        'http_response' => $bentuk_json
      );
      array_push($status,$laporan_satuan);
    } // akhir perulangan
    return $status;
  }

  public function inputPostSatuan($blog_id='', $title = '', $content = '')
  {
    // kirim HTTP POST request ke blogger api
    // untuk memposting tulisan ke satu blog milik satu akun google
    //
    // parameter $blog_id diisi blog ID yang dari blognya (string)
    //
    // parameter $title berisi judul tulisan (string)
    // parameter $content berisi konten tullisan (string)
    $token = $this->getToken();
    $ch = curl_init();
    $url = 'https://www.googleapis.com/blogger/v3/blogs/'.$blog_id.'/posts';
    $data_inputan = array(
      'title' => $title,
      'content' => $content
    );
    $data_inputan = json_encode($data_inputan);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$token["access_token"].'',
      'Accept: application/json',
      'Content-Type: application/json',
      'Content-Length: '.strlen($data_inputan),
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_inputan);
    $result = NULL;
    $result = curl_exec($ch);
    if ($result === FALSE) {
      echo "ada error curl berikut : ".curl_error($ch);
    }
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $bentuk_json = json_decode($result,TRUE);
    $status = array(
      'blog_id'  => $blog_id,
      'http_code' => $httpcode,
      'http_response' => $bentuk_json
    );
    return $status;
  }

}