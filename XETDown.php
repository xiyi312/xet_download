<?php

class XETDown
{
    private $curl_m3u8;
    private $curl_ts;
    private $key_url;
    private $dir;
    //根据m3u8文件生成
    private $iv;
    private $prefix_num;
    //m3u8文件的相关正则
    public $ts_pattern = '/f230.+sign=\w{32}/';
    public $iv_pattern = '/IV=0x(\w{32})/';

    public function __construct($curl_m3u8, $curl_ts, $key_url, $dir)
    {
        $this->curl_m3u8 = $curl_m3u8;
        $this->curl_ts = $curl_ts;
        $this->key_url = $key_url;
        $this->dir = $dir;
    }

    /**
     * 通过正则匹配m3u8文件中的每个ts分片的url，返回这个url数组
     *
     * @return array
     */
    private function tsArr()
    {
        $m3 = shell_exec($this->curl_m3u8);
        preg_match_all($this->ts_pattern, $m3, $ts_url);
        preg_match($this->iv_pattern, $m3, $iv);

        $this->iv = $iv[1];
        $this->prefix_num = strlen(count($ts_url[0]));

        return $ts_url[0];
    }

    /**
     * 循环拼接出每个ts分片的curl_bash，然后进行下载
     */
    public function down()
    {
        $ts_url = $this->tsArr();
        foreach ($ts_url as $k=>$v) {
            $curl = preg_replace($this->ts_pattern, $v, $this->curl_ts);
            //生成文件序号
            $num = sprintf('%0'. $this->prefix_num .'d', $k);
            //下载文件
            file_put_contents($this->dir .'/f_' . $num . '.ts', shell_exec($curl));

//            if ($k > 4) break;
        }
        
        $this->compact();
    }

    /**
     * 将ts分片视频合并成一个完整视频
     */
    private function compact()
    {
        shell_exec("cat $this->dir/f_*.ts > $this->dir/build.ts");
    }

    /**
     * 视频解密
     * 小鹅通视频是加密的 需要用iv和key解密
     */
    public function decrypt()
    {
        //把密钥转为16进制
        $key = bin2hex(file_get_contents($this->key_url));

        shell_exec("openssl aes-128-cbc -d -in $this->dir/build.ts -out $this->dir/f.ts -nosalt -iv $this->iv -K $key");
    }
}

$curl_m3u8 = "";

$key_url = '';

$curl_ts = "";


$dir = '/home/vagrant/code/ts';

$d = new XETDown($curl_m3u8, $curl_ts, $key_url, $dir);

$d->down();
$d->decrypt();