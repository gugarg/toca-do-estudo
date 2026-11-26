<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class TokenAcessoPessoal extends PersonalAccessToken
{
    protected $table = 'tokens_acesso_pessoal';
}
