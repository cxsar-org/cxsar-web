<?php
include 'src/cxsar.php';

$cxsar = Cxsar::getInstance();

$error = null;
$successful = false;

if (isset($_POST['register'])) {
    if ($cxsar->is_a_session_logged_in()) {
        $error = "You're already logged in...";
    } else {
        // Get input
        $usr = $_POST['username'];
        $em = $_POST['email'];
        $pass = $_POST['password'];

        // check if the email is an actual email (not a full on check)
        if (strstr($em, "@") === false) {
            $error = "Incorrect e-mail formatting";
        } else if (strlen($pass) < 8) {
            $error = "Passwords should be at least 8 characters long";
        } else if(strlen($usr) < 3) {
            $error = "Name must be at least 3 characters long";
        } else {
            // sanitize the input
            $em = mysqli_real_escape_string($cxsar->get_connection(), $em);
            $pass = mysqli_real_escape_string($cxsar->get_connection(), $pass);
            $usr = mysqli_real_escape_string($cxsar->get_connection(), $usr);

            // attempt registration
            $res = $cxsar->register_new_user($usr, $em, $pass);

            // check result
            if ($res === true) {
                $successful = true;
                $error = "Successfully registered";
            } else {
                $error = $res;
            }
        }
    }
}
// if($cxsar->is_a_session_logged_in())

?>

<!DOCTYPE html>

<head>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>

    <title>Register</title>
</head>

<body>
    <div class="p-5 text-center bg-dark text-white" style="margin-bottom: 30px;">
        <h1 class="m-2">Register</h1>
        <h4 class="m-3">Create a new account here.</h4>

        <?php if (!$cxsar->is_a_session_logged_in()) : ?>
            <h6 class="m-6">Already have an account?</h6>
            <a href="dashboard.php">
                <button name="login" class="btn btn-primary" href="dashboard.php" style="margin-bottom: 5px;">Login</button>
            </a>
        <?php else : ?>
            <a href="dashboard.php">
                <button name="dashboard" class="btn btn-primary" href="dashboard.php" style="margin-bottom: 5px;">Dashboard</button>
            </a>
        <?php endif ?>
    </div>

    <div class="container">

        <?php if ($error != null) : ?>
            <div class="alert <?php echo !$successful ? "alert-danger" : "alert-success"; ?> alert-dismissible fade show">
                <strong><?php echo !$successful ? "Error!" : "Success!"; ?></strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>


        <div class="col-md-6 container border border-2">
            <h2 class="m-4">Register</h2>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="userName" class="form-label">Name</label>
                    <input name="username" type="text" class="form-control" id="userName" aria-describedby="userNameInfo" required>
                    <div id="userNameInfo" class="form-text">Your full name</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input name="email" type="email" class="form-control" id="email" aria-describedby="emailInfo" required>
                    <div id="emailInfo" class="form-text">Your e-mail address</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input name="password" type="password" class="form-control" id="password" required>
                </div>

                <div class="d-inline-flex">
                    <button type="submit" name="register" class="btn btn-primary" style="margin-bottom: 5px; margin-right: 15px;">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>