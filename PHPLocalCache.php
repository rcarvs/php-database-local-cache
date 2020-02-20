<?php
class PHPLocalCache{

    const RETURN_AS_JSON = "json";
    const RETURN_AS_ARRAY = "array";

    private static $instance;
    private $cache_path;
    private $algorithm;
    private $return_type;



    private function __construct()
    {
        $this->cache_path = "/tmp/";
        $this->algorithm = "sha1";
        $this->return_type = self::RETURN_AS_JSON;
    }

    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new PHPLocalCache();
        }
        return self::$instance;
    }

    /*
     * Modify the folder that the class will use for cache
     */
    public static function setCachePath($path){
        self::getInstance()->cache_path = $path;
    }

    /*
     * Modify the type of return of the returned result
     */
    public static function setReturnType($type){
        if($type == self::RETURN_AS_ARRAY || $type == self::RETURN_AS_JSON){
            self::getInstance()->return_type = $type;
        }else{
            throw new ErrorException("The value provided for the returnType is not allowed. Use self::RETURN_AS_ARRAY or self::RETURN_AS_JSON");
        }
    }

    /*
     * Check if a query is already cached
     */
    public static function hit($query){
        $filename = hash(self::getInstance()->algorithm,$query);
        if(file_exists(self::getInstance()->cache_path.$filename)) {
            //found, lets just check for make sure that the result is the same query
            $returnAsArray = self::getInstance()->return_type == self::RETURN_AS_ARRAY;
            $json = json_decode(file_get_contents(self::getInstance()->cache_path . $filename), $returnAsArray);
            $jsonQuery = (($returnAsArray) ? $json['query'] : $json->query);
            if ($jsonQuery == $query) {
                return (($returnAsArray)?$json['result']:$json->result);
            } else {
                //something wrong occurred
                self::getInstance()->removeCacheFile($filename);
            }
        }
        return false;
    }

    public static function create($query,$result){
        $filename = hash(self::getInstance()->algorithm,$query);
        $file = fopen(self::getInstance()->cache_path.$filename,'w+');
        $returnAsArray = self::getInstance()->return_type == self::RETURN_AS_ARRAY;
        if($returnAsArray){
           $write = [
               'query' => $query,
               'result' => $result
           ];
        }else{
            $write =[
                'query' => $query,
                'result' => json_decode($result,true)
            ];
        }
        fwrite($file,json_encode($write));
        fclose($file);


    }

    private function removeCacheFile($filename){
        return unlink(self::getInstance()->cache_path.$filename);
    }

}

PHPLocalCache::getInstance();
