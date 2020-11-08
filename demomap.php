<html>
<head>
<title>Map</title>
<script type='text/javascript'
src='https://maps.googleapis.com/maps/api/js?key=AIzaSyDFLaJwxTIGpZmwfpbEyOU5XZglUq6-5iM&sensor=false'>
</script>

<?php

  

    function get_infor_from_address($address = null) {
      $prepAddr = str_replace(' ', '+', stripUnicode($address));
      $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
      $output = json_decode($geocode);
      return $output;
    }
   
    // Loại bỏ dấu tiếng Việt để cho kết quả chính xác hơn
    function stripUnicode($str){
      if (!$str) return false;
      $unicode = array(
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd'=>'đ|Đ',
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i'=>'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y'=>'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ'
      );
      foreach($unicode as $nonUnicode=>$uni) $str = preg_replace("/($uni)/i",$nonUnicode,$str);
      return $str;
    }
   
      $vitri = "Nguyen Luong Bang - Tp Da Nang";
$address = get_infor_from_address($vitri);
echo 'Vĩ độ (latitude): ' . $address->results[0]->geometry->location->lat;
echo 'Kinh độ (longitude): ' . $address->results[0]->geometry->location->lng;

$lat= $address->results[0]->geometry->location->lat;
  $long= $address->results[0]->geometry->location->lng;
  
?>

<script type='text/javascript'>
    var latitude = "<?php echo $lat; ?>";
    var longitude ="<?php echo $long; ?>";
    var address = "<?php echo $vitri; ?>";
function initialize()
{
    var myLatLng = new google.maps.LatLng(latitude,longitude);

 var mapProp = {
  zoom:19,
  center: myLatLng,
  mapTypeId: google.maps.MapTypeId.ROADMAP
  };
var map=new google.maps.Map(document.getElementById('map_canvas'),mapProp);

var marker = new google.maps.Marker({
  position: myLatLng,
  map: map,
  optimized: false,
  title:address
}); 
}


</script>
</head>
<body onload='initialize()'>

<div id='map_canvas' style='width:100%; height:100%;'></div>
</body>
</html>