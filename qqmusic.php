<?php

// qq音乐


// 引入 Curl
require 'vendor/autoload.php';

// 使用 Curl
use \Curl\Curl;

// Curl 内容获取
function curl($args = [])
{
    $default = [
        'method'     => 'GET',
        'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
        'url'        => null,
        'referer'    => 'https://www.google.co.uk',
        'headers'    => null,
        'body'       => null,
        'proxy'      => false
    ];
    $args         = array_merge($default, $args);
    $method       = mb_strtolower($args['method']);
    $method_allow = ['get', 'post'];
    if (null === $args['url'] || !in_array($method, $method_allow, true)) {
        return;
    }
    $curl = new Curl();
    $curl->setUserAgent($args['user-agent']);
    $curl->setReferrer($args['referer']);
    $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
    $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
    if ($args['proxy'] && MC_PROXY) {
        $curl->setOpt(CURLOPT_HTTPPROXYTUNNEL, 1);
        $curl->setOpt(CURLOPT_PROXY, MC_PROXY);
        $curl->setOpt(CURLOPT_PROXYUSERPWD, MC_PROXYUSERPWD);
    }
    if (!empty($args['headers'])) {
        $curl->setHeaders($args['headers']);
    }
    $curl->$method($args['url'], $args['body']);
    $curl->close();
    if (!$curl->error) {
        return $curl->response;
    }
}

// 判断地址是否有误
function is_error($url) {
    $curl = new Curl();
    $curl->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1');
    $curl->head($url);
    $curl->close();
    return $curl->error_code;
}

// jsonp 转 json
function jsonp2json($jsonp) {
    if ($jsonp[0] !== '[' && $jsonp[0] !== '{') {
        $jsonp = mb_substr($jsonp, mb_strpos($jsonp, '('));
    }
    $json = trim($jsonp, "();");
    if ($json) {
        return json_decode($json, true);
    }
}

// 去除字符串转义
function str_decode($str) {
    $str = str_replace(['&#13;', '&#10;'], ['', "\n"], $str);
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    return $str;
}


function musicList($keyword, $page=1)
{
    $args = [
        'method'         => 'GET',
        'url'            => 'http://c.y.qq.com/soso/fcgi-bin/search_for_qq_cp',
        'referer'        => 'http://m.y.qq.com',
        'proxy'          => false,
        'body'           => [
            'w'          => $keyword,
            'p'          => $page,
            'n'          => 30,
            'format'     => 'json'
        ],
        'user-agent'     => 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'
    ];
    $result = curl($args);
    $list = json_decode($result, true);
    return $list['data']['song']['list'];
}

function getLrc($songmid)
{
    $args = [
        'method'        => 'GET',
        'url'           => 'http://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric.fcg',
        'referer'       => 'http://m.y.qq.com',
        'proxy'         => false,
        'body'          => [
            'songmid'   => $songmid,
            'format'    => 'json',
            'nobase64'  => 1,
            'songtype'  => 0,
            'callback'  => 'c'
        ],
        'user-agent'    => 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1'
    ];
    return jsonp2json( curl($args) );
}

function getMusicUrl($songmid)
{
    $data['req'] = [
        'module' => 'vkey.GetVkeyServer',
        'method' => 'CgiGetVkey',
        'param'  => [
            'guid'      => 'ffffffff82def4af4b12b3cd9337d5e7',
            'songmid'   => [$songmid],
            'songtype'  => [0],
            'uin'       => '346897220',
            'loginflag' => 1,
            'platform'  => '20',

        ]
    ];
    $res = json_decode(curl([
        'method'     => 'GET',
        'url'        => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
        'referer'    => 'http://y.qq.com',
        'proxy'      => false,
        'body'       => [
            'data'   => json_encode($data),
        ]
    ]), true);
    $music = $res['req']['data'];
    if ($music['midurlinfo'][0]['vkey']) {
        $vkey = $music['midurlinfo'][0]['vkey'];
    } else {
        preg_match("/vkey\=(\w+)/i", $res['req']['data']['testfile2g'], $key);
        $vkey = $key[1];
    }
    return 'http://mobileoc.music.tc.qq.com/C400' . $songmid . '.m4a?vkey=' . $vkey . '&guid=ffffffff82def4af4b12b3cd9337d5e7&fromtag=53&uin=346897220';
}

function find_music($keyword)
{
    $list = musicList($keyword);
    foreach ($list as $v) {
        $songmid = $v['songmid'];
        $radio_authors       = [];
        foreach ($v['singer'] as $singer) {
            $authors[] = $singer['name'];
        }
        $author = implode(',', $authors);
        $lrc = getLrc($songmid);
        $music = getMusicUrl($songmid);
        $songs[] = [
            'songid' => $songmid,
            'title'  => $v['songname'],
            'author' => $author,
            'lrc'    => str_decode($lrc['lyric']),
            'url'    => $music,
            'pic'    => 'http://y.gtimg.cn/music/photo_new/T002R300x300M000' . $v['albummid'] . '.jpg'
        ];
    }
    return $songs;
}
