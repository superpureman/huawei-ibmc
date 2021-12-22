<?php

namespace Ibmc\ServerApi;

class ServerOperation
{
    public $userName;
    public $password;
    public $header;

    public function __construct($config)
    {
        if (empty($config['user_name']) || empty($config['password'])) {
            throw new \Exception('缺少配置参数');
        }
        $this->userName = $config['user_name'];
        $this->password = $config['password'];
    }

    /**
     * 获取auth token
     * @param $ip
     * @return bool
     */
    public function getAuthToken($ip)
    {
        $input           = [];
        $input['url']    = "http://$ip/redfish/v1/SessionService/sessions";
        $input['params'] = [
            'userName' => $this->userName,
            'Password' => $this->password,
            'Oem'      => [
                'Huawei' => [
                    'Domain' => 'domain',
                ]
            ],
        ];

        $headers = $this->requestPost($input['url'], json_encode($input['params'], 320), true);

        if (!empty($headers) && !empty($headers['x-auth-token'])) {
            $this->header = $headers['x-auth-token'];
            return true;
        }
        return false;
    }

    /**
     * @param $ip
     * @return bool|mixed
     * @throws \Exception
     */
    public function getServerStatus($ip)
    {
        if (!isset($ip)) {
            throw new \Exception('ip must be exist', 40100);
        }
        $is_token = $this->getAuthToken($ip);
        if (!$is_token) {
            throw new \Exception('get auth token failed', 40100);
        }
        $input['params'] = [];
        $input['url']    = "https://$ip/redfish/v1/SystemOverview";
        $ret             = $this->call($input);
        if (!empty($ret)) {
            return $ret['Systems']['HealthSummary'];
        }
        return false;
    }


    public function call($input = [], $httpMethod = 'GET', $desc = '')
    {
        if ('GET' == $httpMethod) {
            $result = json_decode($this->requestGet($input['url'], $input['params']), true);
        } elseif ('POST_JSON' == $httpMethod) {
            $data   = json_encode($input['params'], 320);
            $result = json_decode($this->requestPost($input['url'], $data), true);
        } elseif ('POST' == $httpMethod) {
            $result = json_decode($this->requestPostData($input['url'], $input['params']), true);
        } elseif ('POST_URL' == $httpMethod) {
            $result = json_decode($this->requestPostUrl($input['url'], $input['params']), true);
        } else {
            throw new Exception($desc . '请求未定义，方式为：' . $httpMethod);
        }
        return $result;
    }

    /**
     * post urlencoded请求
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestPostUrl($url, $data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $this->header]);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    /**
     *
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestGet($url, $data)
    {
        //初始化
        $curl = curl_init();
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $this->header]);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    /**
     * post请求
     * @param $url
     * @param $data
     * @return bool|string
     */
    private function requestPostData($url, $data)
    {
        //初始化
        $curl = curl_init();

        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $this->header]);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $result;
    }

    private function requestPost($url, $data, $isHeader = false)
    {
        //初始化
        $curl   = curl_init();
        $header = array('Content-Type: application/json', 'Content-Length: ' . strlen($data));

        if (!$isHeader) {
            array_push($header, ['X-Auth-Token: ' . $this->header]);
        }
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $headers = [];
        if ($isHeader) {
            curl_setopt($curl, CURLOPT_HEADERFUNCTION,
                function ($curl_info, $header) use (&$headers) {
                    $len    = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                        return $len;

                    $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                    return $len;
                }
            );
        }
        //执行命令
        $result = curl_exec($curl);

        //关闭URL请求
        curl_close($curl);
        if ($isHeader) {
            return $headers;
        }
        //显示获得的数据
        return $result;
    }
}