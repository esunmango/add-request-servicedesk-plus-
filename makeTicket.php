<?php

/*
 * Autor : Mango
 */

class makeTicket {

    protected $userKey = "" //API USER KEY;
    protected $user = "";
    protected $text = "";
    protected $technician = "";
    protected $attachment = false;
    protected $elemento = "";
    protected $log = false;
    protected $url = "http://yourdomain.com/sdpapi/request"; 
    
     public function setUrl($url){
        $this->url = $url;
    }
    
    public function getUrl(){
        return $this->url;
    }
    
    public function setLog($log){
        $this->log = $log;
    }
    
    public function getLog(){
        return $this->log;
    }

    public function setElemento($elemento) {
        $this->elemento = $elemento;
    }

    public function getElemento() {
        return $this->elemento;
    }

    public function setAttachment($attachment) {
        $this->attachment = $attachment;
    }

    public function getAttachmet() {
        return $this->attachment;
    }

    public function setTechnician($technician) {
        $this->technician = $technician;
    }

    public function getTechnician() {
        return $this->technician;
    }

    public function setUserKey($userKey) {
        $this->userKey = $userKey;
    }

    public function getUserkey() {
        return $this->userKey;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function getUser() {
        return $this->user;
    }

    public function setText($text) {
        $this->text = $text;
    }

    public function getText() {
        return $this->text;
    }

    public function send() {

        try {
            $res = $this->postAPI($this->getUrl(), $this->buildData(), null, false);

            if ($this->getAttachmet()) {
                $resp = simplexml_load_string($res);
                if (isset($resp->response->operation->Details[0]->workorderid)) {
                    $woId = $resp->response->operation->Details[0]->workorderid;
                    $this->sendAttachment($woId);
                }
            }
            return $res;
        } catch (Exception $e) {

            return $e;
        }
    }

    private function buildXML() {

        if($this->getLog()){
           $log = "\n\n\n L O G - - - - - - - - - - - - - - - - - - - - - - - - \n\n"
                   .$this->getLog()
                   ."\n - - - - - - - - - - - - - - - - - - - - - - - - -";
        }
        
        $xml = "<Operation>"
                . "<Details>"
                . "<parameter><name>requester</name><value>" . $this->getUser() . "</value></parameter>"
                . "<parameter><name>subject</name><value>Incidencia en la aplicacion</value></parameter>"
                . "<parameter><name>description</name><value>" . $this->getText(). "$log</value></parameter>"
                . "<parameter><name>priority</name><value>1 - Baja</value></parameter>"
                . "<parameter><name>site</name><value>Site</value></parameter>"
                . "<parameter><name>group</name><value>Soporte Técnico</value></parameter>"
                . "<parameter><name>technician</name><value>" . $this->getTechnician() . "</value></parameter>"
                . "<parameter><name>status</name><value>Open</value></parameter>"
                . "<parameter><name>level</name><value>Nivel I</value></parameter>"
                . "<parameter><name>category</name><value>Soporte</value></parameter>"
                . "<parameter><name>subcategory</name><value>Falla</value></parameter>"
                . "<parameter><name>priority</name><value>High</value></parameter>"
                . "<parameter><name>item</name><value>" . $this->getElemento() . "</value></parameter>"
                . "</Details></Operation>";

        return $xml;
    }

    private function buildData() {
        return array("TECHNICIAN_KEY" => $this->userKey, "INPUT_DATA" => $this->buildXML(), "OPERATION_NAME" => "ADD_REQUEST");
    }

    public function sendAttachment($woId = false) {
        try {
            $img = $this->getAttachmet();

            if (!$img) {
                return array("message" => "no hay archivo");
            }
            if (!$woId) {
                return array("message" => "no hay ID de ticket");
            }

            $cfile = new CURLFile($img);
            $cfile->setPostFilename('attachment.jpg');

            $key = $this->userKey;
            $wo = $woId;
            $url = $this->getUrl()."/{$wo}/attachment?OPERATION_NAME=ADD_ATTACHMENT&TECHNICIAN_KEY={$key}";
            $fields = array("file" => $cfile);
            $headers = array('Content-Type: multipart/form-data');
            $res = $this->postAPI($url, $fields, $headers, false, false);
            $resp = simplexml_load_string($res);
            if (isset($resp->response->operation->result->status)) {
                if ($resp->response->operation->result->status == "Success") {
                    return (array("message" => "success"));
                }
            }
        } catch (Exception $e) {
            return (array("message" => $e));
        }
        return (array("message" => "fail"));
    }

    private function postAPI($url, $params = null, $headers = null, $ssl = true, $parseVars = true) {
        $curl = curl_init();

        if ($parseVars) {
            $params = http_build_query($params);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        if (isset($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        //curl_setopt($curl,	CURLOPT_ENCODING 		, "gzip");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        if (!$result = curl_exec($curl)) {
            
            return curl_error($curl);
        }

        curl_close($curl);
        $data = json_decode($result);
        if ($data == NULL) {
            return $result;
        }
        return $data;
    }

    public function loadLog($url) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            $handle = fopen($url, 'rb');
            $lines = array();
            //$offset = ftell($handle);
            while (($line = fgets($handle)) !== false) {
                if(strpos($line, $ip)){
                    $lines[] = $line;
                }
            }

            if($handle)
            {
                $this->setLog("no se encontró log");
            }
          
            $str = "";

            if(count($lines) < 10){
                for ($l = 0; $l < count($lines); $l++) {
                    $key = ($l - 1);
                    $str = $str.$lines[$key];
                }
            }else{
                $l = count($lines) - 10;
                for ($l; $l < count($lines); $l++) {
                    $key = ($l - 1);
                    $str = $str.$lines[$key];
                }
            }
            $this->setLog($str);
            
        } catch (Exception $ex) {
            echo var_dump($ex);
        }
       
    }

    public function base64_to_jpeg($base64_string, $output_file) {

        $ifp = fopen($output_file, 'wb');

        $data = explode(',', $base64_string);

        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);

        return $output_file;
    }   

}
