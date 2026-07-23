<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        Announcement::create([
            ...$data,
            'is_published' => true,
        ]);

        return redirect()->route('admin.pengumuman')->with('status', 'Pengumuman berhasil dipublikasikan.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.pengumuman')->with('status', 'Pengumuman dihapus.');
    }
}
