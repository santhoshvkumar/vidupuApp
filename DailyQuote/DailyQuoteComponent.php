<?php

class DailyQuoteMaster {
    private $currentDate;
    private $currentVersion = '1.0.15';
    private $playStoreLink = 'https://play.google.com/store/apps/details?id=com.tnscbank.dailyApp';
    private $isRefreshmentEnabled = true;
    
    public function __construct() {
        $this->currentDate = date('Y-m-d');
    }
    
    public function getDailyQuoteInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $queryDailyQuote = "SELECT quote, voiceOfMD, date 
                               FROM tblDailyQuote 
                               WHERE date = '$this->currentDate'";
                               
            $rsd = mysqli_query($connect_var, $queryDailyQuote);
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr['quote'] = $rs['quote'];
                $resultArr['voiceOfMD'] = $rs['voiceOfMD'];
                $resultArr['date'] = $rs['date'];
                $count++;
            }
            
            mysqli_close($connect_var);
            
            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "record_count" => $count,
                    "result" => $resultArr
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => $count,
                    "message_text" => "No quote found for date: $this->currentDate"
                ), JSON_FORCE_OBJECT);
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function checkVersion() {
        try {
            return [
                'status' => 'success',
                'message' => 'Version check successful',
                'currentVersion' => $this->currentVersion,
                'playStoreLink' => $this->playStoreLink,
                'isRefreshmentEnabled' => $this->isRefreshmentEnabled
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

function getDailyQuote() {
    $quoteObject = new DailyQuoteMaster();
    $quoteObject->getDailyQuoteInfo();
}

function checkVersion() {
    $quoteObject = new DailyQuoteMaster();
    $result = $quoteObject->checkVersion();
    echo json_encode($result);
}

?>
