<?php

namespace App\Features\User;

enum Role: string {
    case ADMIN = 'admin';
    case USER = 'user';
}