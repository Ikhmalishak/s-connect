<?php

namespace App\Services;

use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\Generated\Models as Model;
use Illuminate\Support\Facades\Log;

class TeamsService
{
    private function getTenantId(): string
    {
        return config('services.azure.tenant_id', env('AZURE_TENANT_ID'));
    }

    private function getClientId(): string
    {
        return config('services.azure.client_id', env('AZURE_CLIENT_ID'));
    }

    private function getClientSecret(): string
    {
        return config('services.azure.client_secret', env('AZURE_CLIENT_SECRET'));
    }

    public function getGraphClient(): GraphServiceClient
    {
        $tokenRequestContext = new ClientCredentialContext(
            $this->getTenantId(),
            $this->getClientId(),
            $this->getClientSecret()
        );

        return new GraphServiceClient($tokenRequestContext, ['https://graph.microsoft.com/.default']);
    }

    /**
     * Send a personal message to a user
     */
    public function sendPersonalMessage(string $userEmail, string $message): bool
    {
        try {
            $graph = $this->getGraphClient();

            // Find user by email
            $user = $graph->users()->byUserId($userEmail)->get()->wait();

            // Create chat
            $chat = new Model\Chat();
            $chat->setChatType('oneOnOne');

            $member1 = new Model\AadUserConversationMember();
            $member1->setOdataType('#microsoft.graph.aadUserConversationMember');
            $member1->setRoles(['owner']);
            $member1->setAdditionalData(['user@odata.bind' => "https://graph.microsoft.com/v1.0/users('" . $user->getId() . "')"]);

            $member2 = new Model\AadUserConversationMember();
            $member2->setOdataType('#microsoft.graph.aadUserConversationMember');
            $member2->setRoles(['owner']);
            $member2->setAdditionalData(['user@odata.bind' => "https://graph.microsoft.com/v1.0/users('" . $this->getClientId() . "')"]);

            $chat->setMembers([$member1, $member2]);

            $createdChat = $graph->chats()->post($chat)->wait();

            // Send message
            $messageObj = new Model\ChatMessage();
            $body = new Model\ItemBody();
            $body->setContentType('text');
            $body->setContent($message);
            $messageObj->setBody($body);

            $graph->chats()->byChatId($createdChat->getId())->messages()->post($messageObj)->wait();

            Log::info('Teams personal message sent successfully', [
                'user_email' => $userEmail,
                'message' => $message
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send Teams personal message', [
                'user_email' => $userEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send message to a Teams channel using Incoming Webhook
     */
    public function sendChannelMessage(string $webhookUrl, $content): bool
    {
        try {
            if (is_string($content)) {
                // Simple text message
                $payload = [
                    'text' => $content
                ];
            } elseif (is_array($content)) {
                // Adaptive card
                $payload = [
                    'type' => 'message',
                    'attachments' => [
                        [
                            'contentType' => 'application/vnd.microsoft.card.adaptive',
                            'content' => $content
                        ]
                    ]
                ];
            } else {
                throw new \InvalidArgumentException('Content must be string or array');
            }

            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Teams webhook message sent successfully', [
                    'content_type' => is_string($content) ? 'text' : 'card'
                ]);
                return true;
            } else {
                Log::error('Failed to send Teams webhook message', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Teams webhook message', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send Adaptive Card to a user
     */
    public function sendAdaptiveCard(string $userEmail, array $card): bool
    {
        try {
            $graph = $this->getGraphClient();

            // Find user by email
            $user = $graph->createRequest('GET', '/users/' . $userEmail)
                         ->setReturnType(Model\User::class)
                         ->execute();

            // Create or get existing chat
            $chat = [
                'chatType' => 'oneOnOne',
                'members' => [
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'roles' => ['owner'],
                        'user@odata.bind' => 'https://graph.microsoft.com/v1.0/users(\'' . $user->getId() . '\')'
                    ],
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'roles' => ['owner'],
                        'user@odata.bind' => 'https://graph.microsoft.com/v1.0/users(\'' . $this->getClientId() . '\')'
                    ]
                ]
            ];

            $createdChat = $graph->createRequest('POST', '/chats')
                                ->attachBody($chat)
                                ->setReturnType(Model\Chat::class)
                                ->execute();

            // Send adaptive card
            $messageBody = [
                'body' => [
                    'contentType' => 'html',
                    'content' => json_encode($card)
                ]
            ];

            $graph->createRequest('POST', '/chats/' . $createdChat->getId() . '/messages')
                 ->attachBody($messageBody)
                 ->execute();

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send Teams adaptive card', [
                'user_email' => $userEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
