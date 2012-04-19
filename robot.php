<?php
    require_once 'Yahoo/Messenger/Client.php';
    
    if ($argv[1] == NULL){
        die("No robot config provided\n");
    } else {
        
        // you should create this file and paste your key in it without any other character or line breaks
        $filename = 'config/CONSUMER_KEY';
        $handle = fopen($filename, "r");
        $CONSUMER_KEY = fread($handle, filesize($filename));
        fclose($handle);
        // you should create this file and paste your key in it without any other character or line breaks
        $filename = 'config/SECRET_KEY';
        $handle = fopen($filename, "r");
        $SECRET_KEY = fread($handle, filesize($filename));
        fclose($handle);

        $robot = new Yahoo_Messenger_Client($argv[1], $CONSUMER_KEY, $SECRET_KEY);
        $robot->connect();
        $robot->run();
    }
?>