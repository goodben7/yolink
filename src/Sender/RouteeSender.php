<?php
namespace App\Sender;

use App\Entity\Campaign;
use App\Exception\CampaignConfigurationException;
use App\Exception\CampaignException;
use App\Model\CampaignProcessorInterface;
use App\Model\CampaignTrackerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RouteeSender implements CampaignProcessorInterface {

    const SENDERID = 'routee';

    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $http,
        private LoggerInterface $logger,
    )
    {
        
    }
    public function support(string $url): bool
    {
        return self::SENDERID === parse_url($url, PHP_URL_SCHEME);
    }

    public function process(Campaign $campaign, CampaignTrackerInterface $tracker): Campaign
    {
        $url = $tracker->getUrl();
        $http = $this->http;
        $logger = $this->logger;

        $token = $this->cache->get('routee_access_token', function (ItemInterface $item) use ($url, $http, $logger) : ?string {
            $publicId = parse_url($url, PHP_URL_USER);
            $secret = parse_url($url, PHP_URL_PASS);

            $resp = $http->request('POST', 'https://auth.routee.net/oauth/token', [
                'auth_basic' => [$publicId, $secret],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);

            if ($resp->getStatusCode() != 200) {
                $logger->warning(sprintf('Routee auth error: message: %s', $resp->getContent()));
                return null;
            }

            $content = $resp->toArray();
            $item->expiresAfter($content['expires_in']);

            return $content['access_token'];

        });

        if (!$token) {
            throw new CampaignConfigurationException('gateway authentication failed');
        }

        $resp = $this->http->request('POST', 'https://connect.routee.net/sms/campaign', [
            'auth_bearer' => $token,
            'json' => [
                'body' => $campaign->getMessage(),
                'from' => $campaign->getTeam()->getSender(),
                'to' => $campaign->getContact(),
                'allowInvalid' => true,
            ]
        ]);

        if ($resp->getStatusCode() != 200) {
            $logger->warning(sprintf('Routee campaign error: message: %s', $resp->getContent()));
            throw new CampaignException($campaign, 'Application cannot send campaign. Try later or contact support.');
        }

        $content = $resp->getContent();
        $this->logger->debug(sprintf('routee response: %s', $content));

        $result = json_decode($content, true);

        if (strtolower($result['state']) == 'delivered') {
            return $tracker->sent($campaign, $result['trackingId']);
        }
        elseif (in_array(strtolower($result['state']), ['queued', 'sent'])) {
            return $tracker->pending($campaign, $result['trackingId']);
        }

        return $tracker->failed($campaign, $result['trackingId']);
    }

    public function getId(): string {
        return self::SENDERID;
    } 
}