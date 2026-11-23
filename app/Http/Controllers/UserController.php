<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::latest('id')->paginate(12)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User, 'roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('success', __('app.saved'));
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['password'])) {
            unset($data['password']);
        }
        if ($request->user()->is($user) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => __('app.cannot_deactivate_self')]);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', __('app.saved'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['user' => __('app.cannot_delete_self')]);
        }
        $user->delete();

        return redirect()->route('users.index')->with('success', __('app.deleted'));
    }
}
