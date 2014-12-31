<?php
class znamesilo{
	public $KEY = 'default_api_key';
	public $URL = 'https://www.namesilo.com/api/';
	public $debug = false;
	public static function init() {
		$class = __CLASS__ ;
		if ( empty( $GLOBALS[ $class ] ) ) $GLOBALS[ $class ] = new $class;
	}
	function __construct($key){
		if($key) $this->KEY = $key;
	}
	function xml2array($contents, $get_attributes=1, $priority = 'tag') {
		if(!$contents) return array();
		if(!function_exists('xml_parser_create')) {
		return array();
		}
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);
		if(!$xml_values) return;
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
		$current = &$xml_array;
		$repeated_tag_index = array();
		foreach($xml_values as $data) {
			unset($attributes,$value);
			extract($data);
			$result = array();
			$attributes_data = array();
			
			if(isset($value)) {
				if($priority == 'tag') $result = $value;
				else $result['value'] = $value;
			}
			if(isset($attributes) and $get_attributes) {
				foreach($attributes as $attr => $val) {
					if($priority == 'tag') $attributes_data[$attr] = $val;
					else $result['attr'][$attr] = $val;
				}
			}
			if($type == "open") {
				$parent[$level-1] = &$current;
				if(!is_array($current) or (!in_array($tag, array_keys($current)))) { 
					$current[$tag] = $result;
					if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					$current = &$current[$tag];
				} else {
					if(isset($current[$tag][0])) {
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						$repeated_tag_index[$tag.'_'.$level]++;
					} else {
						$current[$tag] = array($current[$tag],$result);
						$repeated_tag_index[$tag.'_'.$level] = 2;
						
						if(isset($current[$tag.'_attr'])) {
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
					$current = &$current[$tag][$last_item_index];
				}
			} elseif($type == "complete") {
				if(!isset($current[$tag])) {
					$current[$tag] = $result;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
				} else { 
					if(isset($current[$tag][0]) and is_array($current[$tag])) {
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						
						if($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag.'_'.$level]++;
					} else {
						$current[$tag] = array($current[$tag],$result);
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $get_attributes) {
							if(isset($current[$tag.'_attr'])) {
								
								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}
							
							if($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag.'_'.$level]++;
					}
				}
			} elseif($type == 'close') {
				$current = &$parent[$level-1];
			}
		}
		if($this->debug) echo "<pre>".print_r($xml_array,1)."</pre>";
		return($xml_array);
	}
	
	function xcurl($url,$post){
		$ch = curl_init();
		$header = array("Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8","Accept-Language: en-us,en;q=0.5","Accept-Charset: UTF-8,*","Keep-Alive: 115","Connection: keep-alive","Cache-Control: max-age=0" );
		if(!empty($post)) curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
		@curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, TRUE);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,0);
		curl_setopt($ch,CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0');
		$ch_data = curl_exec($ch);
		curl_close($ch);
		if($this->debug) echo "<pre>".htmlspecialchars($ch_data)."</pre>";
		return $ch_data;
	}
	// Function list all domain active in your account
	function listDomains(){
		$xml = self::xcurl($this->URL.(__FUNCTION__)."?version=1&type=xml&key={$this->KEY}");
		$arr = self::xml2array($xml);
		return $arr['namesilo']['reply']['domains']['domain'];
	}
	// Function list all domain dns record
	function dnsListRecords($domain){
		$xml = self::xcurl($this->URL.(__FUNCTION__)."?version=1&type=xml&key={$this->KEY}&domain={$domain}");
		$arr = self::xml2array($xml);
		return $arr['namesilo']['reply']['resource_record'];
	}
	// Function add new domain dns record
	function dnsAddRecord($domain,$type,$host="",$value,$ttl=""){
		$xml = self::xcurl($this->URL.(__FUNCTION__)."?version=1&type=xml&key={$this->KEY}&domain={$domain}&rrtype={$type}&rrhost={$host}&rrvalue={$value}&rrttl={$ttl}");
		$arr = self::xml2array($xml);
		return $arr['namesilo']['reply']['detail'];
	}
	// Function update domain dns record
	function dnsUpdateRecord($domain,$type,$host="",$value,$ttl="",$rrid){
		$xml = self::xcurl($this->URL.(__FUNCTION__)."?version=1&type=xml&key={$this->KEY}&domain={$domain}&rrtype={$type}&rrhost={$host}&rrvalue={$value}&rrttl={$ttl}&rrid={$rrid}");
		$arr = self::xml2array($xml);
		return $arr['namesilo']['reply']['detail'];
	}
	// Function delete domain dns record,using only domain to bulk delete records
	function dnsDeleteRecord($domain,$rrid=""){
		if($rrid){
			$xml = self::xcurl($this->URL.(__FUNCTION__)."?version=1&type=xml&key={$this->KEY}&domain={$domain}&rrid={$rrid}");
			$arr = self::xml2array($xml);
		}else{
			$dns = self::dnsListRecords($domain);
			foreach($dns as $record){
				$rrid = $record['record_id'];
				$arr['namesilo']['reply']['detail'][$rrid] = self::dnsDeleteRecord($domain,$rrid);
			}
		}
		return $arr['namesilo']['reply']['detail'];
	}
	
}
