<?php

namespace App\Controllers;

use App\Models\GmailModel;

class GmailController extends BaseController {
    public function index(){
        // $gmail = new GmailModel();
        // $res = $gmail->getProfile('foo');
        $this->json($this->response());
    }

    public function response(){
        // Dummy "comprehensive" response for Gmail /users/me/profile
        // Setup: Replace this with real API calls once OAuth is configured.

        $res = [
            'success' => true,
            'code'    => 200,
            'error'   => null,
            'data'    => [
                'id'           => 'user_123456789',
                'email'        => 'paul.mctosh@example.com',
                'name'         => 'Paul McIntosh',
                'displayName'  => 'Paul McIntosh',
                'picture'      => 'https://example.com/avatar.png',
                'language'     => 'en',
                'country'      => 'GB',
                'profileUrl'   => 'https://mail.google.com/mail/b/123456789/',

                // Additional metadata you might expect from a real API
                'emails'       => [
                    'primary' => 'paul.mctosh@example.com',
                    'work'    => 'paul.mwork@example.com',
                ],
                'phoneNumbers' => [
                    ['type' => 'mobile', 'value' => '+44 7123 456789'],
                ],
                'addresses'    => [
                    [
                        'type'  => 'home',
                        'city'  => 'Manchester',
                        'region' => 'England',
                        'country' => 'GB',
                    ],
                ],

                // Timestamps for your "digital footprint"
                'created_at'   => '2024-01-15T10:30:00Z',
                'updated_at'   => '2026-06-01T14:22:00Z',
            ],
        ];

        return $res;
    }
}
