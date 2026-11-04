<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminPanelController extends Controller
{
    // PRD 8.2: keep the Admin navigation available while later-step pages remain placeholders.
    public function __invoke(string $page): View
    {
        $pages = [
            'dashboard' => 'Dashboard',
            'modules' => 'Kelola Modul',
            'units' => 'Kelola Unit & Kuis',
            'questions' => 'Kelola Bank Soal',
            'forum' => 'Forum & Laporan',
            'learners' => 'Daftar Learner',
            'results' => 'Rekap Hasil',
        ];

        abort_unless(array_key_exists($page, $pages), 404);

        return view('admin.placeholder', ['title' => $pages[$page]]);
    }
}
