<?php

class Jason {
  private $storage_folder;
  private $collection_name;
  private $collection_path;

  public function __construct($collection_name, $storage_folder) {
    $this->collection_name = $collection_name;
    $this->storage_folder = realpath($storage_folder);
    $this->collection_path = $this->storage_folder."/".$this->collection_name.".json";

    if(!file_exists($this->collection_path)) {
      return $this->nuke();
    } else {
      return $this;
    }
  }

  public function save($object) {
    $collection = $this->read();
    $key = array_key_exists("key", $object) ? $object["key"]: "_".count($collection->items);
    if(array_key_exists("key", $object)) {
     unset($object["key"]);
    }
    $collection->items->$key = $object;
    if($this->write($collection)) {
      return $this;
    } else {
      return false;
    }
  }

  public function batch($objects) {
    $collection = $this->read();
    while(count($objects) > 0) {
      $this->save(array_shift($objects));
    }

    return $this;
  }

  public function get($key) {
    $collection = $this->read();
    if(array_key_exists($key, $collection->items)) {
      $item = $collection->items->$key;
      $item->key = $key;
      return $item;
    } else {
      return false;
    }
  }

  public function find($field, $value, $comparison) {
    $collection = $this->read();
    $results = array();
    foreach($collection->items as $key => $item) {
      $test = false;
      switch($comparison) {
        case "==":
          $test = $item->$field == $value;
          break;
      }
      if($test) {
        $item->key = $key;
        $results[] = $item;
      }
    }

    return $results;
  }

  public function all($array = true) {
    $collection = $this->read();
    if(!$array) {
      return $collection->items;  
    } else {
      $return = array();
      $keys = get_object_vars($collection->items);
      foreach($keys as $key=>$item) {
        if(!is_int($key)) {
          $item->key = $key;
        } else {
          $item->key = "_".$key;
        }
        $return[] = $item;
      }
      
      return $return;
    }
  }

  public function keys() {
    $collection = $this->read();
    return get_object_vars($collection->items);
  }

  public function exists($key) {
    $collection = $this->read();
    return property_exists($collection->items, $key);
  }

  public function remove($key) {
    $collection = $this->read();
    if($this->exists($key)) {
      unset($collection->items->$key);
      $this->write($collection);
      return $this;
    } else {
      return false;
    }
  }

  public function nuke() {
    $this->write(array(
      "name" => $this->collection_name, 
      "count" => 0, 
      "items" => new stdClass()
    ));

    return $this;
  }

  private function write($collection) {
    $file = fopen($this->collection_path, "w+");
    $content = json_encode($collection);
    if(fwrite($file, $content)) {
      fclose($file);
      return true;
    } else {
      return false;
    }
  }

  private function decode($json) {
    $decoded = json_decode($json);
    if($decoded != null) {
      return $decoded;
    } else {
      return false;
    }
  }

  private function read() {
    if(file_exists($this->collection_path)) {
      $content = file_get_contents($this->collection_path);
      return $this->decode($content);
    } else {
      return false;
    }
  }
}

?>