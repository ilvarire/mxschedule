<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(30);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);
        $user->notify(new AccountCreatedNotification($this->passwordSetupUrl($user), $validated['role']));

        return redirect()->route('admin.users.index')
            ->with('success', 'User created and password setup link emailed.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            'user' => $user,
            ...$this->studentContext($user),
        ]);
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            ...$this->studentContext($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    protected function passwordSetupUrl(User $user): string
    {
        $token = Password::broker()->createToken($user);

        return route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    protected function studentContext(User $user): array
    {
        $user->load([
            'roles',
            'studentProfile.department.faculty',
            'studentProfile.courses.department',
        ]);

        $profile = $user->studentProfile;

        if (! $profile) {
            return [
                'registeredExams' => collect(),
                'examAllocations' => collect(),
            ];
        }

        $examAllocations = $profile->examAllocations()
            ->with([
                'examSession.exam.course',
                'hall',
                'system',
                'examPass',
            ])
            ->get()
            ->sortByDesc(fn ($allocation) => $allocation->examSession?->start_time)
            ->values();

        return [
            'registeredExams' => $this->registeredExamsFor($profile->courses),
            'examAllocations' => $examAllocations,
        ];
    }

    protected function registeredExamsFor(Collection $courses): Collection
    {
        if ($courses->isEmpty()) {
            return collect();
        }

        return Exam::with('course.department')
            ->where(function ($query) use ($courses) {
                foreach ($courses as $course) {
                    $query->orWhere(function ($examQuery) use ($course) {
                        $examQuery
                            ->where('course_id', $course->id)
                            ->where('academic_session', $course->pivot->academic_session)
                            ->where('semester', $course->pivot->semester);
                    });
                }
            })
            ->orderByDesc('exam_date')
            ->orderBy('start_time')
            ->get();
    }
}
