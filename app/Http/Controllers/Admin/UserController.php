<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Administration\Services\AdminUserService;
use App\Domain\Administration\Services\UserDeletionService;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
        private readonly AuditLogger $auditLogger,
        private readonly UserDeletionService $deletionService,
    ) {}

    public function index(): View
    {
        $users = User::query()
            ->with('assignedRole')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        $this->adminUserService->createUser($data);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, false);

        $this->adminUserService->updateUser($user, $data);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $oldValues = $user->toArray();
            $this->deletionService->delete($user);
            $this->auditLogger->log('user.deleted', $user, $oldValues, null);
        } catch (DeletionBlockException $exception) {
            return redirect()->route('admin.users.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->adminUserService->resetPassword($user, $validated['password']);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Password reset successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($creating ? '' : ','.$request->route('user')->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => (int) $validated['role_id'],
            'is_active' => $request->boolean('is_active'),
            'password' => $validated['password'] ?? null,
        ];
    }
}
