<?php
/**
 * User: Yun Lv(yunlv.go@gmail.com)
 * Date: 2020/3/6 21:09
 */

namespace WechatPayment\Kernel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;

class BaseClient
{
    /**
     * @var string
     */
    private $mchId;
    /**
     * @var string
     */
    private $serialNo;
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var string
     */
    private $wxCert;

    public function __construct($mchId, $serialNo, $privateKey, $wxCert)
    {
        $this->mchId = $mchId;
        $this->serialNo = $serialNo;
        $this->privateKey = $privateKey;
        $this->wxCert = $wxCert;
    }

    public function getHttpClient(): Client
    {
        $privateKey = PemUtil::loadPrivateKey($this->privateKey);
        $certificate = PemUtil::loadCertificate($this->wxCert);

        // 构造一个WechatPayMiddleware
        $wechatpayMiddleware = WechatPayMiddleware::builder()
            ->withMerchant($this->mchId, $this->serialNo, $privateKey)
            ->withWechatPay([$certificate]) // 可传入多个微信支付平台证书，参数类型为array
            ->build();

        // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
        $stack = HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');

        // 创建Guzzle HTTP Client时，将HandlerStack传入
        return new Client(['handler' => $stack, 'http_errors' => false]);
    }

    /**
     * GET request
     *
     * @param string $url
     * @param array  $query
     *
     * @throws GuzzleException
     */
    public function httpGet(string $url, array $query = [])
    {
        $this->request($url, ['query' => $query]);
    }

    /**
     * POST request
     *
     * @param string $url
     * @param array  $data
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function httpPost(string $url, array $data = [])
    {
        return $this->request($url, 'POST', ['form_params' => $data]);
    }

    /**
     * JSON request
     *
     * @param string $url
     * @param array  $data
     * @param array  $query
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function httpPostJson(string $url, array $data = [], array $query = [])
    {
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data]);
    }

    /**
     * @param        $url
     * @param string $method
     * @param array  $options
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request(string $url, $method = 'GET', $options = []): ResponseInterface
    {
        $method = strtoupper($method);
        $response = $this->getHttpClient()->request($url, $method, $options);
        $response->getBody()->rewind();

        return $response;
    }
}