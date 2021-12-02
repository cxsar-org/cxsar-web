<?php
/* Use this script to download from client to server! */
include 'src/cxsar.php';
$cxsar = Cxsar::getInstance();

// There's a download request
if (isset($_POST['dl'])) {
    if (!isset($_POST['hash']) || !isset($_POST['hwid']))
        die("BAD_REQ(000): Invalid paramaters");

    $hash = mysqli_real_escape_string($cxsar->get_connection(), $_POST['hash']);

    $project = $cxsar->find_project_by_hash($hash);

    if ($project === null)
        die("BAD_REQ(002): Project doesn't exist");

    $hwid = $_POST['hwid'];

    // Meaning we should parse the hwid
    if (strstr($hwid, "none") === false) {
        $hwid = mysqli_real_escape_string($cxsar->get_connection(), $hwid);

        // Check if it has HWID protection
        if ($cxsar->project_has_hwid_protection($project['product_id'])) {
            if (!$cxsar->project_is_hwid_whitelisted($project['product_id'], $hwid))
                die("BAD_REQ(003): HWID IS NOT WHITELISTED");
        }
    } else if ($cxsar->project_has_hwid_protection($project['product_id'])) {
        die("BAD_REQ(010): PROJECT IS HWID PROTECTED");
    }

    // $path = $cxsar->generate_jar_stub($project['product_id']);
    // if($path === false)
    //     die("BAD_REQ(004): JAR GENERATION FAILED...");
    // // Read out the file 
    // echo "OK:";
    // $target = fopen($path, 'r') or die("BAD_REQ(005): UNABLE TO OPEN JAR");
    // echo fread($target, filesize($target));
    // fclose($file);
    // register_shutdown_function('unlink', $path);
    $path = $project['product_file_path'] . $project['product_hash'] . ".jar";
    echo "OK:";
    $target = fopen($path, 'r') or die("BAD_REQ(005): UNABLE TO OPEN JAR");
    echo fread($target, filesize($path));
    fclose($target);
}
