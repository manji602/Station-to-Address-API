<?php

main();

//////////////////
//functions
//////////////////
function main(){
  $line=$_GET['line'];
  $name=$_GET['name'];
  $prefecture=$_GET['prefecture'];
  if($line==NULL && $name==NULL){
    $geo[0]=-1;
  } else {
    $geo=sta2geo($line,$name,$prefecture);
    if($geo[0]==1){
      $addr=geo2addr($geo[1],$geo[2]);
    } else {
      $addr=array(-1,"","","");
    }
  }
  //make JSON
  for($i=0;$i<count($geo[1]);$i++){
    $json_data[$i]=array(
      "lat" => $geo[1][$i],
      "lon" => $geo[2][$i],
      "prefecture" => $addr[1][$i],
      "municipality" => $addr[2][$i],
      "local" => $addr[3][$i],
      "line" => $geo[3][$i],
      "prev" => $geo[4][$i],
      "next" => $geo[5][$i],
      "postal" => $geo[6][$i]
    );
  }
  $json_all=array("status"=>$geo[0],"station" => $json_data);
  $encode=json_encode($json_all);
  echo $encode;
}

//station name to geocode(lat or lon)
function sta2geo($line,$name,$prefecture){
  $url=geturlsta2geo($line,$name,$prefecture);
  $json_data=json_decode(file_get_contents($url));
  if($json_data->response->error!=NULL){
    $status=-1;
  } else {
    $status=1;
    $num=count($json_data->response->station);
    for($i=0;$i<$num;$i++){
      $lon[$i]=$json_data->response->station[$i]->x;
      $lat[$i]=$json_data->response->station[$i]->y;
      $line[$i]=$json_data->response->station[$i]->line;
      $prev[$i]=$json_data->response->station[$i]->prev;
      $next[$i]=$json_data->response->station[$i]->next;
      $postal[$i]=$json_data->response->station[$i]->postal;
    }
  }
  $geo=array($status,$lat,$lon,$line,$prev,$next,$postal);
  return $geo;
}

function geturlsta2geo($line,$name,$prefecture){
  $basicurl="http://express.heartrails.com/api/json?method=getStations";
  $param_line="&line=";
  $param_name="&name=";
  $param_prefecture="&prefecture=";
  $url=$basicurl;
  if($line!=NULL){ $url=$url.$param_line.$line; }
  if($name!=NULL){ $url=$url.$param_name.$name; }
  if($prefecture!=NULL){ $url=$url.$param_prefecture.$prefecture; }
  return $url;
}

//geo(lat,lon) to address
function geo2addr($arr_lat,$arr_lon){
  $num=count($arr_lat);
  for($i=0;$i<$num;$i++){
    $url=geturlgeo2addr($arr_lat[$i],$arr_lon[$i]);
    $json_data=json_decode(file_get_contents($url));
    $status=$json_data->status;
    if($status==200 || $status==201 || $status==202){
      $prefecture[$i]=$json_data->result->prefecture->pname;
      $municipality[$i]=$json_data->result->municipality->mname;
      $ret_status[$i]=1;
    }
    if($status==200) $local[$i]=$json_data->result->local[0]->section;
    if($status==201) $local[$i]=$json_data->result->aza[0]->name;
    if($status==202) $local[$i]="";
    if($status==400 || $status==500){
      $prefecture[$i]="";
      $municipality[$i]="";
      $local[$i]="";
      $ret_status[$i]=-1;
    }
  }
  $addr=array($ret_status,$prefecture,$municipality,$local);
  return $addr;
}

function geturlgeo2addr($lat,$lon){
  $basicurl="http://www.finds.jp/ws/rgeocode.php?json=1&";
  $param_lat="&lat=";
  $param_lon="&lon=";
  $url=$basicurl.$param_lat.$lat.$param_lon.$lon;
  return $url;
}

?>