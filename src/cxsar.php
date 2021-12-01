<?php


/*
    Cxsar main PHP 'library' to easily use this stuff

*/

class Cxsar {

    // Singleton
    private static $instance = null;

    // Database connection
    private $connection;

    function __construct()
    {
        // Open connection to our local database
        $this->connection = mysqli_connect("localhost", "root","", "cxsar");
    }

    function __destruct()
    {
        mysqli_close($this->connection);
    }   

    function get_connection() {
        return $this->connection;
    }

    function execute_query($query) {
        $res =  mysqli_query($this->connection, $query);

        return $res;
    }

    function refresh_page() {
        header('Location: '.$_SERVER['REQUEST_URI']);
    }

    function get_current_ip() {
        return $_SERVER['REMOTE_ADDR'];
    }

    function register_new_project($project_name, $product_owner_id, $product_hash, $product_file_path)
    {
        $date = date("Y-m-d H:i:s");
        $query = "INSERT INTO `projects` (`product_name`, `product_hash`, `product_owner`, `product_timestamp`, `product_file_path`) VALUES ('$project_name', '$product_hash', '$product_owner_id', '$date', '$product_file_path')";
        $this->execute_query($query);
    }

    function generate_random_string($n)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
      
        return $randomString;
    }

    function is_a_session_logged_in() {
        return isset($_SESSION['logged_in']);
    }

    function find_project_by_hash($hash) {
        $query = "SELECT * FROM `projects` WHERE `product_hash`='$hash'";


        $res = $this->execute_query($query) or die("Error!: " . mysqli_error($this->connection));

        if($res === false) {
            echo ":query failed";
            return null;
        }

        $result = $res->fetch_array();

        if(sizeof($result) == 0) {
            echo ":nothing returned..";
            return null;
        }

        return $result;
    }

    function current_user_has_projects() {
        $id = $_SESSION['id'];

        $query = "SELECT `product_name`, `product_hash` FROM `projects` WHERE `product_owner`='$id'";

        $res = $this->execute_query($query) or die("Errror!: " . mysqli_error($this->connection));

        $results = $res->fetch_all(MYSQLI_ASSOC);

        return sizeof($results) > 0;
    }

    function fetch_project_from_current_user($id)
    {
        $query = "SELECT `product_name`, `product_hash`, `product_id`, `product_file_path` FROM `projects` WHERE `product_id`='$id' AND `product_owner`='{$_SESSION['id']}'";

        $res = $this->execute_query($query) or die("Errror!: " . mysqli_error($this->connection));

        $results = $res->fetch_array(MYSQLI_ASSOC);

        if(sizeof($results) == 0)
            return false;

        return $results;
    }

    function does_user_own_project($project)
    {
        $id = $_SESSION['id'];
        $query = "SELECT `product_owner` FROM `projects` WHERE `product_id`='$project'";

        $res = $this->execute_query($query) or die("Error! " . mysqli_error($this->connection));

        $result = $res->fetch_array();

        if($result == null)
            return false;

        if(sizeof($result) == 0)
            return false;

        return $result['product_owner'] == $id;
    }

    function fetch_all_projects_from_current_user() {
        $id = $_SESSION['id'];

        $query = "SELECT `product_name`, `product_hash`, `product_id` FROM `projects` WHERE `product_owner`='$id'";

        $res = $this->execute_query($query) or die("Errror!: " . mysqli_error($this->connection));

        $results = $res->fetch_all(MYSQLI_ASSOC);

        return $results;
    }

    function attempt_login($email, $password) {

        $email = mysqli_real_escape_string($this->connection, $email);

        $query = "SELECT `salt`, `id` FROM `users` WHERE `email`='$email'";

        $res = $this->execute_query($query) or die("Error: " . mysqli_error($this->connection));

        $res = mysqli_fetch_array($res);

        $salt = $res['salt'];
        $id   = $res['id'];

        $salted_pw = sha1($password . $salt);

        $query = "SELECT `id`, `username` FROM `users` WHERE `email`='$email' and `hashed_password`='$salted_pw'";
        $res = $this->execute_query($query) or die("error: " . mysqli_error($this->connection));

        $res = mysqli_fetch_array($res);

        // There was a user found
        if(sizeof($res) != 0)
        {
            unset($_SESSION['username']);
            unset($_SESSION['id']);
            unset($_SESSION['name']);

            $_SESSION['name'] = $res['username'];
            $_SESSION['username'] = $email;
            $_SESSION['id'] = $id;
            $_SESSION['logged_in'] = true;

            $this->update_last_ip($id);

            return true;
        }

        return false;
    }

    function logout() {
        unset($_SESSION['username']);
        unset($_SESSION['id']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['name']);
    }

    function update_last_ip($user_id) {
        $ip = $this->get_current_ip();

        $query = "UPDATE `users` SET `last_ip`='$ip' WHERE id='$user_id'";

        $this->execute_query($query) or die ("Error!" . mysqli_error($this->connection));        
    }

    function email_already_in_use($email)
    {
        $email = mysqli_real_escape_string($this->connection, $email);
        $query = "SELECT `id` FROM `users` WHERE `email`='$email'";

        $res = $this->execute_query($query) or die ("Error! " . mysqli_error($this->connection));

        $res = mysqli_fetch_all($res);

        return sizeof($res) > 0;
    }

    function register_new_user($username, $email, $password)
    {
        if($this->email_already_in_use($email))
            return "Email already in use";

        $email = mysqli_real_escape_string($this->connection, $email);

        $salt = $this->generate_random_string(7);
        $hashed = sha1($password . $salt);
        $ip = $this->get_current_ip();

        $query = "INSERT INTO `users` (`username`, `email`, `hashed_password`, `salt`, `last_ip`) VALUES ('$username', '$email', '$hashed', '$salt', '$ip')";

        $this->execute_query($query) or die ("Error! " . mysqli_error($this->connection));

        return true;
    }

    function get_project_by_projectid($id) {
        $query = "SELECT `product_file_path` FROM `projects` WHERE `product_id`='$id'";

        $res = $this->execute_query($query) or die ("BAD REQ(001): Invalid paramater.");

        $res = $res->fetch_array();

        if($res === null)
            return null;

        return $res;
    }

    function project_is_hwid_whitelisted($project_id, $hwid)
    {
        $path_to_hwid_file = $this->get_project_by_projectid($project_id);

        if($path_to_hwid_file === null)
            return false;

        $path = $path_to_hwid_file['product_file_path'] . "hwid.txt";
        $file = fopen($path, 'r');

        while(!feof($file)) {
            $line = fgets($file);

            // hwid matches
            if(strcmp($line, $hwid) == 0)
            {
                fclose($file);
                return true;
            }
        }

        fclose($file);
        return false;
    }

    function project_has_hwid_protection($project_id)
    {
        $query = "SELECT product_file_path FROM `projects` WHERE product_id='$project_id'";

        $res = $this->execute_query($query) or die ("error!" . mysqli_error($this->connection));

        $res = mysqli_fetch_array($res);

        if(!sizeof($res) > 0)
            return false;

        return file_exists($res['product_file_path'] . "hwid.txt");
    }

    function generate_jar_stub($project_id) {
        $proj = $this->fetch_project_from_current_user($project_id);

        if($proj === false)
            return false;

        $name = $this->generate_random_string(8);

        // generated path to jar
        $generated_path_to_jar =  "uploads/" . $proj['product_hash'] . "/$name.jar";

        // copy the stub to the temp path
        copy("utils/stub.jar", $generated_path_to_jar);

        // open the jar as a zip file
        $jar = new ZipArchive();

        // open the jar file
        $jar->open($generated_path_to_jar);

        for($i = 0; $i < $jar->numFiles; $i++) {
            $name = $jar->getNameIndex($i);

            if($name === false)
                continue;

            // is the entry a manifest file
            if(strstr($name, "MANIFEST.MF") !== false)
            {
                // copy the contents of the manifest
                $contents = '';
                $stream = $jar->getStream($name);

                // check if we can open a stream to the manifest
                if(!$stream)
                    return false;

                while(!feof($stream)) {
                    $contents .= fread($stream, 2);
                }

                fclose($stream);

                // replace [hash] in the contents of the manifest with the actual hash
                $new_contents = str_replace("[hash]", $proj['product_hash'], $contents);

                // replace the entry
                $jar->addFromString($name, $new_contents);

                // close the jar file :)
                $jar->close();
                break;
            }
        }

        // return the (RELATIVE) path to the jar file
        return $generated_path_to_jar;
    }

    public static function getInstance()
    {
        if(self::$instance == null)
            self::$instance = new Cxsar();

        return self::$instance;
    }
}