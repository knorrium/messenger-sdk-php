<?php
/*
 *
 *Software Copyright License Agreement (BSD License)
 *
 *Copyright (c) 2010, Yahoo! Inc.
 *All rights reserved.
 *
 *Redistribution and use of this software in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 ** Redistributions of source code must retain the above
 *  copyright notice, this list of conditions and the
 *  following disclaimer.
 *
 ** Redistributions in binary form must reproduce the above
 *  copyright notice, this list of conditions and the
 *  following disclaimer in the documentation and/or other
 *  materials provided with the distribution.
 *
 ** Neither the name of Yahoo! Inc. nor the names of its
 *  contributors may be used to endorse or promote products
 *  derived from this software without specific prior
 *  written permission of Yahoo! Inc.
 *
 *THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
*/
require_once 'Engine.php';

class Yahoo_Messenger_Client {
    protected $jsonObj;
    protected $engine;
    protected $CONSUMER_KEY;
    protected $SECRET_KEY;
    protected $presence_state;
    protected $presence_status;

    public function __construct($config, $CONSUMER_KEY, $SECRET_KEY) {
        $this->CONSUMER_KEY = $CONSUMER_KEY;
        $this->SECRET_KEY = $SECRET_KEY;
        $filename = $config;
        $this->jsonObj = $this->jsonLoad($filename);
        $this->engine = new Yahoo_Messenger_Engine($this->CONSUMER_KEY, $this->SECRET_KEY, $this->jsonObj->robot->credentials->username, $this->jsonObj->robot->credentials->password);
        $this->presence_state = $this->jsonObj->robot->presence_state;
        $this->presence_status = $this->jsonObj->robot->presence_status;
        $this->engine->debug = true;
    }

    private function jsonLoad($filename) {
        $handle = fopen($filename, "r");
        $json = fread($handle, filesize($filename));
        fclose($handle);
        $jsonObj = json_decode($json);
        return $jsonObj;
    }

    public function getUsername() {
        return $this->jsonObj->robot->credentials->username;
    }

    public function connect() {
        if ($this->engine->debug) {
            echo '> Fetching request token'. PHP_EOL;
        }
        if (!$this->engine->fetchRequestToken()) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . 'Fetching request token failed';
            die($msg);
        }

        if ($this->engine->debug) {
            echo '> Fetching access token'. PHP_EOL;
        }

