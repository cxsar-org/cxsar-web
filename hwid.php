<?php

// Cxsar impl...
include('src/cxsar.php');
$cxsar = Cxsar::getInstance();

$error = null;
$successful = false;

if (!$cxsar->is_a_session_logged_in() || !isset($_POST['proj']))
    header('Location: dashboard.php');

if (!$cxsar->does_user_own_project($_POST['proj']))
    header('Location: dashboard.php');

if (isset($_POST['delete'])) {
    $target = $_POST['hwid'];
    $proj = $_POST['proj'];

    $cxsar->remove_hwid($proj, $target);

    $successful = true;
    $error = "HWID Succesfully removed";
} else if (isset($_POST['add'])) {
    $add = $_POST['hwid'];
    $proj = $_POST['proj'];

    $res = $cxsar->add_hwid($proj, $add);

    if ($res === true) {
        $successful = true;
        $error = "HWID Succesfully added";
    } else {
        $successful = false;
        $error = "Failed to add HWID...";
    }
} else if (isset($_POST['disable'])) {
    $proj = $_POST['proj'];
    $project = $cxsar->fetch_project_from_current_user($proj);

    $path = $project['product_file_path'];

    // delete the HWID file
    unlink($path . "hwid.txt");

    $successful = true;
    $error = "Sucessfully disabled HWID protection";

    header('Location: dashboard.php');
}

?>
<!DOCTYPE html>

<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>
    <title>Cxsar Project</title>
</head>

<body>
    <div class="p-5 text-center bg-dark text-white" style="margin-bottom: 30px;">
        <h1 class="m-2">HWID</h1>
        <h4 class="m-3">Add or remove HWIDs for "<?php echo $cxsar->get_project_name($_POST['proj']); ?>" here</h4>
        <a href="dashboard.php">
            <button name="dashboard" class="btn btn-primary" href="dashboard.php" style="margin-bottom: 5px;">Dashboard</button>
        </a>
    </div>

    <div class="col-md-6 container border border-2">
        <?php if ($error != null) : ?>
            <div style="margin-top: 5px;" class="alert <?php echo !$successful ? "alert-danger" : "alert-success"; ?> alert-dismissible fade show">
                <strong><?php echo !$successful ? "Error!" : "Success!"; ?></strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>
        <h2 class="m-4">List</h2>

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">HWID</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hwids = $cxsar->get_all_hwids($_POST['proj']);
                $count = 0;

                if (sizeof($hwids) > 0) {
                    foreach ($hwids as $hwid) {
                        echo "<tr>";
                        echo "<th scope='row'>$count</th>";
                        echo "<td>$hwid</td>";
                        echo "<td>";
                        echo "<td><form action='', method='post'><input type='submit' name='delete' class='btn btn-primary' value='Delete'/>
                                        <input type='text' name='hwid' value='$hwid' hidden></input>
                                        <input type='number' name='proj' value='{$_POST['proj']}' hidden></input>
                                        </form></td>";
                        echo "</tr>";
                        $count++;
                    }
                }
                ?>
            </tbody>
        </table>


        <h2 class="m-4">Add</h2>
        <div class="col-md-12 text-center">
            <form action='' method='post'>
                <div class="mb-3">
                    <input type='text' class="form-control" name='hwid'>HWID</input>
                </div>

                <div class="mb-3">
                    <input type='number' name='proj' value='<?php echo $_POST['proj']; ?>' hidden />
                </div>
                <div class="mb-3">
                    <input type='submit' name='add' class="btn btn-primary" value="Add" />
                </div>

            </form>
            <form action='' method='post'>

                <div class="mb-3">
                    <input type='number' name='proj' value='<?php echo $_POST['proj']; ?>' hidden />
                </div>

                <div class="mb-3">
                    <input type='submit' name='disable' class="btn btn-primary" value="Disable HWID Protection" />
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prevents the form resubmission pop-up from occuring when refreshing
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>