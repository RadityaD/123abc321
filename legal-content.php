<?php session_start(); ?>
<?php
/*
Plugin Name: TKPD HelpCenter Legal Content Form Plugins
Description: TKPD HelpCenter Legal Content Form Plugins
Bitbucket Plugin URI: 
Bitbucket Plugin URI: 
*/

require_once __DIR__ . '/sheetAPI/vendor/autoload.php';

define('APPLICATION_NAME', 'TKPD - LEGAL CONTENT FORM');
define('CREDENTIALS_PATH', './client-credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/sheets.googleapis.com-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Sheets::SPREADSHEETS)
));

function upload_user_file($file = array()) {
    require_once( ABSPATH . '/wp-admin/includes/file.php' );
    $uploadedfile = $file;

    $upload_overrides = array( 
                            'test_form' => false, 
                            'mimes' => array(
                                'txt' => 'text/plain',
                                'pdf' => 'application/pdf',
                                'doc' => 'application/msword',
                                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'jpg|jpeg|jpe' => 'image/jpeg'
                            ) 
                        );
    
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
    
    if ( $movefile && ! isset( $movefile['error'] ) ) {
        echo "File is valid, and was successfully uploaded.\n";
        return $movefile;
    } else {
        /**
         * Error generated by _wp_handle_upload()
         * @see _wp_handle_upload() in wp-admin/includes/file.php
         */
        return $movefile['error'];
    }
    
}

