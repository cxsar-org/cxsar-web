<?php

include 'src/cxsar.php';
$cxsar = Cxsar::getInstance();

$error = null;
$successful = false;

// Check if HWID protection was ticked
function is_hwid_protection_enabled(): bool
{
    if (!isset($_POST['hwid-enabled']))
        return false;
    return $_POST['hwid-enabled'] == "on";
}

// Are we to log in
if (isset($_POST['login'])) {
    $email = $_POST['username'];
    $password = $_POST['password'];

    if ($cxsar->attempt_login($email, $password)) {
    } else {
        $error = "Invalid credentials";
    }
} else if (isset($_POST['logout'])) {
    $cxsar->logout();
} else if (isset($_POST['submit'])) {
    $file = $_FILES['projectFile'];
    $fileName = $file['name'];
    $fileTempPath = $file['tmp_name'];

    $tmp = explode('.', $fileName);
    $fileExtension = strtolower(end($tmp));
    $fileError = $file['error'];

    // Check the file extension to be a JAR
    if (strcmp("jar", $fileExtension) == 0) {
        if ($fileError == 0) {
            // generate sha256 hash of the uploaded file
            $file_hash = hash_file("sha256", $fileTempPath, false);

            // new file path
            $path_to_file = "uploads/" . $file_hash . "/";

            // generate directory for this project
            $exists = file_exists($path_to_file);

            // new file name
            $new_name = $file_hash . '.' . $fileExtension;

            if ($exists) {
                $successful = false;
                $error = "Project already exists with same file...";
            } else {
                // create the directory
                mkdir($path_to_file, 0777, true);

                $new_filename = $path_to_file . $new_name;

                if (file_exists($new_filename)) {
                    // Delete the old file
                    unlink($new_filename);
                }

                if (is_hwid_protection_enabled()) {
                    // create hwid file
                    $hwid_file = fopen($path_to_file . "hwid.txt", 'x+');
                    fclose($hwid_file);
                }

                // move uploaded file to projects repo
                move_uploaded_file($fileTempPath,  $new_filename);

                // register the new project
                $cxsar->register_new_project($_POST['projectName'], $_SESSION['id'], $file_hash, $path_to_file);
                $successful = true;
                $error = "Succesfully created your new project!";
            }
        } else {
            $error = $fileError;
        }
    } else {
        $error = "file must be a jar " . $fileExtension;
    }
} else if (isset($_POST['delete'])) {
    $id_to_delete = $_POST['proj'];

    // sanitize
    $id_to_delete = mysqli_real_escape_string($cxsar->get_connection(), $id_to_delete);

    if ($cxsar->does_user_own_project($id_to_delete)) {
        $project = $cxsar->fetch_project_from_current_user($id_to_delete);

        if ($project === false) {
            $error = "Something went wrong fetching the product...";
        } else {
            $qry = "DELETE FROM `projects` WHERE `product_id`='$id_to_delete'";
            $cxsar->execute_query($qry); // delete it

            // also delete the file!
            $split = explode("/", $project['product_file_path']);

            // get directory
            $dir = "uploads/" . $split[1];

            // good
            array_map('unlink', glob("$dir/*.*"));
            rmdir($dir);

            $successful = true;
            $error = "Succesfully deleted the project...";
        }
    } else {
        $error = "Uh oh! You don't own this project!";
    }
} else if (isset($_POST['dl'])) {
    $id = $_POST['proj'];

    // sanitize
    $id = mysqli_real_escape_string($cxsar->get_connection(), $id);

    if ($cxsar->does_user_own_project($id)) {
        $path_to_stub = $cxsar->generate_jar_stub($id);

        // jic they cancel the download
        ignore_user_abort(true);

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($path_to_stub) . "\"");

        // flush output buffer
        ob_clean();
        flush();

        if (readfile($path_to_stub))
            unlink($path_to_stub); // delete after its been read out

        die; // make sure to not send the HTML data as well, that would be bad.
    } else {
        $error = "You don't own this project!";
        $successful = false;
    }
    // update the project so that it has an HWID file
} else if (isset($_POST['update'])) {
    $id = $_POST['proj'];
    // sanitize
    $id = mysqli_real_escape_string($cxsar->get_connection(), $id);

    if ($cxsar->does_user_own_project($id)) {
        $proj = $cxsar->fetch_project_from_current_user($id);

        $path = $proj['product_file_path'];

        // create hwid file
        $hwid_file = fopen($path . "hwid.txt", 'x+');
        fclose($hwid_file);
    } else {
        $error = "You don't own this project!";
        $successful = false;
    }
}
?>


<!DOCTYPE html>

<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale = 0.86, maximum-scale=3.0, minimum-scale=0.86" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <title>Cxsar Project</title>
</head>

