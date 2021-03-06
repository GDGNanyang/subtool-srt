<?php
/**
 * Created by PhpStorm.
 * User: chenglu
 * Date: 15-1-18
 */

class getXMLfromYT{

	var $youtubeUrl;
	var $language;
	var $cc_sub;
	var $cc_sub_name;
	var $base_url = "https://video.google.com/timedtext";

	public function __construct($youtubeUrl, $language = "en", $cc_sub, $cc_sub_name){
		$this->youtubeUrl = $youtubeUrl;
		$this->language = $language;
		$this->cc_sub = $cc_sub;
		$this->cc_sub_name = $cc_sub_name;
		
	}

	public function getXML(){
		$videoId = $this->getYoutubeVideoId($this->youtubeUrl);
		$cc_sub_name = $this->getCaptioNameByLangCode($videoId, "en", intval($this->cc_sub));
		$target = $this->base_url .
						  "?lang=". $this->language.
							"&v=" . $videoId.
						 "&name=" . urlencode($cc_sub_name);
		
		$result = file_get_contents($target);
		
		return isset($result)? $result: false;
		
		
//		// https://video.google.com/timedtext?lang=en&v=iQTxMkSJ1dQ&list=UU_x5XG1OV2P6uZZ5FSM9Ttw
//		$target = $this->base_url .
//						 "?lang=" . $this->language.
//							"&v=" . $this->getYoutubeVideoId();
//	if($this->cc_sub){
//		$target = $target."&name=CC";
//	}
//		$result = file_get_contents($target);
//		if( strlen($result)!==0 ){
//			return $result;
//		}else{
////			$target = $target."&name=CC";
//			$result = file_get_contents($target."&name=CC");
//			if (strlen($result)!==0){
//				return $result;
//			} else {
////				$target = $target."&name=CC%20(English)";
//				$result = file_get_contents($target."&name=CC%20(English)");
//				if (strlen($result)!==0){
//					return $result;
//				}
//			}
//		}
//		return false;
	}

	function getYoutubeVideoId() {
		$urlParts = parse_url($this->youtubeUrl);
		if($urlParts === false || !isset($urlParts['host']))
			return false;
		if(strtolower($urlParts['host']) === 'youtu.be')
			return ltrim($urlParts['path'], '/');
		if(preg_match('/^(?:www.)?youtube.com$/i', $urlParts['host']) && isset($urlParts['query'])) {
			parse_str($urlParts['query'], $queryParts);
			if(isset($queryParts['v']))
				return $queryParts['v'];
		}
		return false;
	}
	
		
		public function getOriginSrtUrl() {

			$cc_sub = $this->cc_sub;
			
			// get the right langcode and cc name
			$langcode = null;
			$cc_sub_name = null;
			
			$videoId = $this->getYoutubeVideoId();
			
			// this is the url that could get all the captions info of the video
			$videoCaptionInfoUrl = $this->base_url .
					"?hl=en&type=list".
								"&v=" . $videoId;
				
			$res = file_get_contents($videoCaptionInfoUrl);
			if ($res == null){
				echo (" error: API error ");
				return false;
			}
		
			$videoCaptionInfo = $this->simplest_xml_to_array($res);
								
			// check if there has English captions
			if ($videoCaptionInfo["track"]==null) {
				echo (" error: No captions ");
				return false;
				}
			
			$captionNames = array();
			for ($i=0; $i<count($videoCaptionInfo["track"]); $i++) {
				$langcode = $videoCaptionInfo["track"][$i]["@attributes"]["lang_code"];
				$captionName = $videoCaptionInfo["track"][$i]["@attributes"]["name"];
				if ($captionName){
					$captionNames[] = $captionName;
				}
			}
			// if ($langcode != "en") {
// 				echo (" error: No English captions ");
// 				return false;
// 			}
// 		
			// check if there has CC captions
			for ($i=0; $i<count($captionNames); $i++) {
				if (strpos($captionNames[$i], "CC")){
					$cc_sub_name = $captionNames[$i];
				}
			}
			// check the CC caption's name
//			var_dump($captionNames);
//			var_dump($cc_sub_name);
			
			// return the CC URL
			if($cc_sub){
				$target = $this->base_url .
								"?lang=en".
									"&v=" . $videoId.
								 "&name=" . $captionNames[0];
			}else {
				$target = $this->base_url .
								"?lang=en".
									"&v=" . $videoId;
			}
			
//			echo $target;
			
			return $target;
		}
		
