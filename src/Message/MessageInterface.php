<?php

namespace Dialog360\Message;

interface MessageInterface
{
    /**
     * 获取消息接收者
     */
    public function getTo(): string;

    /**
     * 将消息转换为数组格式
     */
    public function toArray(): array;

    /**
     * 获取消息类型
     */
    public function getType(): string;
} 