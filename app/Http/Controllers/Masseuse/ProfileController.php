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
        
        // Получаем обычные услуги (is_extra = false)
        $regularServices = MassageService::where('created_by', $user->creatorId())
            ->where('is_active', true)
            ->where('is_extra', false)
            ->orderBy('name')
            ->get();
        
        // Получаем экстра услуги (is_extra = true)
        $extraServices = MassageService::where('created_by', $user->creatorId())
            ->where('is_active', true)
            ->where('is_extra', true)
            ->orderBy('name')
            ->get();
        
        return view('masseuse.profile.edit', compact('user', 'regularServices', 'extraServices'));
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
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'height' => 'nullable|integer|min:100|max:250',
            'weight' => 'nullable|integer|min:30|max:200',
            'breast_size' => 'nullable|integer|min:0|max:10',
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

        // Обновляем основные поля
        $user->name = $validated['name'];
        $user->company_phone = $validated['phone'] ?? null;
        $user->bio = $validated['bio'] ?? null;
        $user->birth_date = $validated['birth_date'] ?? null;
        $user->nationality = $validated['nationality'] ?? null;
        $user->languages = $validated['languages'] ?? null;
        $user->height = $validated['height'] ?? null;
        $user->weight = $validated['weight'] ?? null;
        $user->breast_size = $validated['breast_size'] ?? null;

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

        // Обновляем аватар если загружен
        if (isset($validated['avatar'])) {
            $user->avatar = $validated['avatar'];
        }
        $user->photos = $validated['photos'];
        $user->save();
        
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