        if (!$this->engine->fetchAccessToken()) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . 'Fetching access token failed';
            die($msg);
        }

        if ($this->engine->debug) {
            echo '> Signon as: '. $this->jsonObj->robot->credentials->username . PHP_EOL;
        }

        if (!$this->engine->signon($this->jsonObj->robot->presence_status, $this->jsonObj->robot->presence_state)) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . "Signon failed 1";
            die($msg);
        }
    }
    public function disconnect() {
        if ($this->engine->debug) {
            echo '> Fetching request token'. PHP_EOL;
        }
        if (!$this->engine->fetchRequestToken()) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . 'Fetching request token failed';
            die($msg);
        }

        if ($this->engine->debug) {
            echo '> Fetching access token'. PHP_EOL;
        }

        if (!$this->engine->fetchAccessToken()) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . 'Fetching access token failed';
            die($msg);
        }

        if ($this->engine->debug) {
            echo '> Signing off '. $this->jsonObj->robot->credentials->username . PHP_EOL;
        }

        if (!$this->engine->signoff()) {
            $msg = "[" . $this->jsonObj->robot->credentials->username . "] " . "Signoff failed";
            die($msg);
        }
    }
    
    public function run() {
        $seq = -1;
        while (true) {
            $resp = $this->engine->fetchLongNotification($seq+1);
            if (isset($resp)) {
                if ($resp === false) {
                    if ($this->engine->getError() != -10) {
                        if ($this->engine->debug) {
                            echo '> Fetching access token'. PHP_EOL;
                        }
                        if (!$this->engine->fetchAccessToken()) {
                            die('Fetching access token failed');
                        }
                        if ($this->engine->debug) {
                            echo '> Signon as: '. $this->jsonObj->robot->credentials->username . PHP_EOL;
                        }
                        if (!$this->engine->signon(date('H:i:s'))) {
                            die('Signon failed');
                        }
                        $seq = -1;
                    }
                    continue;
                }
                foreach ($resp as $row) {
                    foreach ($row as $key=>$val) {
                        if ($val['sequence'] > $seq) $seq = intval($val['sequence']);

                        /*
                         * do actions
                         */

                        if ($key == 'disconnect') {
                              switch(intval($val['reason'])) {
                                  case 1: {
                                      $reason = "This user session has been expired because of login elsewhere.\n";
                                      break;
                                  }
                                  case 2: {
                                      $reason = "This user session has been expired because of idleness.\n";
                                      break;
                                  }
                                  case 3: {
                                      $reason = "This user session has been expired because messages in the session notification queue are not fetched.\n";
                                      break;
                                  }
                                  case 5: {
                                      $reason = "User has logged out\n";
                                      break;
                                  }
                                  default: {
                                      $reason = "Disconnected due to an unexpected cause. Error code: " . intval($val['reason']);
                                      break;
                                  }
                              }
                              echo "Disconnected: " . $reason;
                              exit();
                          }
                        if ($key == 'buddyInfo') { //contact list
                            if (!isset($val['contact'])) {
                                continue;
                            }
                            if ($this->engine->debug) {
                                echo PHP_EOL. 'Contact list: '. PHP_EOL;
                            }
                            foreach ($val['contact'] as $item)
                            {
                                if ($this->engine->debug) {
                                    echo $item['sender']. PHP_EOL;
                                }
                            }
                            if ($this->engine->debug) {
                                echo '----------'. PHP_EOL;
                            }
                        }

                        else if ($key == 'message') { //incoming message
                            if ($this->engine->debug) {
                                echo '+ Incoming message from: "'. $val['sender']. '" on "'. date('H:i:s', $val['timeStamp']). '"'. PHP_EOL;
                            }
                            if ($this->engine->debug) { 
                                echo '   '. $val['msg']. PHP_EOL;
                            }
                            if ($this->engine->debug) {
                                echo '----------'. PHP_EOL;
                            }

                            //reply
                            $words = explode(' ', trim($val['msg']));
                            $words[0] = strtolower($words[0]);
                            if ($words[0] == 'help') {
                                $out = 'This is Yahoo! Open API demo'. PHP_EOL;
                                $out .= '  To get recent news from yahoo type: news'. PHP_EOL;
                                $out .= '  To change my/robot status type: status newstatus'. PHP_EOL;
                            }
                            else if ($words[0] == 'news') {
                                if ($this->engine->debug) {
                                    echo '> Retrieving news rss'. PHP_EOL;
                                } 
                                $rss = file_get_contents('http://rss.news.yahoo.com/rss/topstories');

                                if (preg_match_all('|<title>(.*?)</title>|is', $rss, $m)) {
                                    $out = 'Recent Yahoo News:'. PHP_EOL;
                                    for ($i=2; $i<7; $i++) {
                                        $out .= str_replace("\n", ' ', $m[1][$i]). PHP_EOL;
                                    }
                                }
                            }
                            else if ($words[0] == 'status') {
                                $this->engine->changePresence(str_replace('status ', '', strtolower($val['msg'])));
                                $out = 'My status is changed';
                            }
                            else if ($words[0] == 'message') {
                                $username = $words[1];
                                $message = implode(" ", array_slice($words, 2));
                                $this->engine->sendMessage($username, $message);
                                $out = 'Message sent to ' . $username . ": " . $message;
                            }
                            else if ($words[0] == 'presence_state') {
                                $this->presence_state = str_replace('presence_state ', '', strtolower($val['msg']));
                                $this->engine->changePresenceState($this->presence_state, $this->presence_status);
                                $out = 'My state is changed';
                            }
                            else if ($words[0] == 'presence_status') {
                                $this->presence_status = str_replace('presence_status ', '', $val['msg']);
                                $this->engine->changePresenceStatus($this->presence_state, $this->presence_status);
                                $out = 'My status is changed';
                            }
                            else if ($words[0] == 'signoff') {
                                $out = 'Goodbye!';
                                $this->engine->sendMessage($val['sender'], $out);
                                $this->engine->signoff();
                                die();
                            }
                            else {
                                $out = 'Please type: help';
                            }

                            //send message
                            if ($this->engine->debug) { 
                                echo '> Sending reply message '. PHP_EOL;
                            }
                            if ($this->engine->debug) { 
                                echo '    '. $out. PHP_EOL;
                            }
                            if ($this->engine->debug) {
                                echo '----------'. PHP_EOL;
                            }
                            $this->engine->sendMessage($val['sender'], $out);
                        }

                        else if ($key == 'buddyAuthorize') { //incoming contact request
                            if ($this->engine->debug) {
                                echo PHP_EOL. 'Accept buddy request from: '. $val['sender']. PHP_EOL;
                            }
                            if ($this->engine->debug) {
                                echo '----------'. PHP_EOL;
                            }
                            if (!$this->engine->responseContact($val['sender'], true, 'Welcome to my list')) {
                                $this->engine->deleteContact($val['sender']);
                                $this->engine->responseContact($val['sender'], true, 'Welcome to my list');
                            }
                        }
                    }
                }
            }
        }
    }
}

?>
