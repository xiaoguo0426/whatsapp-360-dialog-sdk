<?php

namespace Dialog360\Message;

class ContactMessage  implements MessageInterface
{

    private string $to;
    private array $contacts;

    public function __construct(string $to, array $contacts)
    {
        $this->to = $to;
        $this->contacts = $contacts;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'contact';
    }

    public function toArray(): array
    {
        return [
            'type' => 'contact',
            'contact' => [
                'contacts' => $this->contacts
            ]
        ];
    }

}