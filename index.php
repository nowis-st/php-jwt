<?php
/*
 * JWT for php
 * See: https://github.com/firebase/php-jwt
 * RFC: https://tools.ietf.org/html/rfc7519
 */

require_once('./vendor/autoload.php');

use \Firebase\JWT\JWT;

/**
 * Call REST API
 * @param string $method The HTTP method
 * @param string $url    The url
 * @param mixed  $data   Optional: Datas
 */
function CallAPI($method, $url, $data = false) {
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json'
                ));
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

// Get secrets datas from JSON
$jwtSecret = file_get_contents('./jwt_secret.json');
$jwtSecret = json_decode($jwtSecret);

// Preapre JWT datas. See RFC for more informations
$tokenId    = base64_encode(mcrypt_create_iv(32));
$issuedAt   = time();
$notBefore  = $issuedAt + 10;
$expire     = $notBefore + 120;
$serverName = 'localhost';

$data = [
  'iat'  => $issuedAt,         // Issued at: time when the token was generated
  'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
  'iss'  => $serverName,       // Issuer: the name or identifier of the issuer application
  'nbf'  => $notBefore,        // Not before: Timestamp of when the token should start being considered valid. Should be equal to or greater than iat
  'exp'  => $expire,           // Expire: Timestamp of when the token should cease to be valid. Should be greater than iat and nbf
  'data' => [                  // Data related to the signer user
    'text'   => 'this is a banana'
  ]
];

$jsonDatas = json_encode($data);

// Create the JSON Web Token
$jwt = JWT::encode($jsonDatas, $jwtSecret->secret, $jwtSecret->algo);
echo "JWT: ".$jwt.'<br>';

$decoded = JWT::decode($jwt, $jwtSecret->secret, array($jwtSecret->algo));
echo "JWT decoded: ". var_dump($decoded) ."<br>";

$json = [
  'authType' => 'jwt',
  'jwt' => $jwt
];

// Sleep because of nbf greater than iat of 10 seconds.
sleep(10);

// Call the REST API
$res = CallAPI('POST','http://127.0.0.1:9000/file', json_encode($json));

if( !$res ) {
  echo "Error: something appends !";
}
else {
  echo $res;
}

?>