<body style="overflow-x: hidden; width: 100%; position: relative;">
    <div class="p-5 text-center bg-dark text-white" style="margin-bottom: 30px;">
        <h1 class="m-3">Dashboard</h1>
        <h4 class="m-4">Edit or add new projects here</h4>
        <?php if ($cxsar->is_a_session_logged_in()) : ?>
            <form action="" method="post">
                <button type="submit" name="logout" class="btn btn-primary" style="margin-bottom: 5px;">Logout</button>
            </form>
        <?php endif ?>
    </div>

    <div class="container">

        <?php if ($error != null) : ?>
            <div class="alert <?php echo !$successful ? "alert-danger" : "alert-success"; ?> alert-dismissible fade show">
                <strong><?php echo !$successful ? "Error!" : "Success!"; ?></strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>


        <?php if ($cxsar->is_a_session_logged_in()) : ?>

            <div class="d-flex bg-white mb-3">
                <div class="col-md-8 container border border-2" style="margin-left: -10px; margin-right: 10px;">
                    <h2 class="m-4">Existing projects </h2>

                    <?php if (!$cxsar->current_user_has_projects()) : ?>
                        <p>You currently do not have any projects <?php echo $_SESSION['name']; ?>, create one now!</p>
                    <?php else : ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Hash</th>
                                    <th scope="col">HWID</th>
                                    <th scope="col">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $projects = $cxsar->fetch_all_projects_from_current_user();
                                $count = 0;

                                foreach ($projects as $project) {
                                    $hash = substr($project['product_hash'], 0, 15);
                                    $hash .= "...";
                                    echo "<tr>";
                                    echo "<th scope='row'>$count</th>";
                                    echo "<td>{$project['product_name']}</td>";
                                    echo "<td>$hash</td>";
                                    echo "<td>";

                                    if ($cxsar->project_has_hwid_protection($project['product_id'])) {
                                        echo "Yes";
                                    } else
                                        echo "No";
                                    echo "</td>";

                                    echo "<td>
                                        
                                    <form action='', method='post'><input type='submit' name='delete' class='btn btn-primary' value='Delete'/>
                                        <input type='number' name='proj' value='{$project['product_id']}' hidden></input>
                                        </form>
                                        </td>
                                    ";

                                    echo "<td><form action='' method='post'><input type='submit' name='dl' class='btn btn-primary' value='Download'/>
                                    <input type='number' name='proj' value='{$project['product_id']}' hidden></input></form></td>";

                                    if ($cxsar->project_has_hwid_protection($project['product_id'])) {
                                        echo "<td>
                                        <form action='hwid.php' method='post'><input type='submit' name='' class='btn btn-primary' value='HWIDs'/>
                                        <input type='number' name='proj' value='{$project['product_id']}' hidden></input>
                                        </form>
                                        </td>";
                                        echo "</tr>";
                                    } else {
                                        echo "<td>
                                        <form action='' method='post'><input type='submit' name='update' class='btn btn-primary' value='Enable HWID'/>
                                        <input type='number' name='proj' value='{$project['product_id']}' hidden></input>
                                        </form>
                                        </td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>

                    <?php endif ?>
                </div>

                <div class="col-md-4 container border border-2">
                    <h2 class="m-4">Create project</h2>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="projectName" class="form-label">Project Name</label>
                            <input name="projectName" type="text" class="form-control" id="projectName" aria-describedby="projectNameInfo" required>
                            <div id="projectNameInfo" class="form-text">The (unique) name for a project!</div>
                        </div>
                        <div class="mb-3">
                            <label for="projectFile" class="form-label">Upload your project</label>
                            <input name="projectFile" type="file" class="form-control" id="projectFile" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input name="hwid-enabled" type="checkbox" class="form-check-input" id="enableHWIDProtection">
                            <label class="form-check-label" for="enableHWIDProtection">Enable HWID protection</label>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary" style="margin-bottom: 5px;">Create Project</button>
                    </form>
                </div>
            </div>

        <?php else : ?>

            <div class="col-md-6 container border border-2">
                <h2 class="m-4">Login</h2>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="userName" class="form-label">E-mail</label>
                        <input name="username" type="text" class="form-control" id="userName" aria-describedby="userNameInfo" required>
                        <div id="userNameInfo" class="form-text">Your email address</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" id="password" required>
                    </div>

                    <div class="d-inline-flex">
                        <button type="submit" name="login" class="btn btn-primary" style="margin-bottom: 5px; margin-right: 15px;">Login</button>
                        <a href="register.php" style="padding-top: 5px;">Or create a new account</a>
                    </div>
                </form>
            </div>

        <?php endif ?>
    </div>

    <script>
        // Prevents the form resubmission pop-up from occuring when refreshing
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>