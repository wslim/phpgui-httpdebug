<?php
namespace Wslim\Util;

/**
 * HttpRequest
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class HttpRequest
{
    const METHODS       = array('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH');
    const DATA_TYPES    = ['text', 'array', 'xml', 'object'];
    
    /**********************************************************
     * static mathods
     **********************************************************/
    /**
     * http request
     * @param  string|array $method
     * @param  string       $url
     * @param  string|array $data
     * @param  array        $options
     * @return static
     */
    static public function request($method, $url=null, $data = false, $options = false)
    {
        $instance = new static($method, $url, $data, $options);
        return $instance->execute();
    }
    
    /**
     * 模拟GET请求
     *
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     * 
     * @return mixed 失败时返false,成功返响应主体
     * 
     * @example
     * ```
     * HttpRequest::get('http://api.example.com/?a=123&b=456');
     * ```
     */
    static public function get($url, $data=null, $options=null)
    {
        $instance = new static('GET', $url, $data, $options);
        return $instance->execute()->getResponse();
    }
    
    /**
     * 模拟POST请求
     *
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     *
     * @return mixed 失败时返false,成功返post的响应值
     *
     * @example
     * ```
     * HttpRequest::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'));
     * HttpRequest::post('http://api.example.com/', '这是post原始内容');
     * HttpRequest::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg')); //文件post上传
     * ```
     */
    static public function post($url, $data, $options=null)
    {
        $instance = new static('POST', $url, $data, $options);
        return $instance->execute()->getResponse();
    }
    
    /**
     * download get请求，用于下载请求内容；返回header信息和body合并组成的数组，可根据 content_type 判断mime类型来确定内容类型
     *
     * @param  string       $url
     * @param  string       $saveFile
     * @param  string|array $data
     * @param  array        $options
     * 
     * @return array  [
     *      'http_code' => 200,
     *      'body'      => false for failed or string for success
     * ]
     *
     * -- 返回结果示例
     * {
     "url": "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=My4oqLEyFVrgFF-XOZagdvbTt9XywYjGwMg_GxkPwql7-f0BpnvXFCOKBUyAf0agmZfMChW5ECSyTAgAoaoU2WMyj7aVHmB17ce4HzLRZ3XFTbm2vpKt_9gYA29xrwIKpnvH-BYmNFSddt7re5ZrIg&media_id=QQ9nj-7ctrqA8t3WKU3dQN24IuFV_516MfZRZNnQ0c-BFVkk66jUkPXF49QE9L1l",
     "content_type": "image/jpeg",
     "http_code": 200,
     "header_size": 308,
     "request_size": 316,
     "filetime": -1,
     "ssl_verify_result": 0,
     "redirect_count": 0,
     "total_time": 1.36,
     "namelookup_time": 1.016,
     "connect_time": 1.078,
     "pretransfer_time": 1.078,
     "size_upload": 0,
     "size_download": 105542,
     "speed_download": 77604,
     "speed_upload": 0,
     "download_content_length": 105542,
     "upload_content_length": 0,
     "starttransfer_time": 1.141,
     "redirect_time": 0,
     "body": .....   // 这部分是合并进来的，响应返回的内容即文件内容
     }
     */
    static public function download($url, $saveFile=null, $data=null, $options=null)
    {
        $instance = new static('GET', $url, $data, $options);
        $http = $instance->execute();
        if ($error = $http->getError()) {
            return $error;
        }
        
        $info = $http->getResponseInfo();
        if ($http->getStatus() != '200') {
            return array_merge(['errcode'=>-1], $info);
        }
        
        if ($saveFile) {
            if ($content_type = explode(';', $info['content_type'], 1)) {
                $content_type = explode('/', $content_type[0], 2);
                $fileExt = $content_type[count($content_type) - 1];
                if ( (pathinfo($saveFile, PATHINFO_EXTENSION) !== $fileExt) && strlen($fileExt) < 5) {
                    $saveFile .= '.' . $fileExt;
                }
            }
            $len = file_put_contents($saveFile, $http->getResponseText());
            if ($len) {
                return ['errcode'=>0, 'errmsg'=>'保存成功'];
            }
        }
        
        $arr = ['errcode'=>0, 'body'=>$http->getResponseText()];
        return array_merge($arr, $info);
    }
    
    /**********************************************************
     * instance mathods
     **********************************************************/
    /**
     * definition
     * @var array
     */
    private $def = [
        'method'    => 'GET',
        'url'       => null,
        'data'      => null,
        'dataType'  => 'text',  // text/json/xml
        'header'    => [
            'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language'   => 'zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding'   => 'gzip, deflate, br',
            'Accept-Charset'    => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'User-Agent'        => 'Mozilla/5.0 Firefox/56.0',
            'Connection'        => 'close',
        ],
        'cookie'    => null,
        'success'   => null,
        'failed'    => null,
        'timeout'   => 0,
    ];
    
    /**
     * options, name is upper, contain curl options with prefix CURLOPT_
     * @var array
     */
    private $options = [
        'HTTP_VERSION'  => '1.1',
        'USE_CURL'      => true,
        'RETURN_HEADERS'=> 1,
    ];
    
    private $error, $xmlErrors;
    private $responseInfo, $responseHeaders, $responseText, $status;
    
    /**
     * consturct
     * 
     * @param string       $method
     * @param string       $url
     * @param array|string $data
     * @param array        $options
     */
    function __construct($method, $url=null, $data = false, $options = false) 
    {
        if (is_array($method)) {
            $this->setOptions($method);
        } else {
            $this->setMethod($method);
            $this->setUrl($url);
            if ($data) {
                if (is_string($data) && in_array($data, static::DATA_TYPES)) {
                    $this->setDataType($data);
                } else {
                    $this->setData($data);
                }
            }
            if ($options) {
                if (is_string($options) && in_array($options, static::DATA_TYPES)) {
                    $this->setDataType($options);
                } else {
                    $this->setOptions($options);
                }
            }
        }
    }
    
    public function setMethod($method)
    {
        $method = strtoupper($method);
        if (in_array($method, static::METHODS)) {
            $this->def['method'] = $method;
        } else {
            $this->error = ['errcode'=>-1, 'errmsg'=>"Invalid method: $method"];
        }
        return $this;
    }
    
    public function setUrl($url)
    {
        if (strpos($url, 'https:') === 0) {
            $this->def['ssl'] = true;
        } else {
            if (strpos($url, 'http:') === false) {
                $url = 'http://' . $url;
            }
        }
        
        $this->def['url'] = $url;
        return $this;
    }
    
    public function setData($data) {
        $this->def['data'] = $data;
        return $this;
    }
    
    public function setDataType($dataType) {
        $this->def['dataType'] = $dataType;
        return $this;
    }
    
    public function setTimeout($timeout)
    {
        $this->def['timeout'] = $timeout;
        return $this;
    }
    
    public function setHeader($name, $value=null) 
    {
        if (is_array($name)) {
            $this->def['header'] = array_merge($this->def['header'], $name);
        } else {
            $this->def['header'][$name] = $value;
        }
        return $this;
    }
    
    public function setCookie($name, $value=null) 
    {
        $str = '';
        if (is_array($name)) {
            $arr = [];
            foreach ($name as $k=>$v) {
                $arr[] = $k . '=' . $v;
            }
            $str = implode(';', $arr);
        } elseif ($value) {
            $str = $name . '=' . $value;
        } else {
            $str = $name;
        }
        
        $this->def['cookie'] = $this->def['cookie'] ? $this->def['cookie'].';'.$str : $str;

        return $this;
    }
    
    
    public function setSuccess($func)
    {
        $this->def['success'] = $func;
    }
    
    public function setFailed($func)
    {
        $this->def['failed'] = $func;
    }
    
    public function setOptions($option, $value=null) 
    {
        if (is_array($option)) {
            foreach ($option as $k=>$v) {
                $this->setOptions($k, $v);
            }
        } else {
            // 兼容转换
            if ($option === 'cookies') {
                $option = 'cookie';
            } elseif ($option === 'headers') {
                $option = 'header';
            }
            
            if (array_key_exists($option, $this->def)) {
                $call = 'set' . ucfirst($option);
                if (method_exists($this, $call)) {
                    $this->$call($value);
                }
            } else {
                $this->options[strtoupper($option)] = $value;
            }
        }
        return $this;
    }
    
    /**
     * execute
     * @return static
     */
    public function execute() 
    {
        if ($this->options['USE_CURL'] && function_exists('curl_init')) {
            $this->curlExecute();
        } else {
            $this->fsockgetExecute();
        }
        return $this;
    }
    
    /**
     * convert @ prefixed file names to CurlFile class, since @ prefix is deprecated as of PHP 5.6
     * @param mixed $data
     * @param mixed
     */
    protected function parseData($data=null)
    {
        if (!$data) $data = $this->def['data'];
        
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (strpos($v, '@') === 0 && class_exists('\CURLFile')) {
                    $v = ltrim($v, '@');
                    $data[$k] = new \CURLFile($v);
                }
            }
        }
        
        return $data;
    }
    
    protected function parseQuery($data=null)
    {
        if (!$data) $data = $this->def['data'];
        
        if (is_array($data)) {
            $data_array = array();
            foreach ($this->def['data'] as $key => $val) {
                if (!is_string($val)) {
                    $val = json_encode($val);
                }
                $data_array[] = urlencode($key).'='.urlencode($val);
            }
            return implode('&', $data_array);
        } else {
            return $data;
        }
    }
    
    private function parseCallback($func)
    {
        if (is_callable($func)) {
            return $func;
        } elseif (is_string($func)) {
            return [&$this, $func];
        } else {
            return null;
        }
    }
    
    /**
     * parse header to array
     * @param  $str
     * @return array
     */
    private function header2Array($str)
    {
        if (is_array($str)) return $str;
        
        $result = [];
        $array = explode("\n", trim(str_replace("\r\n", "\n", $str), "\n"));
        foreach($array as $i => $line) {
            if ($i === 0) {
                $result['Http-Status'] = $line; // HTTP/1.1 200 OK
            } else {
                $header = explode(': ', $line);
                if (!$header[0]) continue;
                
                if (isset($header[1])) {
                    $result[$header[0]] = trim($header[1]);
                } else {
                    $result[] = trim($header[0]);
                }
            }
        }
        return $result;
    }
    
    /**
     * parse header to string
     * @param  mixed  $headers
     * @return string
     */
    private function header2String($header)
    {
        $str = '';
        if (is_array($header)) foreach ($header as $k=>$v) {
            if (is_numeric($k)) continue;
            $str .= $k . ': ' . $v . "\r\n";
        } else {
            $str = $header;
        }
        return $str;
    }
    
    private function curlExecute() 
    {
        // check
        if (!$this->def['url']) {
            return $this->setError('url is not set');
        }
        
        $ch = curl_init();
        
        // method and data
        $method = $this->def['method'];
        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            
            if ($this->def['data']) {
                $this->def['url'] .= (strpos($this->def['url'], '?') ? "&" : '?') . $this->parseQuery($this->def['data']);
            }
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($this->def['data']) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parseData($this->def['data']));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_PUT, true);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        // header, require set array
        if ($this->def['header']) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header2Array($this->def['header']));
        }
        
        // receive header
        $returnHeaders = (bool) $this->options['RETURN_HEADERS'];
        curl_setopt($ch, CURLOPT_HEADER, $returnHeaders);       // 设为 TRUE 获取responseHeader，curl_exec()返回结果是 header和body的组合文本，需要手动分离
        curl_setopt($ch, CURLINFO_HEADER_OUT, $returnHeaders);  // 设为 TRUE 时curl_getinfo()返回结果包含 request_header 信息，从 PHP 5.1.3 开始可用。
        
        // register callback which process the headers
        if (isset($this->def['headerCallback']) && $this->def['headerCallback']) {
            if (!is_callable($this->def['headerCallback']) && is_string($this->def['headerCallback'])) {
                $this->def['headerCallback'] = array(&$this, $this->def['headerCallback']);
            }
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, $this->def['headerCallback']);
        }
        
        // cookie
        if ($this->def['cookie']) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->def['cookie']);
        }
        
        // url and base
        curl_setopt($ch, CURLOPT_URL, $this->def['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );   // return result
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // allow redirect
        if ($this->def['timeout']) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->def['timeout']);
        }
        
        // ssl
        if (strpos($this->def['url'], 'https') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 1的值不再支持，请使用2或0
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        
        // Authentication
        if (isset($this->def['authUsername']) && isset($this->def['authPassword']) && $this->def['authUsername']) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->def['authUsername'] . ':' . $this->def['authPassword']);
        }
        
        // custom option
        try {
            foreach ($this->options as $k => $v) {
                if (strpos($k, 'CURLOPT_') !== false) {
                    curl_setopt($ch, get_defined_constants()[$k], $v);
                }
            }
        } catch (\Exception $e) {
            //
        }
        
        $this->responseText     = curl_exec($ch);    // 如果设置了 CURLOPT_HEADER, 返回结果是 header和body的组合文本，需要手动分离
        $this->status           = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->responseInfo     = curl_getinfo($ch);
        
        if ($errno = curl_errno($ch)) {
            $this->setError(curl_errno($ch), curl_error($ch));
        } else {
            if ($returnHeaders) {
                // 如果有302,需要去掉多个headers
                $res = explode("\r\n\r\n", $this->responseText, 3);
                if (($count = count($res)) > 1) {
                    $this->responseHeaders  = $this->header2Array($res[$count - 2]);
                    $this->responseText     = $res[$count - 1];
                }
            }
        }
        
        curl_close($ch);
        
        // handle success/failed callback
        static::callback();
    }
    
    protected function fsockgetExecute() 
    {
        $uri        = $this->def['url'];
        $method     = $this->def['method'];
        $httpVersion = $this->options['HTTP_VERSION'];
        $data       = $this->def['data'];
        $crlf = "\r\n";
        
        $rsp = '';
        
        // parse host, port
        preg_match('/(http\\s?):\/\/([^\:\/]+)(:\d+)?/', $uri, $matches);
        $isSSL = isset($matches[1]) && $matches[1]=='https' ? true : false;
        $host = isset($matches[2]) ? $matches[2] : null;
        $port = isset($matches[3]) ? str_replace(':', '', $matches[3]) : null;
        if (!$host) {
            $this->setError('Host set error'.$uri);
            return false;
        }
        $port = $port ? : ($isSSL ? 443 : 80);
        
        // Deal with the data first.
        if ($data && $method === 'POST') {
            $data = $this->parseQuery($data);
        } else if ($data && $method === 'GET') {
            $uri .= (strpos($uri, '?') ? "&" : '?') . $this->parseQuery($data);
            $data = $crlf;
        } else {
            $data = $crlf;
        }
        
        // Then add
        if ($method === 'POST') {
            $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            $this->setHeader('Content-Length', strlen($data));
        } else {
            $this->setHeader('Content-Type', 'text/plain');
            $this->setHeader('Content-Length', strlen($crlf));
        }
        if (isset($this->def['authUsername']) && isset($this->def['authPassword']) && $this->def['authUsername'] && $this->def['authPassword']) {
            $this->setHeader('Authorization', 'Basic '.base64_encode($this->def['authUsername'].':'.$this->def['authPassword']));
        }
        
        $headers = $this->def['header'];
        $req = '';
        $req .= $method.' '.$uri.' HTTP/'.$httpVersion.$crlf;
        $req .= "Host: ".$host.$crlf;
        foreach ($headers as $header => $content) {
            if (is_numeric($header)) continue;  // 跳过无效值
            $req .= $header.': '.$content.$crlf;
        }
        $req .= $crlf;
        if ($method === 'POST') {
            $req .= $data;
        } else {
            $req .= $crlf;
        }
        
        // Construct hostname.
        $fsock_host = ($isSSL ? 'ssl://' : '').$host;
        
        // Open socket.
        $httpreq = @fsockopen($fsock_host, $port, $errno, $errstr, 30);
        
        // Handle an error.
        if (!$httpreq) {
            $this->setError($errno, $errstr);
            return false;
        }
        
        // Send the request.
        fputs($httpreq, $req);
        
        // Receive the response.
        /*
        while ($line = fgets($httpreq)) {
            $rsp .= $line;
        }
        */
        while (!feof($httpreq)) {
            $rsp .= fgets($httpreq);
        }
        
        // Extract the headers and the responseText.
        list($headers, $responseText) = explode($crlf.$crlf, $rsp, 2);
        
        // Store the finalized response.
        // HTTP/1.1 下过滤掉分块的标志符
        if ($httpVersion == '1.1') {
            $responseText = static::unchunkHttp11($responseText);
        }
        $this->responseText = $responseText;
        
        // Store the response headers.
        $headers = explode($crlf, $headers);
        $this->status = array_shift($headers);  // HTTP/1.1 200 OK
        $this->status = explode(' ', $this->status)[1];
        $this->responseHeaders = array();
        foreach ($headers as $header) {
            list($key, $val) = explode(': ', $header);
            $this->responseHeaders[$key] = $val;
        }
        
        fclose($httpreq);
        
        // handle success/failed callback
        static::callback();
    }
    
    private function callback()
    {
        if (!$this->error) {
            if ($this->def['success']) {
                $callback = static::parseCallback($this->def['success']);
                $callback($this->getResponse(), $this->getVerboseResponse());
            }
        } else {
            if ($this->def['failed']) {
                $callback = static::parseCallback($this->def['success']);
                $callback($this->getError());
            }
        }
    }
    
    /**
     * get error, ['errcode'=>, 'errmsg'=>]
     * @return array
     */
    public function getError() 
    {
        return $this->error;
    }
    
    public function gerErrorString()
    {
        return $this->error ? $this->error['errcode'] . ':' . $this->error['errmsg'] : null;
    }
    
    private function setError($errno, $errmsg=null)
    {
        $this->error = is_numeric($errno) ? ['errcode' => $errno, 'errmsg' => $errmsg] : ['errcode' => -1, 'errmsg' => $errno];
        return $this;
    }
    
    /**
     * get resposne
     * @return mixed false if failure
     */
    public function getResponse()
    {
        if ($this->error) {
            return false;
        }
        switch ($this->def['dataType']) {
            case 'array':
                $result = static::asArray();
                break;
            case 'xml':
                $result = static::asXml();
                break;
            case 'object':
                $result = static::asObject();
                break;
            default:
                $result = $this->responseText;
        }
        return $result;
    }
    
    /**
     * get verbose response, ['error'=>, 'status'=>, 'info'=>, 'body'=>, 'header'=>, 'request_header'=>]
     * @return array
     */
    public function getVerboseResponse() 
    {
        return [
            'error'     => $this->error,
            'status'    => $this->status,
            'info'      => $this->responseInfo,
            'body'      => $this->responseText, 
            'header'    => $this->responseHeaders,
            'request_header' => $this->getRequestHeaders(),
        ];
    }
    
    /**
     * get resposne status code: 200|xxx
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * get response info
     * @return array
     */
    public function getResponseInfo()
    {
        return $this->responseInfo;
    }
    
    /**
     * get response text
     * @return false|string false if failure
     */
    public function getResponseText() 
    {
        if ($this->error) {
            return false;
        }
        
        return $this->responseText;
    }
    
    /**
     * get response headers
     * @return array
     */
    public function getResponseHeaders() 
    {
        return $this->responseHeaders;
    }
    
    public function getResponseHeadersString()
    {
        return static::header2String($this->responseHeaders);
    }
    
    public function getResponseHeader($header) {
        $headers = $this->responseHeaders;
        if (array_key_exists($header, $headers)) {
            return $headers[$header];
        }
        return null;
    }
    
    public function getResponseCookie($cookie = false)
    {
        if($cookie !== false) {
            return isset($this->responseHeaders["Set-Cookie"][$cookie]) ? $this->responseHeaders["Set-Cookie"][$cookie] : null;
        }
        return isset($this->responseHeaders["Set-Cookie"]) ? $this->responseHeaders["Set-Cookie"] : null;
    }
    
    public function getRequestString()
    {
        return $this->parseQuery();
    }
    
    public function getRequestHeaders()
    {
        return isset($this->responseInfo['request_header']) ? $this->header2Array($this->responseInfo['request_header']) : null;
    }
    
    public function getRequestHeadersString()
    {
        return isset($this->responseInfo['request_header']) ? $this->responseInfo['request_header'] : '';
    }
    
    public function asObject() 
    {
        return json_decode($this->responseText);
    }
    
    public function asArray()
    {
        return (!is_array($this->responseText)) && strpos($this->responseText, '{' !== false) && strpos($this->responseText, '[' !== false) 
        ? json_decode($this->responseText, true) : (array) $this->responseText;
    }
    
    public function asXml($useErrors = false) 
    {
        libxml_use_internal_errors($useErrors);
        $xml = simplexml_load_string($this->responseText);
        if($useErrors == false) $this->xmlErrors = libxml_get_errors();
        return $xml;
    }
    
    /**
     * fsockopen 读取因为使用了 Transfer-Encoding: chunked, 会多出分块时的数字字符，需要去掉。方法一，会用如下，方法二，使用 HTTP/1.0
     * @param  string $data
     * @return string
     */
    function unchunkHttp11($data) {
        /*
        $fp = 0;
        $outData = "";
        while ($fp < strlen($data)) {
            $rawnum = substr($data, $fp, strpos(substr($data, $fp), "\r\n") + 2);
            $num = hexdec(trim($rawnum));
            $fp += strlen($rawnum);
            $chunk = substr($data, $fp, $num);
            $outData .= $chunk;
            $fp += strlen($chunk);
        }
        return $outData;
        */
        
        return preg_replace_callback(
            '/(?:(?:\r\n|\n)|^)([0-9A-F]+)(?:\r\n|\n){1,2}(.*?)'.
            '((?:\r\n|\n)(?:[0-9A-F]+(?:\r\n|\n))|$)/si',
            create_function(
                '$matches',
                'return hexdec($matches[1]) == strlen($matches[2]) ? $matches[2] : $matches[0];'
                ),
            $data
        );
    }
    
    
    
    
}