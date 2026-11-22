<?php

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\User;

class DocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, DocumentTemplate $template): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, DocumentTemplate $template): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, DocumentTemplate $template): bool
    {
        // System defaults cannot be deleted
        if ($template->is_system_default) {
            return false;
        }

        return $this->canManage($user);
    }

    public function publish(User $user, DocumentTemplate $template): bool
    {
        return $this->canManage($user);
    }

    public function activate(User $user, DocumentTemplate $template): bool
    {
        return $this->canManage($user);
    }

    public function generate(User $user, DocumentTemplate $template): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        if ($user->hasRole('admin') || $user->role === 'admin') {
            return true;
        }

        if ($user->can('official-documents.manage') || $user->can('document-templates.manage')) {
            return true;
        }

        return false;
    }
}
