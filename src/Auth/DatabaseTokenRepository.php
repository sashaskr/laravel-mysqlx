<?php

namespace Sashaskr\Mysqlx\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as BaseDatabaseTokenRepository;
use Illuminate\Support\Carbon;

class DatabaseTokenRepository extends BaseDatabaseTokenRepository
{
    /**
     * @inheritdoc
     */
    protected function getPayload($email, $token)
    {
        return [
            'email' => $email,
            'token' => $this->hasher->make($token),
            'created_at' => Carbon::now()->timezone('UTC')->format('Y-m-d H:i:s'),
        ];
    }
}
