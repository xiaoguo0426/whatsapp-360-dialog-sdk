<?php

namespace Dialog360\Tests;

use Dialog360\Dialog360Client;
use Dialog360\Api\ConversationalComponentsApi;
use Dialog360\Message\TextMessage;
use Dialog360\Message\MediaMessage;
use Dialog360\Message\TemplateMessage;
use Dialog360\Message\InteractiveMessage;
use Dialog360\Message\ContactsMessage;
use Dialog360\Message\DocumentMessage;
use Dialog360\Message\LocationMessage;
use Dialog360\Message\ReactionMessage;
use Dialog360\Message\StickerMessage;
use Dialog360\Exception\Dialog360Exception;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class Dialog360ClientTest extends TestCase
{
    private Dialog360Client $client;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        $this->client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-v2.360dialog.io',
            30,
            3,
            $handlerStack
        );
    }

    public function testSendTextMessageSuccess(): void
    {
        // Cloud API v2 响应结构
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-message-id']
                ]
            ]))
        );

        $message = new TextMessage('1234567890', 'Hello World!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-message-id', $response->getMessageId());
    }

    public function testSendTextMessageFailure(): void
    {
        $this->mockHandler->append(
            new Response(400, [], json_encode([
                'errors' => [
                    [
                        'code' => 'invalid_phone_number',
                        'message' => 'Invalid phone number'
                    ]
                ]
            ]))
        );

        $message = new TextMessage('invalid-number', 'Hello World!');
        $response = $this->client->sendMessage($message);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('invalid_phone_number', $response->getErrorCode());
        $this->assertEquals('Invalid phone number', $response->getErrorMessage());
    }

    public function testSendMediaMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-media-message-id']
                ]
            ]))
        );

        $message = MediaMessage::image('1234567890', 'https://example.com/image.jpg', 'Beautiful image!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-media-message-id', $response->getMessageId());
    }

    public function testSendTemplateMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-template-message-id']
                ]
            ]))
        );

        $message = new TemplateMessage(
            '1234567890',
            'hello_world',
            'en_US',
            [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => 'John'
                        ]
                    ]
                ]
            ]
        );
        
        $response = $this->client->sendMessage($message);
        $this->assertTrue($response->isSuccess());
    }

    public function testSendInteractiveMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-interactive-message-id']
                ]
            ]))
        );

        $message = InteractiveMessage::button(
            '1234567890',
            'Choose an option:',
            [
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'btn_1',
                        'title' => 'Option 1'
                    ]
                ]
            ]
        );
        
        $response = $this->client->sendMessage($message);
        $this->assertTrue($response->isSuccess());
    }

    public function testSendContactsMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-contacts-message-id');

        $contacts = [
            [
                'name' => ContactsMessage::name(
                    formattedName: 'Zhang San',
                    firstName: 'San',
                    lastName: 'Zhang'
                ),
                'birthday' => '1990-01-01',
                'phones' => [
                    ContactsMessage::phone('85268064134', 'CELL'),
                ],
                'emails' => [
                    ContactsMessage::email('zhangsan@example.com', 'WORK'),
                ],
            ],
        ];

        $message = new ContactsMessage('1234567890', $contacts);
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-contacts-message-id', $response->getMessageId());

        // 验证请求 payload 结构
        $payload = $this->getLastRequestPayload();
        $this->assertEquals('contacts', $payload['type']);
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('1234567890', $payload['to']);
        $this->assertCount(1, $payload['contacts']);
        $this->assertEquals('Zhang San', $payload['contacts'][0]['name']['formatted_name']);
        $this->assertEquals('San', $payload['contacts'][0]['name']['first_name']);
        $this->assertEquals('85268064134', $payload['contacts'][0]['phones'][0]['phone']);
        $this->assertEquals('CELL', $payload['contacts'][0]['phones'][0]['type']);
        $this->assertEquals('1990-01-01', $payload['contacts'][0]['birthday']);
    }

    public function testContactsMessageRequiresContacts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('联系人数组不能为空');

        new ContactsMessage('1234567890', []);
    }

    public function testContactsMessageRequiresFormattedName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name.formatted_name');

        new ContactsMessage('1234567890', [
            ['phones' => [['phone' => '85268064134']]]
        ]);
    }

    public function testContactsMessageExceedsMaxContacts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('257');

        $contacts = array_fill(0, 258, ['name' => ['formatted_name' => 'Test User']]);
        new ContactsMessage('1234567890', $contacts);
    }

    public function testSendDocumentMessageByMediaId(): void
    {
        $this->mockSuccessResponse('wamid.test-document-message-id');

        $message = DocumentMessage::fromMediaId(
            '1234567890',
            'test-media-id',
            'Your order confirmation (PDF)',
            'order_abc123.pdf'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('document', $payload['type']);
        $this->assertEquals('test-media-id', $payload['document']['id']);
        $this->assertEquals('Your order confirmation (PDF)', $payload['document']['caption']);
        $this->assertEquals('order_abc123.pdf', $payload['document']['filename']);
        $this->assertArrayNotHasKey('link', $payload['document']);
    }

    public function testSendDocumentMessageByUrl(): void
    {
        $this->mockSuccessResponse('wamid.test-document-message-id');

        $message = DocumentMessage::fromUrl(
            '1234567890',
            'https://example.com/docs/report.pdf',
            'Monthly report'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('document', $payload['type']);
        $this->assertEquals('https://example.com/docs/report.pdf', $payload['document']['link']);
        $this->assertArrayNotHasKey('id', $payload['document']);
    }

    public function testDocumentMessageRequiresMediaIdOrLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new DocumentMessage('1234567890');
    }

    public function testDocumentMessageRejectsBothMediaIdAndLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new DocumentMessage('1234567890', 'media-id', 'https://example.com/doc.pdf');
    }

    public function testDocumentMessageCaptionTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('caption 不能超过 1024');

        DocumentMessage::fromMediaId('1234567890', 'media-id', str_repeat('a', 1025));
    }

    public function testDocumentMessageUnsupportedExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文档扩展名');

        DocumentMessage::fromMediaId('1234567890', 'media-id', null, 'archive.zip');
    }

    public function testSendLocationMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-location-message-id');

        $message = LocationMessage::named(
            '1234567890',
            114.1694,
            22.2933,
            'Victoria Harbour',
            'Tsim Sha Tsui, Hong Kong'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('location', $payload['type']);
        $this->assertEquals(114.1694, $payload['location']['longitude']);
        $this->assertEquals(22.2933, $payload['location']['latitude']);
        $this->assertEquals('Victoria Harbour', $payload['location']['name']);
        $this->assertEquals('Tsim Sha Tsui, Hong Kong', $payload['location']['address']);
    }

    public function testSendLocationCoordinatesOnly(): void
    {
        $this->mockSuccessResponse('wamid.test-location-message-id');

        $message = LocationMessage::coordinates('1234567890', 114.1694, 22.2933);
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('location', $payload['type']);
        $this->assertArrayNotHasKey('name', $payload['location']);
        $this->assertArrayNotHasKey('address', $payload['location']);
    }

    public function testLocationMessageInvalidLatitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('纬度必须在 -90 到 90 之间');

        new LocationMessage('1234567890', 114.1694, 91.0);
    }

    public function testLocationMessageInvalidLongitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('经度必须在 -180 到 180 之间');

        new LocationMessage('1234567890', 181.0, 22.2933);
    }

    public function testLocationMessageAddressRequiresName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('address 仅在提供 name 时才能使用');

        new LocationMessage('1234567890', 114.1694, 22.2933, null, 'Some address');
    }

    public function testSendReactionMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-reaction-message-id');

        $message = ReactionMessage::react('1234567890', 'wamid.test-message-id', '👍');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('reaction', $payload['type']);
        $this->assertEquals('wamid.test-message-id', $payload['reaction']['message_id']);
        $this->assertEquals('👍', $payload['reaction']['emoji']);
    }

    public function testRemoveReactionMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-reaction-message-id');

        $message = ReactionMessage::remove('1234567890', 'wamid.test-message-id');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        // 官方规定移除回应时 emoji 为空字符串
        $payload = $this->getLastRequestPayload();
        $this->assertEquals('', $payload['reaction']['emoji']);
    }

    public function testReactionMessageRequiresMessageId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('message_id 不能为空');

        new ReactionMessage('1234567890', '', '👍');
    }

    public function testSendStickerMessageByMediaId(): void
    {
        $this->mockSuccessResponse('wamid.test-sticker-message-id');

        $message = StickerMessage::fromMediaId('1234567890', 'test-sticker-media-id');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('sticker', $payload['type']);
        $this->assertEquals('test-sticker-media-id', $payload['sticker']['id']);
        $this->assertArrayNotHasKey('link', $payload['sticker']);
    }

    public function testSendStickerMessageByUrl(): void
    {
        $this->mockSuccessResponse('wamid.test-sticker-message-id');

        $message = StickerMessage::fromUrl('1234567890', 'https://example.com/sticker.webp');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('sticker', $payload['type']);
        $this->assertEquals('https://example.com/sticker.webp', $payload['sticker']['link']);
        $this->assertArrayNotHasKey('id', $payload['sticker']);
    }

    public function testStickerMessageRequiresMediaIdOrLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new StickerMessage('1234567890');
    }

    public function testGetHealthStatus(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'health_status' => [
                    'can_send_message' => 'AVAILABLE',
                    'entities' => [
                        [
                            'entity_type' => 'PHONE_NUMBER',
                            'id' => '106540352242922',
                            'can_send_message' => 'AVAILABLE'
                        ],
                        [
                            'entity_type' => 'WABA',
                            'id' => '102290129340398',
                            'can_send_message' => 'AVAILABLE'
                        ]
                    ]
                ],
                'id' => '106540352242922'
            ]))
        );

        $health = $this->client->getHealthStatus();
        $this->assertEquals('AVAILABLE', $health['health_status']['can_send_message']);
        $this->assertCount(2, $health['health_status']['entities']);
    }

    public function testGetConversationalAutomation(): void
    {
        $this->mockJsonResponse(200, [
            'id' => '106540352242922',
            'conversational_automation' => [
                'commands' => [
                    [
                        'command_name' => 'support',
                        'command_description' => 'Contact customer support'
                    ]
                ],
                'enable_welcome_message' => true,
                'prompts' => ['How can we help you today?'],
                'id' => 'conversational-automation-id'
            ]
        ]);

        $automation = $this->client->conversationalComponents()->get();

        $this->assertEquals('106540352242922', $automation->getPhoneNumberId());
        $this->assertTrue($automation->getEnableWelcomeMessage());
        $this->assertEquals(['How can we help you today?'], $automation->getPrompts());
        $commands = $automation->getCommands();
        $this->assertCount(1, $commands);
        $this->assertEquals('support', $commands[0]['command_name']);
        $this->assertEquals('Contact customer support', $commands[0]['command_description']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/conversational_automation', $request->getUri()->getPath());
    }

    public function testConfigureConversationalAutomation(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->conversationalComponents()->configure(
            [ConversationalComponentsApi::command('support', '联系客服')],
            true,
            ['您好，请问有什么可以帮您？']
        );

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/conversational_automation', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('support', $payload['commands'][0]['command_name']);
        $this->assertEquals('联系客服', $payload['commands'][0]['command_description']);
        $this->assertTrue($payload['enable_welcome_message']);
        $this->assertEquals(['您好，请问有什么可以帮您？'], $payload['prompts']);
    }

    public function testConfigureConversationalAutomationRejectsEmptyCommandName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('command_name 不能为空');

        $this->client->conversationalComponents()->configure(
            [['command_name' => '  ', 'command_description' => 'Contact support']]
        );
    }

    public function testConfigureConversationalAutomationRejectsInvalidPrompt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('prompts[1] 必须为非空字符串');

        $this->client->conversationalComponents()->configure(
            [ConversationalComponentsApi::command('support', '联系客服')],
            false,
            ['合法提示语', '   ']
        );
    }

    public function testBlockUsers(): void
    {
        $this->mockJsonResponse(200, [
            'messaging_product' => 'whatsapp',
            'block_users' => [
                'added_users' => [
                    ['input' => '+1234567890', 'wa_id' => '1234567890']
                ],
                'failed_users' => []
            ]
        ]);

        $response = $this->client->blockUsers()->block(['+1234567890']);

        $this->assertTrue($response->isSuccess());
        $this->assertNull($response->getError());
        $this->assertEquals('whatsapp', $response->getMessagingProduct());
        $added = $response->getAddedUsers();
        $this->assertCount(1, $added);
        $this->assertEquals('1234567890', $added[0]['wa_id']);
        $this->assertSame([], $response->getFailedUsers());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/block_users', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('+1234567890', $payload['block_users'][0]['user']);
    }

    public function testBlockUsersMixedFailure(): void
    {
        // 400"混合成功/失败"：不抛异常，返回携带部分结果与 error 的响应对象
        $this->mockJsonResponse(400, [
            'messaging_product' => 'whatsapp',
            'block_users' => [
                'added_users' => [
                    ['input' => '+1234567890', 'wa_id' => '1234567890']
                ],
                'failed_users' => [
                    [
                        'input' => 'invalid-number',
                        'errors' => [
                            ['code' => 131000, 'message' => 'Something went wrong']
                        ]
                    ]
                ]
            ],
            'error' => [
                'code' => 131000,
                'message' => 'Mixed success/failure',
                'type' => 'OAuthException'
            ]
        ]);

        $response = $this->client->blockUsers()->block(['+1234567890', 'invalid-number']);

        $this->assertFalse($response->isSuccess());
        $this->assertNotNull($response->getError());
        $this->assertEquals('Mixed success/failure', $response->getError()['message']);
        $this->assertCount(1, $response->getAddedUsers());
        $this->assertCount(1, $response->getFailedUsers());
    }

    public function testUnblockUsers(): void
    {
        $this->mockJsonResponse(200, [
            'messaging_product' => 'whatsapp',
            'block_users' => [
                'added_users' => [
                    ['input' => '+1234567890', 'wa_id' => '1234567890']
                ],
                'failed_users' => []
            ]
        ]);

        $response = $this->client->blockUsers()->unblock(['+1234567890']);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/block_users', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('+1234567890', $payload['block_users'][0]['user']);
    }

    public function testListBlockedUsers(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                [
                    'block_users' => [
                        ['input' => '+1234567890', 'wa_id' => '1234567890'],
                        ['input' => '+9876543210', 'wa_id' => '9876543210']
                    ]
                ]
            ],
            'paging' => [
                'cursors' => [
                    'after' => 'cursor-after',
                    'before' => 'cursor-before'
                ],
                'next' => 'https://waba-v2.360dialog.io/block_users?after=cursor-after'
            ]
        ]);

        $list = $this->client->blockUsers()->list(10);

        $this->assertCount(2, $list->getUsers());
        $this->assertEquals('1234567890', $list->getUsers()[0]['wa_id']);
        $this->assertEquals('cursor-after', $list->getAfterCursor());
        $this->assertEquals('cursor-before', $list->getBeforeCursor());
        $this->assertTrue($list->hasNextPage());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/block_users', $request->getUri()->getPath());
        $this->assertStringContainsString('limit=10', $request->getUri()->getQuery());
    }

    public function testListBlockedUsersWithoutPaging(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['block_users' => []]
            ]
        ]);

        $list = $this->client->blockUsers()->list();

        $this->assertSame([], $list->getUsers());
        $this->assertNull($list->getAfterCursor());
        $this->assertFalse($list->hasNextPage());
    }

    public function testBlockUsersRejectsEmptyUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('users[1] 必须为非空的用户号码');

        $this->client->blockUsers()->block(['+1234567890', '   ']);
    }

    public function testGetBusinessProfile(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                [
                    'messaging_product' => 'whatsapp',
                    'about' => 'Hi, we are here to help',
                    'address' => 'Room 1201, Tower A, Hong Kong',
                    'description' => 'An e-commerce business',
                    'email' => 'support@example.com',
                    'profile_picture_url' => 'https://example.com/avatar.jpg',
                    'vertical' => 'RETAIL',
                    'websites' => ['https://example.com', 'https://blog.example.com']
                ]
            ]
        ]);

        $profile = $this->client->profile()->get(['about', 'email', 'websites']);

        $this->assertEquals('whatsapp', $profile->getMessagingProduct());
        $this->assertEquals('Hi, we are here to help', $profile->getAbout());
        $this->assertEquals('support@example.com', $profile->getEmail());
        $this->assertEquals('RETAIL', $profile->getVertical());
        $this->assertCount(2, $profile->getWebsites());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/whatsapp_business_profile', $request->getUri()->getPath());
        $this->assertEquals('fields=about%2Cemail%2Cwebsites', $request->getUri()->getQuery());
    }

    public function testGetBusinessProfileDefaultFields(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['messaging_product' => 'whatsapp', 'about' => 'Hi']
            ]
        ]);

        $profile = $this->client->profile()->get();

        $this->assertEquals('Hi', $profile->getAbout());
        $this->assertEquals('', $profile->getVertical());

        // 未指定 fields 时不带查询参数
        $request = $this->getLastCapturedRequest();
        $this->assertEquals('', $request->getUri()->getQuery());
    }

    public function testGetBusinessProfileRejectsUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('含未知字段"nickname"');

        $this->client->profile()->get(['about', 'nickname']);
    }

    public function testUpdateBusinessProfile(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->profile()->update([
            'about' => 'Hi, we are here to help',
            'email' => 'support@example.com',
            'vertical' => 'RETAIL',
            'websites' => ['https://example.com'],
        ]);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/whatsapp_business_profile', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('Hi, we are here to help', $payload['about']);
        $this->assertEquals('support@example.com', $payload['email']);
        $this->assertEquals('RETAIL', $payload['vertical']);
        $this->assertEquals(['https://example.com'], $payload['websites']);
    }

    public function testUpdateBusinessProfileRejectsUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('含未知字段"profile_picture_url"');

        // GET 返回的是 profile_picture_url，更新时应使用 profile_picture_handle
        $this->client->profile()->update(['profile_picture_url' => 'https://example.com/avatar.jpg']);
    }

    public function testUpdateBusinessProfileRejectsInvalidVertical(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vertical 取值"SHOPPING"不合法');

        $this->client->profile()->update(['vertical' => 'SHOPPING']);
    }

    public function testUpdateBusinessProfileRejectsInvalidWebsites(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('websites[1] 必须为非空字符串');

        $this->client->profile()->update(['websites' => ['https://example.com', '   ']]);
    }

    public function testListGroups(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                'groups' => [
                    ['id' => 'gid-1', 'subject' => 'Support Group', 'created_at' => 1700000000],
                    ['id' => 'gid-2', 'subject' => 'Sales Group', 'created_at' => 1700000100]
                ]
            ],
            'paging' => [
                'cursors' => ['after' => 'cursor-after', 'before' => 'cursor-before'],
                'next' => 'https://waba-v2.360dialog.io/groups?after=cursor-after'
            ]
        ]);

        $list = $this->client->groups()->list(30);

        $this->assertCount(2, $list->getGroups());
        $this->assertEquals('gid-1', $list->getGroups()[0]['id']);
        $this->assertEquals('cursor-after', $list->getAfterCursor());
        $this->assertEquals('cursor-before', $list->getBeforeCursor());
        $this->assertTrue($list->hasNextPage());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/groups', $request->getUri()->getPath());
        $this->assertEquals('limit=30', $request->getUri()->getQuery());
    }

    public function testListGroupsRejectsInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('limit 必须在 1 到 1024 之间');

        $this->client->groups()->list(0);
    }

    public function testCreateGroup(): void
    {
        $this->mockJsonResponse(200, ['id' => 'gid-new']);

        $response = $this->client->groups()->create('Support Group', 'Customer support', true);

        $this->assertEquals('gid-new', $response->getGroupId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/groups', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('Support Group', $payload['subject']);
        $this->assertEquals('Customer support', $payload['description']);
        $this->assertTrue($payload['join_approval_mode']);
    }

    public function testCreateGroupRequiresSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('subject 不能为空');

        $this->client->groups()->create('   ');
    }

    public function testGetGroupInfo(): void
    {
        $this->mockJsonResponse(200, [
            'id' => 'gid-1',
            'subject' => 'Support Group',
            'description' => 'Customer support',
            'creation_timestamp' => 1700000000,
            'join_approval_mode' => true,
            'messaging_product' => 'whatsapp',
            'suspended' => false,
            'total_participant_count' => 2,
            'participants' => [
                ['wa_id' => '1234567890'],
                ['wa_id' => '9876543210']
            ]
        ]);

        $info = $this->client->groups()->get('gid-1', ['subject', 'participants']);

        $this->assertEquals('gid-1', $info->getGroupId());
        $this->assertEquals('Support Group', $info->getSubject());
        $this->assertEquals('Customer support', $info->getDescription());
        $this->assertEquals(1700000000, $info->getCreationTimestamp());
        $this->assertTrue($info->getJoinApprovalMode());
        $this->assertFalse($info->isSuspended());
        $this->assertEquals(2, $info->getTotalParticipantCount());
        $this->assertEquals(['1234567890', '9876543210'], $info->getParticipantWaIds());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/groups/gid-1', $request->getUri()->getPath());
        $this->assertEquals('fields=subject%2Cparticipants', $request->getUri()->getQuery());
    }

    public function testUpdateGroupWithJson(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->groups()->update('gid-1', [
            'subject' => 'New Subject',
            'description' => 'New description',
        ]);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/groups/gid-1', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('New Subject', $payload['subject']);
        $this->assertEquals('New description', $payload['description']);
    }

    public function testUpdateGroupWithProfilePictureUsesMultipart(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $pictureFile = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($pictureFile, 'fake-image-bytes');

        try {
            $response = $this->client->groups()->update('gid-1', [
                'subject' => 'New Subject',
                'profile_picture_file' => $pictureFile,
            ]);

            $this->assertTrue($response->isSuccess());

            $request = $this->getLastCapturedRequest();
            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('/groups/gid-1', $request->getUri()->getPath());
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));

            $body = (string) $request->getBody();
            $this->assertStringContainsString('name="profile_picture_file"', $body);
            $this->assertStringContainsString(basename($pictureFile), $body);
            $this->assertStringContainsString('name="subject"', $body);
        } finally {
            unlink($pictureFile);
        }
    }

    public function testUpdateGroupRejectsUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('含未知字段"members"');

        $this->client->groups()->update('gid-1', ['members' => ['1234567890']]);
    }

    public function testDeleteGroup(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->groups()->delete('gid-1');

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/groups/gid-1', $request->getUri()->getPath());
    }

    public function testGetInviteLink(): void
    {
        $this->mockJsonResponse(200, [
            'invite_link' => 'https://chat.whatsapp.com/abc123',
            'messaging_product' => 'whatsapp'
        ]);

        $response = $this->client->groups()->getInviteLink('gid-1');

        $this->assertEquals('https://chat.whatsapp.com/abc123', $response->getInviteLink());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/groups/gid-1/invite_link', $request->getUri()->getPath());
    }

    public function testResetInviteLink(): void
    {
        $this->mockJsonResponse(200, [
            'invite_link' => 'https://chat.whatsapp.com/new-link',
            'messaging_product' => 'whatsapp'
        ]);

        $response = $this->client->groups()->resetInviteLink('gid-1');

        $this->assertEquals('https://chat.whatsapp.com/new-link', $response->getInviteLink());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/groups/gid-1/invite_link', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
    }

    public function testListJoinRequests(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['join_request_id' => 'jr-1', 'wa_id' => '1234567890', 'creation_timestamp' => 1700000000],
                ['join_request_id' => 'jr-2', 'wa_id' => '9876543210', 'creation_timestamp' => 1700000100]
            ],
            'paging' => [
                'cursors' => ['after' => 'cursor-after']
            ]
        ]);

        $list = $this->client->groups()->listJoinRequests('gid-1', 10);

        $this->assertCount(2, $list->getJoinRequests());
        $this->assertEquals('jr-1', $list->getJoinRequests()[0]['join_request_id']);
        $this->assertEquals('cursor-after', $list->getAfterCursor());
        $this->assertFalse($list->hasNextPage());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/groups/gid-1/join_requests', $request->getUri()->getPath());
        $this->assertEquals('limit=10', $request->getUri()->getQuery());
    }

    public function testApproveJoinRequests(): void
    {
        $this->mockJsonResponse(200, [
            'approved_join_requests' => ['jr-1', 'jr-2'],
            'rejected_join_requests' => [],
            'failed_join_requests' => [],
            'messaging_product' => 'whatsapp'
        ]);

        $response = $this->client->groups()->approveJoinRequests('gid-1', ['jr-1', 'jr-2']);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(['jr-1', 'jr-2'], $response->getApprovedJoinRequests());
        $this->assertSame([], $response->getFailedJoinRequests());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/groups/gid-1/join_requests', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals(['jr-1', 'jr-2'], $payload['join_requests']);
    }

    public function testApproveJoinRequestsWithPartialFailure(): void
    {
        $this->mockJsonResponse(200, [
            'approved_join_requests' => ['jr-1'],
            'failed_join_requests' => [
                [
                    'join_request_id' => 'jr-bad',
                    'errors' => [
                        ['code' => 131000, 'message' => 'Request not found']
                    ]
                ]
            ],
            'messaging_product' => 'whatsapp'
        ]);

        $response = $this->client->groups()->approveJoinRequests('gid-1', ['jr-1', 'jr-bad']);

        $this->assertFalse($response->isSuccess());
        $this->assertCount(1, $response->getApprovedJoinRequests());
        $this->assertEquals('jr-bad', $response->getFailedJoinRequests()[0]['join_request_id']);
        $this->assertEquals('Request not found', $response->getFailedJoinRequests()[0]['errors'][0]['message']);
    }

    public function testRejectJoinRequests(): void
    {
        $this->mockJsonResponse(200, [
            'rejected_join_requests' => ['jr-2'],
            'failed_join_requests' => [],
            'messaging_product' => 'whatsapp'
        ]);

        $response = $this->client->groups()->rejectJoinRequests('gid-1', ['jr-2']);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(['jr-2'], $response->getRejectedJoinRequests());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/groups/gid-1/join_requests', $request->getUri()->getPath());
    }

    public function testJoinRequestsRequireIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('joinRequestIds 不能为空');

        $this->client->groups()->approveJoinRequests('gid-1', []);
    }

    public function testRemoveParticipants(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->groups()->removeParticipants('gid-1', ['+1234567890', '+9876543210']);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/groups/gid-1/participants', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('+1234567890', $payload['participants'][0]['user']);
        $this->assertCount(2, $payload['participants']);
    }

    public function testRemoveParticipantsRejectsOverMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('单次最多移除 8 名成员');

        $this->client->groups()->removeParticipants('gid-1', array_fill(0, 9, '+1234567890'));
    }

    public function testRemoveParticipantsRejectsEmptyUsers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('users 不能为空');

        $this->client->groups()->removeParticipants('gid-1', []);
    }

    public function testSendMarketingMessage(): void
    {
        $this->mockJsonResponse(200, [
            'messaging_product' => 'whatsapp',
            'contacts' => [
                ['input' => '+1234567890', 'wa_id' => '1234567890', 'user_id' => 'bsuid-123']
            ],
            'messages' => [
                ['id' => 'wamid.marketing-1', 'message_status' => 'accepted']
            ]
        ]);

        $response = $this->client->marketing()->send(
            '+1234567890',
            [
                'name' => 'promo_template',
                'language' => 'en',
                'components' => [
                    ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'Alice']]]
                ],
            ],
            1.5
        );

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.marketing-1', $response->getMessageId());
        $this->assertEquals('accepted', $response->getMessageStatus());
        $this->assertEquals('1234567890', $response->getWaId());
        $this->assertEquals('bsuid-123', $response->getUserId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/marketing_messages', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('template', $payload['type']);
        $this->assertEquals('+1234567890', $payload['to']);
        $this->assertEquals('promo_template', $payload['template']['name']);
        $this->assertEquals(['code' => 'en'], $payload['template']['language']);
        $this->assertEquals('Alice', $payload['template']['components'][0]['parameters'][0]['text']);
        $this->assertEquals(1.5, $payload['bid_spec']['per_message_bid_multiplier']);
    }

    public function testSendMarketingMessageWithBsuidRecipient(): void
    {
        $this->mockJsonResponse(200, [
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => 'US.abc123', 'wa_id' => 'US.abc123', 'user_id' => 'US.abc123']],
            'messages' => [['id' => 'wamid.marketing-2', 'message_status' => 'accepted']]
        ]);

        $response = $this->client->marketing()->send(
            '',
            ['name' => 'promo_template', 'language' => ['code' => 'en']],
            null,
            ['recipient' => 'US.abc123', 'product_policy' => 'promotional']
        );

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('US.abc123', $payload['recipient']);
        $this->assertArrayNotHasKey('to', $payload);
        $this->assertEquals('promotional', $payload['product_policy']);
        $this->assertArrayNotHasKey('bid_spec', $payload);
    }

    public function testSendMarketingMessageFailure(): void
    {
        // 4xx（如模板未批准 403）不抛异常，返回 isSuccess=false 的响应对象
        $this->mockJsonResponse(403, ['error' => 'Template not approved or insufficient permissions']);

        $response = $this->client->marketing()->send(
            '+1234567890',
            ['name' => 'unapproved_template', 'language' => 'en']
        );

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Template not approved or insufficient permissions', $response->getErrorMessage());
        $this->assertEquals('', $response->getMessageId());
    }

    public function testSendMarketingMessageRequiresTemplateName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('template.name 不能为空');

        $this->client->marketing()->send('+1234567890', ['language' => 'en']);
    }

    public function testSendMarketingMessageRequiresLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('template.language 不能为空');

        $this->client->marketing()->send('+1234567890', ['name' => 'promo_template']);
    }

    public function testGetMarketingDataset(): void
    {
        $this->mockJsonResponse(200, ['id' => 'dataset-1', 'name' => 'My dataset']);

        $dataset = $this->client->marketing()->getDataset();

        $this->assertEquals('dataset-1', $dataset->getDatasetId());
        $this->assertEquals('My dataset', $dataset->getName());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/dataset', $request->getUri()->getPath());
    }

    public function testCreateMarketingDataset(): void
    {
        $this->mockJsonResponse(200, ['id' => 'dataset-1', 'name' => 'My dataset']);

        $dataset = $this->client->marketing()->createDataset('My dataset');

        $this->assertEquals('dataset-1', $dataset->getDatasetId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/marketing/dataset', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('My dataset', $payload['name']);
    }

    public function testGetDatasetForMetaNode(): void
    {
        $this->mockJsonResponse(200, ['id' => 'dataset-2', 'name' => 'Page dataset']);

        $dataset = $this->client->marketing()->getDatasetFor('page-123');

        $this->assertEquals('dataset-2', $dataset->getDatasetId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/page-123/dataset', $request->getUri()->getPath());
    }

    public function testCreateDatasetForMetaNode(): void
    {
        $this->mockJsonResponse(200, ['id' => 'dataset-2', 'name' => 'Page dataset']);

        $dataset = $this->client->marketing()->createDatasetFor('page-123', 'Page dataset');

        $this->assertEquals('dataset-2', $dataset->getDatasetId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/marketing/page-123/dataset', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('Page dataset', $payload['name']);
    }

    public function testGetDatasetQuality(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['agent_name' => 'default', 'quality' => 'GREEN']
            ]
        ]);

        $data = $this->client->marketing()->getDatasetQuality('dataset-1', 'default', ['quality']);

        $this->assertEquals('GREEN', $data['data'][0]['quality']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/dataset_quality', $request->getUri()->getPath());
        $this->assertStringContainsString('dataset_id=dataset-1', $request->getUri()->getQuery());
        $this->assertStringContainsString('agent_name=default', $request->getUri()->getQuery());
        $this->assertStringContainsString('fields=quality', $request->getUri()->getQuery());
    }

    public function testGetReachEstimate(): void
    {
        $this->mockJsonResponse(200, [
            'estimates' => [
                [
                    'bid_amount' => 100,
                    'deliveries_lower_bound' => 500,
                    'deliveries_upper_bound' => 900,
                    'cost_lower_bound' => 10.5,
                    'cost_upper_bound' => 20.5,
                    'users' => 1000
                ]
            ],
            'waba_currency' => 'USD'
        ]);

        $estimate = $this->client->marketing()->getReachEstimate(
            ['geo_locations' => ['countries' => ['BR']]],
            'L7D'
        );

        $this->assertEquals('USD', $estimate->getCurrency());
        $this->assertCount(1, $estimate->getEstimates());
        $this->assertEquals(500, $estimate->getEstimates()[0]['deliveries_lower_bound']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/reachestimate', $request->getUri()->getPath());

        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('targeting_spec=' . rawurlencode((string) json_encode(['geo_locations' => ['countries' => ['BR']]])), $query);
        $this->assertStringContainsString('date_interval=L7D', $query);
    }

    public function testGetReachEstimateRejectsInvalidInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dateInterval 取值"L3D"不合法');

        $this->client->marketing()->getReachEstimate(['geo_locations' => ['countries' => ['BR']]], 'L3D');
    }

    public function testListMarketingTemplates(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['id' => 'tpl-1', 'name' => 'promo_template'],
                ['id' => 'tpl-2', 'name' => 'sale_template']
            ],
            'paging' => [
                'cursors' => ['after' => 'cursor-after'],
                'next' => 'https://waba-v2.360dialog.io/marketing/message_templates?after=cursor-after'
            ]
        ]);

        $list = $this->client->marketing()->listTemplates(20);

        $this->assertCount(2, $list->getTemplates());
        $this->assertEquals('promo_template', $list->getTemplates()[0]['name']);
        $this->assertEquals('cursor-after', $list->getAfterCursor());
        $this->assertTrue($list->hasNextPage());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/message_templates', $request->getUri()->getPath());
        $this->assertEquals('limit=20', $request->getUri()->getQuery());
    }

    public function testGetMarketingTemplateById(): void
    {
        $this->mockJsonResponse(200, [
            'id' => 'tpl-1',
            'name' => 'promo_template',
            'status' => 'APPROVED'
        ]);

        $data = $this->client->marketing()->getTemplate('tpl-1');

        $this->assertEquals('APPROVED', $data['status']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/tpl-1', $request->getUri()->getPath());
    }

    public function testGetTemplateAnalytics(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['template_id' => 'tpl-1', 'sent' => 100]
            ]
        ]);

        $data = $this->client->marketing()->getTemplateAnalytics(['date_preset' => 'last_7d']);

        $this->assertEquals(100, $data['data'][0]['sent']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/template_analytics', $request->getUri()->getPath());
        $this->assertStringContainsString('date_preset=last_7d', $request->getUri()->getQuery());
    }

    public function testEnableTemplateAnalytics(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $data = $this->client->marketing()->enableTemplateAnalytics();

        $this->assertTrue($data['success']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/marketing/template_analytics', $request->getUri()->getPath());
        $this->assertStringContainsString('enable=true', $request->getUri()->getQuery());
    }

    public function testGetInsights(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['impressions' => 500, 'clicks' => 25]
            ]
        ]);

        $data = $this->client->marketing()->getInsights('ad-object-1', ['fields' => 'impressions,clicks']);

        $this->assertEquals(500, $data['data'][0]['impressions']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/marketing/ad-object-1/insights', $request->getUri()->getPath());
    }

    public function testSendMarketingEvents(): void
    {
        $this->mockJsonResponse(200, [
            'events_received' => 2,
            'fbtrace_id' => 'trace-1',
            'messages' => []
        ]);

        $response = $this->client->marketing()->sendEvents('dataset-1', [
            ['event_name' => 'Purchase', 'event_time' => 1700000000],
            ['event_name' => 'Lead', 'event_time' => 1700000100],
        ]);

        $this->assertEquals(2, $response->getEventsReceived());
        $this->assertEquals('trace-1', $response->getFbtraceId());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/marketing/dataset-1/events', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertCount(2, $payload['data']);
        $this->assertEquals('Purchase', $payload['data'][0]['event_name']);
    }

    public function testSendMarketingEventsRejectsOverMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('单次最多上报 1000 条事件');

        $this->client->marketing()->sendEvents('dataset-1', array_fill(0, 1001, ['event_name' => 'Purchase']));
    }

    public function testListMessageTemplates(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                ['id' => 'tpl-1', 'name' => 'promo', 'status' => 'APPROVED', 'category' => 'MARKETING'],
                ['id' => 'tpl-2', 'name' => 'otp', 'status' => 'PENDING', 'category' => 'AUTHENTICATION']
            ],
            'paging' => [
                'cursors' => ['after' => 'cursor-after', 'before' => 'cursor-before'],
                'next' => 'https://waba-v2.360dialog.io/message_templates?after=cursor-after'
            ]
        ]);

        $list = $this->client->templates()->listMessageTemplates(20, null, null, ['name', 'status']);

        $this->assertCount(2, $list->getTemplates());
        $this->assertEquals('promo', $list->getTemplates()[0]['name']);
        $this->assertEquals('cursor-after', $list->getAfterCursor());
        $this->assertTrue($list->hasNextPage());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/message_templates', $request->getUri()->getPath());
        $this->assertEquals('limit=20&fields=name%2Cstatus', $request->getUri()->getQuery());
    }

    public function testGetTemplateLibrary(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                [
                    'name' => 'order_update',
                    'category' => 'UTILITY',
                    'language' => 'en_US',
                    'body' => 'Your order {{1}} has shipped',
                    'topic' => 'ORDER_STATUS'
                ]
            ],
            'paging' => ['cursors' => ['after' => 'cursor-after']]
        ]);

        $list = $this->client->templates()->getTemplateLibrary();

        $this->assertCount(1, $list->getTemplates());
        $this->assertEquals('order_update', $list->getTemplates()[0]['name']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/message_template_library', $request->getUri()->getPath());
    }

    public function testGetMessageTemplate(): void
    {
        $this->mockJsonResponse(200, [
            'id' => 'tpl-1',
            'name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}']
            ]
        ]);

        $template = $this->client->templates()->get('tpl-1', ['name', 'status', 'components']);

        $this->assertEquals('tpl-1', $template->getTemplateId());
        $this->assertEquals('promo', $template->getName());
        $this->assertEquals('MARKETING', $template->getCategory());
        $this->assertEquals('en_US', $template->getLanguage());
        $this->assertEquals('APPROVED', $template->getStatus());
        $this->assertCount(1, $template->getComponents());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/message_templates/tpl-1', $request->getUri()->getPath());
        $this->assertStringContainsString('fields=name', $request->getUri()->getQuery());
    }

    public function testCreateMessageTemplate(): void
    {
        $this->mockJsonResponse(200, [
            'id' => 'tpl-new',
            'status' => 'PENDING',
            'category' => 'MARKETING'
        ]);

        $response = $this->client->templates()->create(
            'promo',
            'en_US',
            'MARKETING',
            [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, enjoy 20% off!'],
            ],
            ['allow_category_change' => true]
        );

        $this->assertEquals('tpl-new', $response->getTemplateId());
        $this->assertEquals('PENDING', $response->getStatus());
        $this->assertEquals('MARKETING', $response->getCategory());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/message_templates', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('promo', $payload['name']);
        $this->assertEquals('en_US', $payload['language']);
        $this->assertEquals('MARKETING', $payload['category']);
        $this->assertEquals('Hello {{1}}, enjoy 20% off!', $payload['components'][0]['text']);
        $this->assertTrue($payload['allow_category_change']);
    }

    public function testCreateMessageTemplateRequiresComponents(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('components 不能为空');

        $this->client->templates()->create('promo', 'en_US', 'MARKETING', []);
    }

    public function testCreateMessageTemplateRejectsUnknownOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('含未知字段"send_now"');

        $this->client->templates()->create(
            'promo',
            'en_US',
            'MARKETING',
            [['type' => 'BODY', 'text' => 'Hi']],
            ['send_now' => true]
        );
    }

    public function testUpdateMessageTemplate(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->templates()->update('tpl-1', [
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => 'Updated text']],
        ]);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/message_templates/tpl-1', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('UTILITY', $payload['category']);
        $this->assertEquals('Updated text', $payload['components'][0]['text']);
    }

    public function testUpdateMessageTemplateRejectsUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('含未知字段"name"');

        $this->client->templates()->update('tpl-1', ['name' => 'new-name']);
    }

    public function testDeleteTemplateByName(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->templates()->deleteByName('promo');

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('/message_templates', $request->getUri()->getPath());
        $this->assertEquals('name=promo', $request->getUri()->getQuery());
    }

    public function testDeleteTemplateById(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->templates()->deleteById('tpl-1');

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('hsm_id=tpl-1', $request->getUri()->getQuery());
    }

    public function testDeleteTemplatesByIds(): void
    {
        $this->mockJsonResponse(200, ['success' => true]);

        $response = $this->client->templates()->deleteByIds(['tpl-1', 'tpl-2']);

        $this->assertTrue($response->isSuccess());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('hsm_ids=' . rawurlencode((string) json_encode(['tpl-1', 'tpl-2'])), $request->getUri()->getQuery());
    }

    public function testArchiveTemplates(): void
    {
        $this->mockJsonResponse(200, [
            'archived_templates' => ['tpl-1', 'tpl-2'],
            'failed_templates' => []
        ]);

        $response = $this->client->templates()->archive(['tpl-1', 'tpl-2']);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(['tpl-1', 'tpl-2'], $response->getProcessedTemplates());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/message_templates/archive', $request->getUri()->getPath());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals(['tpl-1', 'tpl-2'], $payload['hsm_ids']);
    }

    public function testArchiveTemplatesWithFailures(): void
    {
        $this->mockJsonResponse(200, [
            'archived_templates' => ['tpl-1'],
            'failed_templates' => [
                'tpl-2' => ['error' => 'Template not in ARCHIVED-able state']
            ]
        ]);

        $response = $this->client->templates()->archive(['tpl-1', 'tpl-2']);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals(['tpl-1'], $response->getProcessedTemplates());
        $this->assertEquals('Template not in ARCHIVED-able state', $response->getFailedTemplates()['tpl-2']['error']);
    }

    public function testUnarchiveTemplates(): void
    {
        $this->mockJsonResponse(200, [
            'unarchived_templates' => ['tpl-1'],
            'failed_templates' => []
        ]);

        $response = $this->client->templates()->unarchive(['tpl-1']);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(['tpl-1'], $response->getProcessedTemplates());

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/message_templates/unarchive', $request->getUri()->getPath());
    }

    public function testArchiveRejectsOverMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('单次最多处理 100 个模板');

        $this->client->templates()->archive(array_fill(0, 101, 'tpl-1'));
    }

    public function testCompareTemplates(): void
    {
        $this->mockJsonResponse(200, [
            'data' => [
                [
                    'metric' => 'BLOCK_RATE',
                    'type' => 'RELATIVE',
                    'number_values' => [['key' => 'tpl-2', 'value' => 0.5]]
                ],
                [
                    'metric' => 'TOP_BLOCK_REASON',
                    'type' => 'STRING_VALUES',
                    'string_values' => [['key' => 'tpl-2', 'value' => 'OTHER']]
                ]
            ]
        ]);

        $response = $this->client->templates()->compare('tpl-1', ['tpl-2'], 1700000000, 1700600000);

        $this->assertCount(2, $response->getMetrics());
        $this->assertEquals('BLOCK_RATE', $response->getMetrics()[0]['metric']);

        $request = $this->getLastCapturedRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/message_templates/tpl-1/compare', $request->getUri()->getPath());

        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('template_ids=' . rawurlencode((string) json_encode(['tpl-2'])), $query);
        $this->assertStringContainsString('start=1700000000', $query);
        $this->assertStringContainsString('end=1700600000', $query);
    }

    public function testGetMediaInfo(): void
    {
        // Cloud API v2 媒体响应结构
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'id' => 'test-media-id',
                'url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=130345565692730173924&ext=1664537344507&hash=ATtBt0Cdio',
                'mime_type' => 'image/jpeg',
                'sha256' => 'test-sha256',
                'file_size' => '1024'
            ]))
        );

        $media = $this->client->getMediaInfo('test-media-id');
        
        $this->assertEquals('test-media-id', $media->getMediaId());
        $this->assertStringContainsString('lookaside.fbsbx.com', $media->getUrl());
        $this->assertEquals('image/jpeg', $media->getMimeType());
        $this->assertEquals(1024, $media->getFileSize());
        $this->assertTrue($media->isImage());
    }

    public function testNetworkError(): void
    {
        // 网络错误（无响应）属于暂时性错误，会触发客户端重试；测试中使用 1 次重试上限以快速验证
        $client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-v2.360dialog.io',
            30,
            1,
            HandlerStack::create($this->mockHandler)
        );

        $this->mockHandler->append(
            new RequestException('Network error', new Request('POST', '/messages'))
        );

        $message = new TextMessage('1234567890', 'Hello World!');

        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('发送消息失败，已重试1次: Network error');

        $client->sendMessage($message);
    }

    public function testSendMessageRetriesOnServerError(): void
    {
        // 5xx 属于暂时性错误，重试后成功
        $client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-v2.360dialog.io',
            30,
            2,
            HandlerStack::create($this->mockHandler)
        );

        // 第一次 500 触发重试，第二次成功
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));
        $this->mockSuccessResponse('wamid.test-retry-message-id');

        $message = new TextMessage('1234567890', 'Hello World!');
        $response = $client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-retry-message-id', $response->getMessageId());
    }

    public function testClientConfiguration(): void
    {
        $config = $this->client->getConfig();
        
        $this->assertEquals('test-api-key', $config['apiKey']);
        $this->assertEquals('test-phone-number-id', $config['phoneNumberId']);
        $this->assertEquals('https://waba-v2.360dialog.io', $config['baseUrl']);
        $this->assertEquals(30, $config['timeout']);
        $this->assertEquals(3, $config['retryAttempts']);
    }

    public function testUnsupportedMethods(): void
    {
        // 测试不再支持的方法抛出异常
        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('Cloud API 暂不支持通过 Messaging API 获取电话号码信息');
        
        $this->client->getPhoneNumberInfo();
    }

    /**
     * 模拟消息发送成功响应
     */
    private function mockSuccessResponse(string $messageId): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => $messageId]
                ]
            ]))
        );
    }

    /**
     * 模拟指定状态的 JSON 响应
     */
    private function mockJsonResponse(int $status, array $body): void
    {
        $this->mockHandler->append(new Response($status, [], (string) json_encode($body)));
    }

    /**
     * 获取最后一次请求（MockHandler 返回值可能为 null，此处断言失败以收窄类型）
     */
    private function getLastCapturedRequest(): RequestInterface
    {
        $request = $this->mockHandler->getLastRequest();
        if ($request === null) {
            self::fail('未捕获到任何请求');
        }

        return $request;
    }

    /**
     * 获取最后一次请求的 payload
     */
    private function getLastRequestPayload(): array
    {
        return json_decode($this->mockHandler->getLastRequest()->getBody()->getContents(), true);
    }
} 