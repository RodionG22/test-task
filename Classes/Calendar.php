<?php 

class Calendar {
    public $url;
    public $request;
    private $db;
    private $token=BEARER_TOKEN;
    private $bearer_token;
    private $tableName;
    public function __construct()
    {
        $this->db=new DB();
        $this->request=$_REQUEST;
        $this->url=((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->url = urldecode($this->url);  //     декод урла 
        $this->bearer_token =substr(apache_request_headers()['Authorization'],7);
    }


    public function endpointsApi(){
        $this->tableName="calendar";
        // $point=explode("?",$this->url); 
     
        // $point = str_replace('%2F', '/',$point[1]); //      '/'  заменяеться на '%2F' поэтому делаем заменяем обратно

        // $point=explode('/',$point);

        // проверка на ?calendar в урле
        $haystack =$this->url;
        $needle   = '?calendar';

        $pos      = strripos($haystack, $needle);

        if ($pos === false) {
            $this->sendError(405,"Wrong url");
        } else {    
        $request_method=$_SERVER['REQUEST_METHOD'];
        switch (strtoupper($request_method)) {
            case "GET":
                $this->getAPI();
                break;
            case "POST":
                $cols=$this->getTableColsInfo($this->tableName);
                $data=$this->validateColumnsTable($cols,["description"]);
                $this->postAPI($data);
                break;
            case "DELETE":
                $id=$this->checkForId($this->url);
                $this->deleteAPI($id);
                break;
            case "PATCH":
                $id=$this->checkForId($this->url);
                $this->patchAPI($id);

                break;
            default:
                $this->sendError(405,"Method Not Allowed");
               
            }
         }  

    }

    private function getAPI(){
        if ($this->validateBearerToken()) {
            if ( isset($this->request['date_start']) && isset($this->request['date_end']) ){
                $date_end=$this->request['date_end'];
                $date_start=$this->request['date_start'];
                if ( isDate($date_end) && isDate($date_start) ){

                        $data=$this->db->query("Select * from calendar where '$date_start'<=datetime and datetime<='$date_end' ");
   
                        $this->send($data);
                } else {
                    $this->sendError(415,"Wrong var Type");
                 }
            } else {
             $this->sendError(0,"No date_start or/and date_end");
            }
        } else {
            $this->sendError(401,"No bearer token");
        }

    }

    private function postAPI($data){

        // Проверка на дубликат
        if ( isset($data['id'] ) && $data['id']) { 
            $id=$data['id'];
            $checkid=$this->db->query("Select * from calendar where id=$id ");
            if (!empty($checkid))
                $this->sendError(0,"Primary key duplicate"); 
        }

        // Проверка на свободное время если нет токена 
        if (!$this->validateBearerToken()) {
            $duration=$data['duration'];
            $datetime=new DateTime($data['datetime']);
            $datetime2=$datetime->format('Y-m-d H:i:s');
            $datetime->modify("+{$duration} minutes");
            $datetime1=$datetime->format('Y-m-d H:i:s');
            $checkfreetime=$this->db->query("Select * from calendar where '$datetime2'<=datetime and datetime<='$datetime1' ");

            if (!empty($checkfreetime))
                $this->sendError(0,"Time is booked");
        }
   
        // INSERT INTO `calendar` (`id`, `datetime`, `duration`, `title`, `description`) VALUES ('132', '2022-07-07 02:14:15', '132', '132', '132');
        // Добавление записей
        $cols=$this->getTableColsInfo($this->tableName);
        $sql="INSERT INTO `$this->tableName`";
        $insert_cols=[];
        $insert_val=[];
        foreach ($cols as $col){
            if ( isset($data[$col['COLUMN_NAME']] )) {
                $insert_cols[]="`".$col['COLUMN_NAME']."`";
                $insert_val[]="'".$data[$col['COLUMN_NAME']]."'";
            }
        }
        $sql=$sql."(".implode(",",$insert_cols) .")  VALUES (".implode(",",$insert_val).")";
        $this->db->query($sql);
        $last_id=$this->db->last_id();
        $data=$this->db->query("select * from $this->tableName where id = $last_id ");
        $data['status']="inserted";
        $this->send($data);
    }

    private function patchAPI($id){

        if ($this->validateBearerToken()) {
            $q=$this->db->query("select * from $this->tableName where id=$id ");
            if (empty($q)){
                $this->sendError(401,"No record to edit");  // проверка на наличие записи с таким-то id
            } else {
                $datetime=new DateTime('now');
                $datetime1=$datetime->format('Y-m-d H:i:s');
                $datetime->modify('3 hours');
                $datetime2=$datetime->format('Y-m-d H:i:s');

                $q=$this->db->query("select * from $this->tableName where id=$id and '$datetime1'<=datetime and datetime<='$datetime2'");
         
                if (!empty($q))
                    $this->sendError(0,"3 hours not passed yet");       // прошло ли 3 часа 
                else {
                    $sql="WHERE `$this->tableName`.`id`=$id";
                    $sql_upd=[];
                    //  UPDATE `calendar` SET `datetime` = '2022-07-01 01:19:00' WHERE `calendar`.`id` = 6;
                    $cols=$this->getTableColsInfo($this->tableName);
              
                    foreach ($cols as $col){
                        if ($col['EXTRA']=='auto_increment')
                            continue;
                        if (isset($this->request[$col['COLUMN_NAME']] ) ) {                         // наличие необходимых полей
                            if ($this->validateValueType($this->request[$col['COLUMN_NAME']],$col['DATA_TYPE'])) {         // валидация значений

                                $sql_upd[]= "`".$col['COLUMN_NAME']."`"."="." ' ". $this->request[$col['COLUMN_NAME']]."'" ;                      
                            } else {

                                $this->sendError(0,"Сolumn validation");
                            } 
                        }
                    }
                    if (empty($sql_upd)){           
                        $this->sendError(0,"Nothing to edit"); // ни одного поля чтобы редактировать
                    } else {
                        $sql_upd=implode(",",$sql_upd);
                        $sql=" UPDATE $this->tableName SET $sql_upd "."$sql";
                        $this->db->query($sql);
                        $res=($this->db->query("SELECT * FROM $this->tableName where id=$id "));
                        $res['status']="edited";
                        $this->send($res);
                    }
                    
                }


            }
        } else {
            $this->sendError(401,"No bearer token");
         }
    }

    private function deleteAPI($id){

        if ($this->validateBearerToken()) {

            $q=$this->db->query("select * from $this->tableName where id=$id ");

            if (empty($q)){
                $this->sendError(401,"No record to delete");        // проверка наличия записи
            } else {
                $datetime=new DateTime('now');
                $datetime1=$datetime->format('Y-m-d H:i:s');
                $datetime->modify('3 hours');
                $datetime2=$datetime->format('Y-m-d H:i:s');
                $q=$this->db->query("select * from $this->tableName where id=$id and '$datetime1'<=datetime and datetime<='$datetime2'");
                if (!empty($q))
                    $this->sendError(401,"3 hours not passed yet");        // прошло ли 3 часа 
                else 
                    $delrecord=$this->db->query("SELECT * FROM $this->tableName where id=$id ");
                    $delrecord['status']="deleted";
                    $this->db->query("Delete from $this->tableName where id =$id");
                    $this->send($delrecord);
            }
         } else {
            $this->sendError(401,"No bearer token");
         }
    }

    private function getTableColsInfo($tableName){
        $cols=$this->db->query("select column_name, data_type,extra from information_schema.columns where table_schema = 'task'  and TABLE_NAME='$tableName' order by table_name,ordinal_position");
        return $cols;
    }
    private function checkForId($url){

        $url=explode("?",$url);
        $haystack =$url[1];
        $needle   = '/';
        $pos      = strripos($haystack, $needle);
        if ($pos === false) {

            $this->sendError(405,"Wrong url");
        } else { 
            $url=explode($needle,$url[1]);
            $id=$url[1];
            $haystack =$id;
            $needle   = '&';
            $pos      = strripos($haystack, $needle);
            if ($pos === false) {
       
                $id=$this->isIntNumber($id);
                return $id;
            }else {

                $url=explode("&",$id);

                $id=$this->isIntNumber($url[0]);
                return $id;
            } 
        }
    }

    private function isIntNumber(int $number) {

        if (is_numeric($number)){
            $id1=$number - floor($number);
            if ($id1)
                $this->sendError(405,"Wrong id"); 
            else 
                return $number;
        } else {
            $this->sendError(405,"Not numeric id"); 
        }
    }



    private function validateValueType($value, $type,$extra=""){
        switch(strtoupper($type)){
            case "INT": 
                    return ( $extra=="auto_increment" && empty($value) ? true : is_numeric($value));
                break;
            case "VARCHAR":
                    return ($value ? true : false); 
                break;
            case "DATETIME":
                    return isDate($value); 
                break;
        }
    }
    private function validateColumnsTable($cols,$notrequiredCols=[]){
        $data=[];
        // Берем необходимые поля 
        foreach ($cols as $col){
            if ($col['EXTRA']!="auto_increment"  && !in_array($col['COLUMN_NAME'],$notrequiredCols)  )
                $required_cols[]=$col['COLUMN_NAME'];
        }
        // Валидация данных
        foreach ($cols as $col){
                if ( !isset($this->request[$col['COLUMN_NAME']]) &&  in_array($col['COLUMN_NAME'],$required_cols))  {
                    $this->sendError(0,"No required col");
                }
                if ( isset($this->request[$col['COLUMN_NAME']] )) {

                    if ($this->validateValueType($this->request[$col['COLUMN_NAME']],$col['DATA_TYPE'],$col['EXTRA']) ){
                        
                        $data[$col['COLUMN_NAME']]=$this->request[$col['COLUMN_NAME']];
                    }
                    else {
                        if (strtoupper($col['DATA_TYPE'])!="varchar" && !in_array($col['COLUMN_NAME'],$notrequiredCols) )
                            $this->sendError(0,"Сolumn validation");
                    }
                }
        }
      
        return $data;
    }
    private function validateBearerToken(){
        if ( $this->bearer_token==$this->token)
            return true;
        else 
            return false;
    }
    private function sendError($status,$text){
        http_response_code($status);
       echo json_encode(['status'=>$status,'message'=>$text],JSON_UNESCAPED_UNICODE);
       exit();
    }
    private function send($data){
        echo json_encode($data);
    }
}

function isDate($str){
    return is_numeric(strtotime($str));
}