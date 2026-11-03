<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\HousingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::with('author')->where('housing_id', $request->user()->housing_id)
            ->when(! $request->user()->isManager(), fn ($q) => $q->where('status', 'published'))
            ->latest('id')->paginate(15);

        return view('announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('announcements.form', ['announcement' => new Announcement]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:10000']]);
        $announcement = Announcement::create($data + ['housing_id' => $request->user()->housing_id, 'created_by' => $request->user()->id]);

        return redirect()->route('announcements.show', $announcement)->with('success', 'Draft pengumuman tersimpan.');
    }

    public function show(Request $request, Announcement $announcement)
    {
        $this->access($request, $announcement);

        return view('announcements.show', compact('announcement'));
    }

    public function edit(Request $request, Announcement $announcement)
    {
        $this->access($request, $announcement);
        abort_unless($announcement->status === 'draft', 403);

        return view('announcements.form', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->access($request, $announcement);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:10000']]);
        DB::transaction(function () use ($announcement, $data) {
            $locked = Announcement::lockForUpdate()->findOrFail($announcement->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['announcement' => 'Hanya draft yang dapat diubah.']);
            }
            $locked->update($data);
        });

        return redirect()->route('announcements.show', $announcement)->with('success', 'Draft diperbarui.');
    }

    public function publish(Request $request, Announcement $announcement)
    {
        $this->access($request, $announcement);
        DB::transaction(function () use ($announcement) {
            $locked = Announcement::lockForUpdate()->findOrFail($announcement->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['announcement' => 'Pengumuman sudah diterbitkan atau diarsipkan.']);
            }
            $locked->update(['status' => 'published', 'published_at' => now()]);
            foreach (User::where('housing_id', $locked->housing_id)->where('role', 'warga')->where('is_active', true)->cursor() as $user) {
                $user->notify(new HousingNotification($locked->housing_id, 'announcement:'.$locked->id, [
                    'event' => 'announcement', 'announcement_id' => $locked->id, 'title' => 'Pengumuman perumahan', 'message' => $locked->title,
                ]));
            }
        });

        return redirect()->route('announcements.show', $announcement)->with('success', 'Pengumuman terbit dan notifikasi dikirim.');
    }

    public function archive(Request $request, Announcement $announcement)
    {
        $this->access($request, $announcement);
        DB::transaction(function () use ($announcement) {
            $locked = Announcement::lockForUpdate()->findOrFail($announcement->id);
            if ($locked->status !== 'published') {
                throw ValidationException::withMessages(['announcement' => 'Hanya pengumuman terbit yang dapat diarsipkan.']);
            }
            $locked->update(['status' => 'archived', 'archived_at' => now()]);
        });

        return redirect()->route('announcements.show', $announcement)->with('success', 'Pengumuman diarsipkan.');
    }

    private function access(Request $request, Announcement $announcement): void
    {
        abort_unless($announcement->housing_id === $request->user()->housing_id, 403);
        abort_if(! $request->user()->isManager() && $announcement->status === 'draft', 404);
    }
}
