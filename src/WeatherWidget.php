<?php

declare(strict_types=1);

namespace BobdenOtter\WeatherWidget;

use Bolt\Widget\BaseWidget;
use Bolt\Widget\CacheAwareInterface;
use Bolt\Widget\CacheTrait;
use Bolt\Widget\Injector\AdditionalTarget;
use Bolt\Widget\Injector\RequestZone;
use Bolt\Widget\StopwatchAwareInterface;
use Bolt\Widget\StopwatchTrait;
use Bolt\Widget\TwigAwareInterface;
use Symfony\Component\HttpClient\HttpClient;

class WeatherWidget extends BaseWidget implements TwigAwareInterface, CacheAwareInterface, StopwatchAwareInterface
{
    use CacheTrait;
    use StopwatchTrait;

    protected ?string $name = 'Weather Widget';
    protected string $target = AdditionalTarget::WIDGET_BACK_DASHBOARD_ASIDE_TOP;
    protected ?int $priority = 200;
    protected ?string $template = '@weather-widget/weather.html.twig';
    protected ?string $zone = RequestZone::BACKEND;
    protected int $cacheDuration = 1800;
    protected string $location = '';

    public function run(array $params = []): ?string
    {
        $weather = $this->getWeather();

        if (empty($weather)) {
            return null;
        }

        return parent::run(['weather' => $weather]);
    }

    private function getWeather(): array
    {
        $url = 'https://wttr.in/' . $this->getLocation() .  '?format=%c|%C|%h|%t|%w|%l|%m|%M|%p|%P';

        $curlOptions = $this->getExtension()->getBoltConfig()->get('general/curl_options', [])->all();
        $curlOptions['timeout'] = 6;

        $details = [];

        try {
            $client = HttpClient::create();
            $result = $client->request('GET', $url, $curlOptions)->getContent();
            if (mb_substr_count($result, '|') === 9) {
                $details = explode('|', trim($result));
            }
        } catch (\Throwable $e) {
            dump($this->getName() . ' exception: ' . $e->getMessage());
            // Do nothing, fall through to empty array
        }

        return $details;
    }

    private function getLocation(): string
    {
        return (string) $this->extension?->getConfig()->get('location');
    }
}
