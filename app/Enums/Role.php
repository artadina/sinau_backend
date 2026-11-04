<?php

namespace App\Enums;

enum Role: string
{
    // FR-S01: persisted values follow PRD 7.4 and the role enum migration.
    case Learner = 'learner';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
}
