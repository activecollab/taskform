<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body background="bg.jpg">
  <div align="center">
    <H1>SUCCESS</h1>
    </div>

    <?php
    $codeversion = $_POST['code-version'];
    $name = $_POST['name'];
    $url = $_POST['url'];
    $expected = $_POST['expected'];
    $actual = $_POST['actual'];
    $email = $_POST['reported-by'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $emailErr = "Invalid email format"; 
    }
    $desc = $_POST['desc'];
    $reproducible="";
    if(!empty($_POST['reproducible'])) {
      $reproducible = $_POST['reproducible'][0];
    }
    $browser="";
    if(!empty($_POST['browser'])) {
      foreach($_POST['browser'] as $check) {
        $browser .=$check."<br>";
      }
    }
    $prod="";
    $produced="";
    if(!empty($_POST['produced'])) {
      foreach($_POST['produced'] as $check1) {
        switch ($check1) {
          case 1:
          $prod .=" ,client cloud account";
          break;
          case 2:
          $prod .=" ,client self-hosted account";
          break;
          case 3:
          $prod .=" ,all cloud accounts";
          break;
          case 4:
          $prod .=" ,development activecollab";
          break; 
          case 5:
          $prod .=" ,everywhere";
          break; 
          default:
          $prod .="";
          break;
        }
      }
    }
    $prod = ltrim($prod, ',');
    switch ($reproducible) {
      case '1':
      $reproducible="Yes";
      break;
      case '2':
      $reproducible="Occasionally";
      break;
      case '3':
      $reproducible="One Time";
      break;
      case '4':
      $reproducible="No";
      break; 
      default:
      $reproducible="Non";
      break;
    }
// Location of autoload.php
    require_once 'vendor/autoload.php';
// Provide name of your company, name of the app that you are developing, your email address and password.
    $authenticator = new \ActiveCollab\SDK\Authenticator\Cloud('rasa', 'My Awesome Application', 'rasaradoslav@gmail.com', 'rasa0037');
// Issue a token for account #123456789.
    $token = $authenticator->issueToken(127799);
// Did we get it?
    if ($token instanceof \ActiveCollab\SDK\TokenInterface) {
    } else {
      print "Invalid response\n";
      die();
    }
    $client = new \ActiveCollab\SDK\Client($token);
    $descr = "<strong>Code version: </strong> ".$codeversion."<br>";
    $descr .= "<strong>Browser: </strong>".$browser."<br>";
    $descr .= "<strong>URL: </strong>".$url."<br>";
    $descr .= "<strong>Is it reproduced: </strong>".$reproducible."<br>";
    $descr .= "<strong>Where can be reproduced: </strong>".$prod."<br>";
    $descr .="<strong>Description: </strong><br>".$desc."<br>";
    $descr .="<strong>Expected Results: </strong><br>".$expected."<br>";
    $descr .="<strong>Actula Results: </strong><br>".$actual."<br>";
    $descr .="<strong>Reported by: </strong><br>".$email."<br>";
// Creating task
    try {
// Location of task
      $result = $client->post('projects/1/tasks', [

// Locaion of task
        'name' => "Bug report  ".$name, 
        'assignee_id' => 1,
        'task_list_id'=>1,
// Discription of task
        "body"=> $descr
        ]);
    } catch(AppException $e) {
      print $e->getMessage() . '<br><br>';
// var_dump($e->getServerResponse()); (need more info?)
    }
    
    ?>
  </body>
  </html>