function legal_content_submission() {
    //Ambil dan Sanitasi Data
    $user_name = isset($_POST['your_name']) ? filter_var($_POST['your_name'], FILTER_SANITIZE_STRING) : ''; //Required
    $user_company = isset($_POST['your_company']) ? filter_var($_POST['your_company'], FILTER_SANITIZE_STRING) : '';
    $user_title = isset($_POST['your_title']) ? filter_var($_POST['your_title'], FILTER_SANITIZE_STRING) : '';

    $user_email = isset($_POST['your_email']) ? filter_var($_POST['your_email'], FILTER_SANITIZE_EMAIL) : ''; //Required
    $user_phone = isset($_POST['your_phone']) ? filter_var($_POST['your_phone'], FILTER_SANITIZE_STRING) : ''; //Required
    $user_report = isset($_POST['your_phone']) ? filter_var($_POST['your_phone'], FILTER_SANITIZE_STRING) : ''; //Required
    $user_doc = isset($_FILES['your_doc']) ? $_FILES['your_doc'] : 'null'; //Required

    $user_captcha = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $user_currurl = isset($_POST['current_url']) ? $_POST['current_url'] : '';
    $user_attachment = upload_user_file( $_FILES['your_doc'] );
    $secretKey = '6LeIChsUAAAAAFU-k93h3nzTqq2vY5RFj1U3IQtM'; // Captcha, to be added later
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // DEBUG CODE
    // echo 'name : '.$user_name;
    // echo '<br>company: '.$user_company;
    // echo '<br>title: '.$user_title;

    // $brandCount = count($_POST['your_brand']);
    // for($i = 0; $i < $brandCount; $i++) {
    //     echo '<br>brand-'.$i.': '.$_POST['your_brand'][$i];
    //     echo '<br>brandID-'.$i.': '.$_POST['your_brandid'][$i];
    //     echo '<br>brandLINK-'.$i.': '.$_POST['your_productlink'][$i].'<br>';
    // }

    // echo '<br>email: '.$user_email;
    // echo '<br>phone: '.$user_phone;
    // echo '<br>report: '.$user_report;
    // echo '<br>document: '.$user_doc;

    //Validasi
    $boolError = true;
    $boolInfo = '<strong>Sucessful!</strong> You Have Submitted Your Documents';

    if(!$user_name) {
        $boolError = false;
        $boolInfo = 'Please, Insert Your Name';
    }
    else {
        if(!$user_email) {
            $boolError = false;
            $boolInfo = 'Incorrect Email Format';
        }
        else {
            if(!$user_phone) {
                $boolError = false;
                $boolInfo = 'Please, Insert Your Phone Number';
            }
            else {
                if(!$user_report) {
                    $boolError = false;
                    $boolInfo = 'Please, Insert or Select Your Report Type';
                }
                else {
                    if($user_attachment['error']) {
                        $boolError = false;
                        $boolInfo = 'Please, Attach Required Document';
                    }
                    else {
                        if(
                            !isset($_POST['sellerstory_form_nonce']) 
                            || !wp_verify_nonce($_POST['sellerstory_form_nonce'], 'sellerstory_form')
                          )
                        {
                            $boolError = false;
                            $boolInfo = 'Invalid Nonce!';
                        }
                        else {
                            $brandCount = count($_POST['your_brand']);
                            $arrBrands = [];
                            echo $brandCount;
                            for($i = 0; $i < $brandCount; $i++) {
                                echo $i;
                                if( 
                                    filter_var($_POST['your_brand'][$i], FILTER_SANITIZE_STRING) == '' || 
                                    filter_var($_POST['your_productlink'][$i], FILTER_VALIDATE_URL) == ''
                                ) {
                                    $boolError = false;
                                    $boolInfo = 'Please, Insert Brand Name and Product Link';
                                }
                                else {
                                    $bN = (string) 'Brand Name '.($i+1);
                                    $bID = (string) 'Brand ID '.($i+1);
                                    $bL = (string) 'Brand Link '.($i+1);
                                    $arrBrands[$bN] = $_POST['your_brand'][$i];
                                    $arrBrands[$bID] = $_POST['your_brandid'][$i];
                                    $arrBrands[$bL] = $_POST['your_productlink'][$i].chr(10);
                                }
                            }
                            print_r($arrBrands);
                            $arrData = [];
                            $theBrands = http_build_query($arrBrands, '', ''.chr(10));
                            var_dump($theBrands);  
                            array_push($arrData, $user_name,  $user_company, $user_title, urldecode($theBrands), $user_email, $user_phone, $user_report, $user_attachment['url']);
                            print_r($arrData);
                            postData($arrData);
                        }
                    }
                }
            }
        }
    }

    if($boolError == false) {
        $_SESSION["error"] = $boolInfo;
        $_SESSION["stats"] = $boolError;

        // DEBUG CODE (comment wp_redirect)
        // echo $_SESSION["error"];
        // echo $_SESSION["stats"];

        wp_redirect($user_currurl.'#seller-form');
    }
    else {
        $_SESSION["error"] = $boolInfo;
        $_SESSION["stats"] = $boolError;

        // DEBUG CODE (comment wp_redirect)
        // echo $_SESSION["error"];
        // echo $_SESSION["stats"];

        wp_redirect($user_currurl.'#seller-form');
    }
}
add_action( 'admin_post_nopriv_legalcontent_submit', 'legal_content_submission' );
add_action( 'admin_post_legalcontent_submit', 'legal_content_submission' );

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $credentials_file = __DIR__.'/legal-content.json';
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAccessType('offline');

  if ($credentials_file) {
    // set the location manually
    $client->setAuthConfig($credentials_file);
    return $client;
  } elseif (getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
    // use the application default credentials
    $client->useApplicationDefaultCredentials();
  } else {
    echo missingServiceAccountDetailsWarning();
    return;
  }
}

// Get the API client and construct the service object.
function postData($data = []) {
  $client = getClient();
  $service = new Google_Service_Sheets($client);

  // Prints the names and majors of students in a sample spreadsheet:
  // https://docs.google.com/spreadsheets/d/1s4ig5n0VxtEM-jxQhOkX6gls1i1vrw07SwpxxduFbNM/edit
  $spreadsheetId = '1s4ig5n0VxtEM-jxQhOkX6gls1i1vrw07SwpxxduFbNM';
  $asSpreadsheetRows = $data;
  $body = new Google_Service_Sheets_ValueRange(array(
    'values' => array($asSpreadsheetRows)
  ));
  $params = array(
    'valueInputOption' => 'USER_ENTERED'
  );

  $range = 'A1:H1';
  $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
  }