		/**
		* 最简单的XML转数组
		* @param string $xmlstring XML字符串
		* @return array XML数组
		*/
		public function simplest_xml_to_array($xmlstring) {
			return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
		}

		/**
		* Get video caption info
		* @param string $videoId 视频 ID
		* @return array 视频信息
		*/
		
		public function getVideoCaptionInfo($videoId) {
			
			$videoCaptionInfoUrl = $this->base_url .
					"?hl=en&type=list".
								"&v=" . $videoId;
				
			$res = file_get_contents($videoCaptionInfoUrl);
			if ($res == null){
				echo (" error: API error ");
				return false;
			}

			$videoCaptionInfo = $this->simplest_xml_to_array($res);
			$videoCaptionTrack = $videoCaptionInfo["track"];
			
			return isset($videoCaptionTrack) ? $videoCaptionTrack : false;
			
		}
		
		/**
		* Get en caption name
		* @param string $videoId 视频 ID
		* @return array 视频信息
		*/
		
		public function getCaptioNameByLangCode($videoId, $langcode, $preferCC) {
			$tracks = $this->getVideoCaptionInfo($videoId);
//			$captionNames = array();
			if($tracks){
				$captionNames = array();
//				var_dump($tracks);
				if (count($tracks) == 1){
					$captionNames[] = array("name"=>$tracks["@attributes"]["name"]);
				}else if(count($tracks) > 1) {
					// 下面适用于有多个字幕文件，所以这里要判断，如果只有一个，就不能用这种方法
					foreach ($tracks as $key => $value) {
						$captionInfo = $value["@attributes"];
	//					var_dump($captionInfo);
	//					var_dump($captionInfo["lang_original"]);
						if ($captionInfo["lang_code"] == $langcode){
							$captionNames[] = array("name"=>$captionInfo["name"]);
						}
					}
				}
				
			}else {
				// tracks is null, this video has no caption
				echo "Error - 这个视频没有字幕，如果这是一个误报，请联系我们";
			}
			
//			if($preferCC){
//				if (count($captionNames) > 1){
//					// 说明英文字幕中有很多选项
//					foreach ($captionNames as $key => $value) {
////						print_r($value);
//						// 筛选出 CC 字幕
//						if (strpos($value["name"], "CC") === false){
////							echo "ffff";
//							array_splice($captionNames, $key, 1);
//						}
//					}
//				}				
//			}
			
			
			/*
			* 如果字幕出现多个选项，则以 CC 字幕为判断
			*/
			if (count($captionNames) > 1){
				foreach ($captionNames as $key => $value) {
					var_dump(strpos($value["name"], "CC"));
					if (strpos($value["name"], "CC") === !$preferCC){
						array_splice($captionNames, $key, 1);
					}else if(strpos($value["name"], "CC") === $preferCC){
						array_splice($captionNames, $key, 1);
					}					
				}
			}
						
			return isset($captionNames) ? $captionNames[0]["name"] : false;
		}
		
}

//// https://www.youtube.com/watch?v=G7oolm0jU8I
//// https://www.youtube.com/watch?v=nKW8Ndu7Mjw
///*
//* CC = https://www.youtube.com/watch?v=5DknTFbcGVM
// CC English = https://www.youtube.com/watch?v=nKW8Ndu7Mjw
//KrGvKhgW3U4
//
//BOHZHMmTYbk
//*/
//$ccUrl = "https://www.youtube.com/watch?v=nKW8Ndu7Mjw";
//$ccenUrl = "https://www.youtube.com/watch?v=nKW8Ndu7Mjw";
//
//$getxml = new getXMLfromYT($ccUrl, "en", true, "");
//
//$res = $getxml->getXML();
////array_splice($res,1);
//
//var_dump($res);



