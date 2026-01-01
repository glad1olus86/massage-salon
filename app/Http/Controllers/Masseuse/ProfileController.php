<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\MassageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the profile.
     */
    public function edit()
    {
        $user = auth()->user();
        $user->load(['massageServices', 'branch']);
        
        // Получаем все доступные услуги от создателя
        $services = MassageService::where('created_by', $user->creatorId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('masseuse.profile.edit', compact('user', 'services'));
    }

    /**
     * Update the profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'photos' => 'nullable|array|max:8',
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
            'existing_photos' => 'nullable|array',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
        ]);

        // Email и роль нельзя менять
        unset($validated['email'], $validated['type']);

        // Обновляем аватар
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        
        // Обрабатываем галерею фото
        $existingPhotos = $request->input('existing_photos', []);
        $currentPhotos = $user->photos ?? [];
        
        // Удаляем фото, которые убрали
        foreach ($currentPhotos as $photo) {
            if (!in_array($photo, $existingPhotos) && Storage::disk('public')->exists($photo)) {
                Storage::disk('public')->delete($photo);
            }
        }
        
        // Добавляем новые фото
        $newPhotos = $existingPhotos;
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (count($newPhotos) < 8) {
                    $newPhotos[] = $photo->store('employee-photos', 'public');
                }
            }
        }
        
        $validated['photos'] = !empty($newPhotos) ? $newPhotos : null;
        unset($validated['existing_photos']);

        $user->update($validated);
        
        // Синхронизируем услуги
        $syncData = [];
        foreach ($request->input('services', []) as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => false];
        }
        foreach ($request->input('extra_services', []) as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => true];
        }
        $user->massageServices()->sync($syncData);

        return redirect()->route('masseuse.profile.edit')
            ->with('success', __('Профиль успешно обновлён.'));
    }
}
