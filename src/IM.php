<?php

/*
 * This file is part of the hedeqiang/im.
 *
 * (c) hedeqiang<laravel_code@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Hedeqiang\IM;

use Hedeqiang\IM\Exceptions\Exception;
use Hedeqiang\IM\Exceptions\HttpException;
use Hedeqiang\IM\Support\Config;
use Hedeqiang\IM\Traits\HasHttpRequest;
use Psr\SimpleCache\CacheInterface;
use Tencent\TLSSigAPIv2;
use Hyperf\Utils\ApplicationContext;

class IM
{
    use HasHttpRequest;

//    const ENDPOINT_TEMPLATE = 'https://console.tim.qq.com/%s/%s/%s?%s';

    protected $imUrls = [
        'zh' => 'https://console.tim.qq.com/%s/%s/%s?%s',
        'sgp' => 'https://adminapisgp.im.qcloud.com/%s/%s/%s?%s',
        'kr' => 'https://adminapikr.im.qcloud.com/%s/%s/%s?%s',
        'ger' => 'https://adminapiger.im.qcloud.com/%s/%s/%s?%s',
        'ind' => 'https://adminapiind.im.qcloud.com/%s/%s/%s?%s',
        'usa' => 'https://adminapiusa.im.qcloud.com/%s/%s/%s?%s',
    ];

    const ENDPOINT_VERSION = 'v4';

    const ENDPOINT_FORMAT = 'json';

    /**
     * @var Config
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * @param string $servername
     * @param string $command
     *
     * @return array
     *
     * @throws Exception
     * @throws HttpException
     */
    public function send($servername, $command, array $params = [])
    {
        try {
            $result = $this->postJson($this->buildEndpoint($servername, $command), $params);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        if (0 === $result['ErrorCode'] && 'OK' === $result['ActionStatus']) {
            return $result;
        }

        throw new Exception('Tim REST API error: '.json_encode($result));
    }

    /**
     * Build endpoint url.
     *
     * @throws \Exception
     */
    protected function buildEndpoint(string $servername, string $command): string
    {
        $imUrl = $this->config->get('region') ? $this->imUrls[$this->config->get('region')] : $this->imUrls['zh'];
        $query = http_build_query([
            'sdkappid' => $this->config->get('sdk_app_id'),
            'identifier' => $this->config->get('identifier'),
            'usersig' => $this->generateSign($this->config->get('identifier')),
            'random' => mt_rand(0, 4294967295),
            'contenttype' => self::ENDPOINT_FORMAT,
        ]);

//        return \sprintf(self::ENDPOINT_TEMPLATE, self::ENDPOINT_VERSION, $servername, $command, $query);
        return \sprintf($imUrl, self::ENDPOINT_VERSION, $servername, $command, $query);
    }

    /**
     * Generate Sign.
     *
     * @throws \Exception
     */
    protected function generateSign(string $identifier, int $expires = 15552000): string
    {
        $cache = $this->di()->get(CacheInterface::class);

        if (!$cache->has($identifier.'_cache')) {
            $api = new TLSSigAPIv2($this->config->get('sdk_app_id'), $this->config->get('secret_key'));
            $sign = $api->genUserSig($identifier, $expires);
            $cache->set($identifier.'_cache', $sign, $expires);

            return $sign;
        }

        return $cache->get($identifier.'_cache');
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param mixed|null $id
     *
     * @return mixed|\Psr\Container\ContainerInterface
     */
    protected function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }

        return $container;
    }
}
