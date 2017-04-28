<?php

class Music
{
	
	public function Curl($api)
	{
		$curl = curl_init($api);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (Linux; U; Android 2.3.6; en-us; Nexus S Build/GRK39F) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 AliApp(TT/8.1.0) TTPodClient/8.1.0");
		curl_setopt($curl, CURLOPT_REFERER, "http://y.qq.com");
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		$data = curl_exec($curl);
		return $data;
	}

	public function search($keyword, $page=1)
	{
		$url = 'https://c.y.qq.com/soso/fcgi-bin/search_cp?';
		$key = array(
            'p'        => $page,
            'n'        => 30,
            'w'        => $keyword,
            'cr'       => 1,
            'jsonpCallback' => '?'
        );
        $url = $url.http_build_query($key);
        return $this->get_list($url);
	}

	public function get_list($url)
	{
		$guid = mt_rand();
		$con = $this->Curl('https://c.y.qq.com/base/fcgi-bin/fcg_musicexpress.fcg?json=3&guid='.$guid);
		preg_match('/key": "(.*)"}/', $con, $con);
		$key = $con[1];

		$json = substr($this->Curl($url), 9, -1);
        $arr = json_decode($json, 1);
        foreach ($arr['data']['song']['list'] as $k => $v) {
        	$songlist[$k]['author'] = $v['singer'][0]['name'];
        	$songlist[$k]['title'] = $v['songname'];
        	$songlist[$k]['url'] = 'http://dl.stream.qqmusic.qq.com/'.$v['songid'].'.mp3?vkey='.$key.'&guid='.$guid.'&fromtag=22';
        	$songlist[$k]['pic'] = 'https://y.gtimg.cn/music/photo_new/T002R300x300M000'.$v['albummid'].'.jpg?max_age=2592000';
        }
        return json_encode($songlist, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}
}


$keyword = $_GET['key'];
if ($keyword) {
	$search = new Music();
	echo $search->search($keyword);
}

