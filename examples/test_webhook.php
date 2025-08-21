<?php
/**
 * 360 Dialog Webhook 测试脚本
 * 
 * 这个脚本用于测试 webhook 接收器的功能
 * 可以模拟不同类型的 webhook 请求
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load(__DIR__.'/../.env');

class WebhookTester
{
    private string $webhookUrl;
    private string $verifyToken;
    
    public function __construct()
    {
        $this->webhookUrl = EnvironmentLoader::get('WEBHOOK_URL', 'http://localhost/webhook/simple_webhook.php');
        $this->verifyToken = EnvironmentLoader::get('DIALOG360_VERIFY_TOKEN', 'your-verify-token');
    }
    
    /**
     * 测试 webhook 验证
     */
    public function testVerification(): void
    {
        echo "🔍 测试 Webhook 验证...\n";
        
        $url = $this->webhookUrl . '?hub_mode=subscribe&hub_verify_token=' . $this->verifyToken . '&hub_challenge=test-challenge-123';
        
        $response = $this->makeRequest($url, 'GET');
        if ($response['status'] === 200 && $response['body'] === 'test-challenge-123') {
            echo "✅ Webhook 验证成功！\n";
        } else {
            echo "❌ Webhook 验证失败！\n";
            echo "状态码: " . $response['status'] . "\n";
            echo "响应: " . $response['body'] . "\n";
        }
    }
    
    /**
     * 测试文本消息接收
     */
    public function testTextMessage(): void
    {
        echo "\n📝 测试文本消息接收...\n";
        
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '1234567890',
                                    'phone_number_id' => 'test-phone-id'
                                ],
                                'contacts' => [
                                    [
                                        'profile' => [
                                            'name' => 'Test User'
                                        ],
                                        'wa_id' => '1234567890'
                                    ]
                                ],
                                'messages' => [
                                    [
                                        'from' => '1234567890',
                                        'id' => 'test-message-id-' . time(),
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, this is a test message!'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest($this->webhookUrl, 'POST', $payload);
        
        if ($response['status'] === 200) {
            echo "✅ 文本消息测试成功！\n";
            echo "响应: " . $response['body'] . "\n";
        } else {
            echo "❌ 文本消息测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
            echo "响应: " . $response['body'] . "\n";
        }
    }
    
    /**
     * 测试图片消息接收
     */
    public function testImageMessage(): void
    {
        echo "\n🖼️ 测试图片消息接收...\n";
        
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '1234567890',
                                    'phone_number_id' => 'test-phone-id'
                                ],
                                'contacts' => [
                                    [
                                        'profile' => [
                                            'name' => 'Test User'
                                        ],
                                        'wa_id' => '1234567890'
                                    ]
                                ],
                                'messages' => [
                                    [
                                        'from' => '1234567890',
                                        'id' => 'test-image-message-id-' . time(),
                                        'timestamp' => time(),
                                        'type' => 'image',
                                        'image' => [
                                            'id' => 'test-image-id',
                                            'mime_type' => 'image/jpeg',
                                            'sha256' => 'test-sha256-hash',
                                            'caption' => 'Test image caption'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest($this->webhookUrl, 'POST', $payload);
        
        if ($response['status'] === 200) {
            echo "✅ 图片消息测试成功！\n";
        } else {
            echo "❌ 图片消息测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
        }
    }
    
    /**
     * 测试交互式消息接收
     */
    public function testInteractiveMessage(): void
    {
        echo "\n🔘 测试交互式消息接收...\n";
        
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '1234567890',
                                    'phone_number_id' => 'test-phone-id'
                                ],
                                'contacts' => [
                                    [
                                        'profile' => [
                                            'name' => 'Test User'
                                        ],
                                        'wa_id' => '1234567890'
                                    ]
                                ],
                                'messages' => [
                                    [
                                        'from' => '1234567890',
                                        'id' => 'test-interactive-message-id-' . time(),
                                        'timestamp' => time(),
                                        'type' => 'interactive',
                                        'interactive' => [
                                            'type' => 'button_reply',
                                            'button_reply' => [
                                                'id' => 'btn_help',
                                                'title' => 'Help'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest($this->webhookUrl, 'POST', $payload);
        
        if ($response['status'] === 200) {
            echo "✅ 交互式消息测试成功！\n";
        } else {
            echo "❌ 交互式消息测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
        }
    }
    
    /**
     * 测试状态更新接收
     */
    public function testStatusUpdate(): void
    {
        echo "\n📊 测试状态更新接收...\n";
        
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '1234567890',
                                    'phone_number_id' => 'test-phone-id'
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'test-message-id',
                                        'status' => 'delivered',
                                        'timestamp' => time(),
                                        'recipient_id' => '1234567890'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest($this->webhookUrl, 'POST', $payload);
        
        if ($response['status'] === 200) {
            echo "✅ 状态更新测试成功！\n";
        } else {
            echo "❌ 状态更新测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
        }
    }
    
    /**
     * 测试错误处理
     */
    public function testErrorHandling(): void
    {
        echo "\n⚠️ 测试错误处理...\n";
        
        // 测试无效的 JSON
        $response = $this->makeRequest($this->webhookUrl, 'POST', 'invalid-json');
        
        if ($response['status'] === 400) {
            echo "✅ 错误处理测试成功！\n";
        } else {
            echo "❌ 错误处理测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
        }
    }
    
    /**
     * 测试不支持的方法
     */
    public function testUnsupportedMethod(): void
    {
        echo "\n🚫 测试不支持的方法...\n";
        
        $response = $this->makeRequest($this->webhookUrl, 'PUT');
        
        if ($response['status'] === 405) {
            echo "✅ 不支持方法测试成功！\n";
        } else {
            echo "❌ 不支持方法测试失败！\n";
            echo "状态码: " . $response['status'] . "\n";
        }
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests(): void
    {
        echo "🚀 开始运行 Webhook 测试...\n";
        echo "Webhook URL: " . $this->webhookUrl . "\n";
        echo "验证令牌: " . $this->verifyToken . "\n\n";
        
        $this->testVerification();
        $this->testTextMessage();
        $this->testImageMessage();
        $this->testInteractiveMessage();
        $this->testStatusUpdate();
        $this->testErrorHandling();
        $this->testUnsupportedMethod();
        
        echo "\n✨ 所有测试完成！\n";
        echo "请检查 webhook 日志文件以查看详细信息。\n";
    }
    
    /**
     * 发送 HTTP 请求
     */
    private function makeRequest(string $url, string $method, $data = null): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: WebhookTester/1.0'
            ]
        ]);
        
        if ($method === 'POST' && $data !== null) {
            if (is_array($data)) {
                $data = json_encode($data);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'status' => 0,
                'body' => 'cURL Error: ' . $error
            ];
        }
        
        return [
            'status' => $httpCode,
            'body' => $response
        ];
    }
}

// 运行测试
if (php_sapi_name() === 'cli') {
    $tester = new WebhookTester();
    $tester->runAllTests();
} else {
    echo "请在命令行中运行此脚本：php test_webhook.php\n";
} 