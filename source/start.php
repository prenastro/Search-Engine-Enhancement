<?php
	ini_set('memory_limit','2048M');
        include("snippet.php");

	function snippet($value, $query){

		$file = file_get_contents($value);
		$html = str_get_html($file);
		$s =  strtolower($html->plaintext);
		echo $s;

		$strips = explode(" ",$query);
		$query = array_pop($strips);
		$s = str_replace("\'","",$s);
		$s = str_replace("!","",$s);
		$s = str_replace("?","",$s);
		$s = str_replace(",","",$s);
		$s = str_replace(",","",$s);
		$piece = explode(" ", $s);
		$pieces = array_values(array_filter($piece)); 
		if(false !== $start = array_search($query, $pieces)){
			$start -=10;
		}
		else{
			return "0"; 
		}
		$end = $start+40;
		if($end>count($pieces))$end=count($pieces)-1;
		$str = "";
		if($start<0)$start =0;
		if($start < $end){
			for($i = $start ; $i<$end; $i++) 
				$str.=" ".$pieces[$i];
			echo $str;
			
			return "...".substr($str,0,160)."...";
		}
		else{
			return "0";
		}
	}
	
?>