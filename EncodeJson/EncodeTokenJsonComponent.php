<?php
    class EncodeTokenJsonComponent {
        public $secratekey;
        
        public $encryptJsonObject;
        public $decryptJsonObject;

        public function loanEncodeJsonDetails(array $data) {
            $this->encryptJsonObject = $data;
            $this->secratekey = $data['Secratekey'];
            return true;
        }

        public function loanDecodeJsonDetails(array $data) {
            $this->decryptJsonObject = $data['DecryptValue'];
            $this->secratekey = $data['Secratekey'];
            return true;
        }

        public function encryptJson() 
        {
            header('Content-Type: application/json');
            $status =01;
            try {
                // ENCODE START 
                 $encryptValue = encryptDataFunc($this->encryptJsonObject, $this->secratekey);
                // ENCODE END 

                if($status === 01) 
                    echo json_encode(array("StatusCode"=>"01", "Response"=>$encryptValue),JSON_FORCE_OBJECT);
                else
                    echo json_encode(array("StatusCode"=>"02", "Response"=>$encryptValue),JSON_FORCE_OBJECT);
            }
            catch(PDOException $e) {
                echo json_encode(array("status"=>"errors","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
            }
          
        }


        public function decryptJson() 
        {
            header('Content-Type: application/json');
            $status =01;
            try {
                // ENCODE START 
                 $decryptValue = decryptDataFunc($this->decryptJsonObject, $this->secratekey);

                // ENCODE END 
                if($status === 01) 
                    echo json_encode(array("StatusCode"=>"01", "Response"=>$decryptValue));
                else
                    echo json_encode(array("StatusCode"=>"02", "Response"=>$decryptValue),JSON_FORCE_OBJECT);
            }
            catch(PDOException $e) {
                echo json_encode(array("status"=>"errors","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
            }
          
        }
    } // CLASS END HERE

    function encodeJson(array $data) {
        $EncodeTockenObject = new EncodeTokenJsonComponent;
        if($EncodeTockenObject->loanEncodeJsonDetails($data)) {
			$EncodeTockenObject->encryptJson();
        }
        else
            echo json_encode(array("status"=>"error On Encypt ","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }

    function decodeJson(array $data) {
        $decodeJsonObject = new EncodeTokenJsonComponent;
        if($decodeJsonObject->loanDecodeJsonDetails($data)) {
			$decodeJsonObject->decryptJson();
        }
        else
            echo json_encode(array("status"=>"error On Decrypt   ","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }

?>