<?php

namespace Jero\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use Jero\Weather\Exceptions\Exception;
use Jero\Weather\Exceptions\HttpException;
use Jero\Weather\Exceptions\InvalidArgumentException;
use Jero\Weather\Weather;
use PHPUnit\Framework\TestCase;

class WeatherTest extends TestCase
{
    public function testGetWeather()
    {
        $response = new Response(200, [], '{"success": true}');

        $client = \Mockery::mock(Client::class);

        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        $weather = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['success' => true], $weather->getWeather('深圳'));


        $response = new Response(200, [], '<hello>content</hello>');

        $client = \Mockery::mock(Client::class);

        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $weather = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $weather->getWeather('深圳', 'all', 'xml'));

    }

    public function testGetHttpClient()
    {
        $weather = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $weather->getHttpClient());
    }

    public function testGetGuzzleOptions()
    {
        $weather = new Weather('mock-key');

        $this->assertNull($weather->getHttpClient()->getConfig('timeout'));

        $weather->setGuzzleOptions(['timeout' => 5000]);

        $this->assertSame(5000, $weather->getHttpClient()->getConfig('timeout'));
    }

    public function testGetWeatherWithInvalidType()
    {
        $weather = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid type value(base/all): foo'
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $weather->getWeather('深圳', 'foo');

        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $weather = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid response format: array'
        $this->expectExceptionMessage('Invalid response format: array');

        // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
        $weather->getWeather('深圳', 'base', 'array');

        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()->get(new AnyArgs())->andThrow(new \Exception('request timeout'));

        $weather = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $weather->getWeather('深圳');
    }

    public function testGetLiveWeather()
    {
        $weather = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->expects()->getWeather('深圳', 'base', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $weather->getLiveWeather('深圳'));
    }

    public function testGetForecastsWeather()
    {
        $weather = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->expects()->getWeather('深圳', 'all', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $weather->getForecastsWeather('深圳'));
    }
}