<html>
<head>
<meta charset="UTF-8" />
<title>Please Login!</title>
<link rel="shortcut icon" href="./images/index.ico">
<link rel="stylesheet" href="login.css" />
</head>
<body>
	<div class="login-box">
       <h2>Welcome My Channel!</h2>

    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
        <div class="login-field">
            <input type="text" name="username" id="username"required="">
            <label>User</label>
        </div>
        <div class="login-field">
            <input type="password" id="password" name="password" required="">
            <label>Password</label>
        </div>
       <div> 
            <button type="login" name="login">Login</button>
       </div>
    </form>
</body>
</html>
<?php
   
include('connect.php');
session_start();

if(isset($_SESSION['userId'])) {
 //header('location:'.$store_url.'query.php');   
}

$errors = array();

if($_POST) {    

  $username = $_POST['username'];
  $password = $_POST['password'];
  if(preg_match("/[!-\/]/",$username)){
    die('非法字符，停止访问');
  }
  if(preg_match("/[!-\/]/",$password)){
    die('非法字符，停止访问');
  }

  if(empty($username) || empty($password)) {
    echo '<h3 class="popup__content__title">请填写账号和密码</h1>';
  } else {
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $connect->query($sql);
    if($result->num_rows == 1) {
      //$password = md5($password);
      // exists
      $mainSql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
      $mainResult = $connect->query($mainSql);

      if($mainResult->num_rows == 1) {
        $value = $mainResult->fetch_assoc();
        $user_id = $value['user_id'];

        // set session
        $_SESSION['userId'] = $user_id;
        ?>

      

         <div class="popup popup--icon -success js_success-popup popup--visible">
  <div class="popup__background"></div>
  <div class="popup__content">
    <h3 class="popup__content__title">
      <font color="white">登录成功，正在跳转至首页</font> 
    </h1>
     <?php echo "<script>setTimeout(\"location.href = './index';\",800);</script>"; ?>
    </p>
  </div>
</div>
     <?php  }  
      else{
        ?>


        <div class="popup popup--icon -error js_error-popup popup--visible">
  <div class="popup__background"></div>
  <div class="popup__content">
    <h3 class="popup__content__title">
    <font color="white">账号或密码错误</font> 
    </h1>
  </div>
</div>
       
      <?php } // /else
    } else { ?> 
        <div class="popup popup--icon -error js_error-popup popup--visible">
  <div class="popup__background"></div>
  <div class="popup__content">
    <h3 class="popup__content__title">
       <font color="white">账号或密码错误</font> 
    </h1>
  </div>
</div>  
         
    <?php } // /else
  } // /else not empty username // password
  
} // /if $_POST

?>
