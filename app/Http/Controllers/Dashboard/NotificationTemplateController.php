<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{

    public function __construct()
    {
        // 🔐 Permissions middleware
        $this->middleware('can:notifications.view')->only(['index', 'show']);
        $this->middleware('can:notifications.create')->only(['create', 'store']);
        $this->middleware('can:notifications.edit')->only(['edit', 'update']);
        $this->middleware('can:notifications.delete')->only(['destroy']);
    }

    public function index()
    {
        $templates = NotificationTemplate::orderBy('key')->get();
        return view('dashboard.notifications.templates.index', compact('templates'));
    }

    public function edit(NotificationTemplate $template)
    {
        return view('dashboard.notifications.templates.edit', compact('template'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_en'  => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $template->update([
            'title'       => $data['title'],
            'body'        => $data['body'],
            'title_en'    => $data['title_en'] ?? null,
            'body_en'     => $data['body_en'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'updated_by'  => auth()->id(),
        ]);

        return redirect()
            ->route('dashboard.notification-templates.index')
            ->with('success', 'تم تحديث نص الإشعار بنجاح.');
    }
}